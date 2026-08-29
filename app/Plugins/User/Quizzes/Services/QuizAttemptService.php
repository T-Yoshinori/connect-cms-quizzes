<?php

namespace App\Plugins\User\Quizzes\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

use App\Models\User\Quizzes\Quizzes;
use App\Models\User\Quizzes\QuizzesAttempts;
use App\Models\User\Quizzes\QuizzesAttemptPages;
use App\Models\User\Quizzes\QuizzesAttemptQuestions;
use App\Models\User\Quizzes\QuizzesAttemptQuestionChoices;

/**
 * 受験開始とAttemptスナップショットの取得を担当します。
 */
class QuizAttemptService
{
    /**
     * 受験を開始し、その時点の問題構成をスナップショットします。
     */
    public function startAttempt(
        $quiz_id,
        $user_id,
        $page_id,
        $frame_id,
        $is_preview = false
    ) {
        if (empty($user_id)) {
            throw ValidationException::withMessages([
                'user_id' => '受験するにはログインが必要です。',
            ]);
        }

        return DB::transaction(function () use (
            $quiz_id,
            $user_id,
            $page_id,
            $frame_id,
            $is_preview
        ) {
            $quiz = Quizzes::with([
                'pages.questions.current_revision.choices',
                'pages.questions.current_revision.categories',
            ])->lockForUpdate()->findOrFail($quiz_id);

            if (!$is_preview && !$this->isPublishedNow($quiz)) {
                throw ValidationException::withMessages([
                    'quiz' => 'この小テストは現在公開されていません。',
                ]);
            }

            if (!$is_preview) {
                $in_progress = QuizzesAttempts::query()
                    ->where('quiz_id', $quiz->id)
                    ->where('user_id', $user_id)
                    ->where('is_preview', false)
                    ->where('status', 'in_progress')
                    ->lockForUpdate()
                    ->latest('attempt_no')
                    ->first();

                if ($in_progress) {
                    return $in_progress->fresh($this->attemptRelations());
                }

                $this->validateRetryLimit($quiz, $user_id);
            }

            $pages = $quiz->pages
                ->filter(function ($page) {
                    return !$page->trashed();
                })
                ->values();

            $question_count = $pages->sum(function ($page) {
                return $page->questions
                    ->filter(function ($question) {
                        return !$question->trashed()
                            && $question->status === 'active'
                            && !empty($question->current_revision);
                    })
                    ->count();
            });

            if ($question_count === 0) {
                throw ValidationException::withMessages([
                    'quiz' => '受験できる問題が登録されていません。',
                ]);
            }

            $attempt_no = QuizzesAttempts::query()
                ->where('quiz_id', $quiz->id)
                ->where('user_id', $user_id)
                ->where('is_preview', (bool)$is_preview)
                ->lockForUpdate()
                ->max('attempt_no');

            $attempt_no = (int)$attempt_no + 1;
            $started_at = now();

            $attempt = QuizzesAttempts::create([
                'quiz_id' => $quiz->id,
                'page_id' => $page_id ?: null,
                'frame_id' => $frame_id ?: null,
                'user_id' => $user_id,
                'attempt_no' => $attempt_no,
                'status' => 'in_progress',
                'grading_status' => 'not_started',
                'pass_status' => 'pending',
                'started_at' => $started_at,
                'expires_at' => $quiz->time_limit_minutes
                    ? $started_at->copy()->addMinutes($quiz->time_limit_minutes)
                    : null,
                'total_score' => 0,
                'effective_max_score' => 0,
                'score_rate' => null,
                'passing_type_snapshot' => $quiz->passing_type,
                'passing_score_snapshot' => $quiz->passing_score,
                'passing_rate_snapshot' => $quiz->passing_rate,
                'time_limit_minutes_snapshot' => $quiz->time_limit_minutes,
                'is_preview' => (bool)$is_preview,
            ]);

            $max_score = 0;
            $attempt_category_id_map = [];

            if ($quiz->use_category_scoring) {
                $groups = $quiz->active_category_groups()
                    ->with('active_categories')
                    ->get();

                foreach ($groups as $group) {
                    $attempt_group_id = DB::table('quiz_attempt_category_groups')->insertGetId([
                        'quiz_attempt_id' => $attempt->id,
                        'source_category_group_id' => $group->id,
                        'name' => $group->name,
                        'display_sequence' => $group->sequence,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    foreach ($group->active_categories as $category) {
                        $attempt_category_id_map[(int) $category->id] =
                            DB::table('quiz_attempt_categories')->insertGetId([
                                'quiz_attempt_category_group_id' => $attempt_group_id,
                                'source_category_id' => $category->id,
                                'name' => $category->name,
                                'display_sequence' => $category->sequence,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                    }
                }
            }

            foreach ($pages as $page_index => $page) {
                $attempt_page = QuizzesAttemptPages::create([
                    'quiz_attempt_id' => $attempt->id,
                    'quiz_page_id' => $page->id,
                    'title' => $page->title,
                    'description' => $page->description,
                    'display_sequence' => $page_index + 1,
                ]);

                $questions = $page->questions
                    ->filter(function ($question) {
                        return !$question->trashed()
                            && $question->status === 'active'
                            && !empty($question->current_revision);
                    })
                    ->sortBy(function ($question) {
                        return sprintf('%010d-%020d', $question->sequence, $question->id);
                    })
                    ->values();

                if ($quiz->question_order === 'random') {
                    $questions = $questions->shuffle()->values();
                }

                foreach ($questions as $question_index => $question) {
                    $revision = $question->current_revision;

                    $attempt_question = QuizzesAttemptQuestions::create([
                        'quiz_attempt_page_id' => $attempt_page->id,
                        'quiz_question_id' => $question->id,
                        'question_revision_id' => $revision->id,
                        'display_sequence' => $question_index + 1,
                        'points' => $revision->points,
                        'scoring_status' => $revision->question_type === 'essay'
                            ? 'manual_pending'
                            : 'scored',
                    ]);

                    $max_score += (float)$revision->points;

                    if (!empty($attempt_category_id_map)) {
                        foreach ($revision->categories as $category) {
                            $attempt_category_id =
                                $attempt_category_id_map[(int) $category->id] ?? null;

                            if ($attempt_category_id) {
                                DB::table('quiz_attempt_question_categories')->insert([
                                    'quiz_attempt_question_id' => $attempt_question->id,
                                    'quiz_attempt_category_id' => $attempt_category_id,
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ]);
                            }
                        }
                    }

                    $choices = $revision->choices->values();
                    if ($revision->choice_random) {
                        $choices = $choices->shuffle()->values();
                    }

                    foreach ($choices as $choice_index => $choice) {
                        QuizzesAttemptQuestionChoices::create([
                            'quiz_attempt_question_id' => $attempt_question->id,
                            'choice_revision_id' => $choice->id,
                            'display_sequence' => $choice_index + 1,
                        ]);
                    }
                }
            }

            $attempt->effective_max_score = $max_score;
            $attempt->save();

            return $attempt->fresh([
                'quiz',
                'attempt_pages.attempt_questions.question_revision',
                'attempt_pages.attempt_questions.choices.choice_revision',
            ]);
        });
    }

    /**
     * 回答中Attemptを取得します。
     */
    public function getAnsweringAttempt($attempt_id, $user_id)
    {
        $attempt = $this->findOwnedAttempt($attempt_id, $user_id);

        if ($attempt->status !== 'in_progress') {
            throw ValidationException::withMessages([
                'attempt' => 'この受験は回答を終了しています。',
            ]);
        }

        if ($attempt->expires_at && now()->greaterThan($attempt->expires_at)) {
            throw ValidationException::withMessages([
                'attempt' => '制限時間を過ぎています。提出処理を行ってください。',
            ]);
        }

        return $attempt;
    }

    /**
     * 回答画面表示用Attemptを取得します。
     *
     * 制限時間超過後も、保存済み回答の提出導線を表示するために取得を許可します。
     * 回答の保存可否はgetAnsweringAttempt()で引き続き厳格に判定します。
     */
    public function getAnswerDisplayAttempt($attempt_id, $user_id)
    {
        $attempt = $this->findOwnedAttempt($attempt_id, $user_id);

        if ($attempt->status !== 'in_progress') {
            throw ValidationException::withMessages([
                'attempt' => 'この受験は回答を終了しています。',
            ]);
        }

        return $attempt;
    }

    /**
     * 提出可能なAttemptを取得します。
     *
     * 制限時間超過後は回答を変更できませんが、保存済み回答は提出できます。
     */
    public function getSubmittableAttempt($attempt_id, $user_id)
    {
        $attempt = $this->findOwnedAttempt($attempt_id, $user_id);

        if ($attempt->status !== 'in_progress') {
            throw ValidationException::withMessages([
                'attempt' => 'この受験はすでに提出されています。',
            ]);
        }

        return $attempt;
    }

    /**
     * 提出前確認用Attemptを取得します。
     */
    public function getReviewAttempt($attempt_id, $user_id)
    {
        $attempt = $this->findOwnedAttempt($attempt_id, $user_id);

        if ($attempt->status !== 'in_progress') {
            throw ValidationException::withMessages([
                'attempt' => 'この受験はすでに提出されています。',
            ]);
        }

        if (empty($attempt->reviewed_at)) {
            $attempt->reviewed_at = now();
            $attempt->save();
        }

        return $attempt->fresh($this->attemptRelations());
    }

    /**
     * 結果表示用Attemptを取得します。
     */
    public function getResultAttempt($attempt_id, $user_id)
    {
        $attempt = $this->findOwnedAttempt($attempt_id, $user_id);

        if ($attempt->status === 'in_progress') {
            throw ValidationException::withMessages([
                'attempt' => '小テストがまだ提出されていません。',
            ]);
        }

        return $attempt;
    }

    private function findOwnedAttempt($attempt_id, $user_id)
    {
        return QuizzesAttempts::with($this->attemptRelations())
            ->whereKey($attempt_id)
            ->where('user_id', $user_id)
            ->firstOrFail();
    }

    private function attemptRelations()
    {
        return [
            'quiz',
            'attempt_pages.attempt_questions.question_revision.choices',
            'attempt_pages.attempt_questions.question_revision.correct_answers',
            'attempt_pages.attempt_questions.choices.choice_revision',
            'attempt_pages.attempt_questions.answer.current_grade',
        ];
    }

    private function validateRetryLimit(Quizzes $quiz, $user_id)
    {
        $count = QuizzesAttempts::query()
            ->where('quiz_id', $quiz->id)
            ->where('user_id', $user_id)
            ->where('is_preview', false)
            ->whereIn('status', ['submitted', 'graded', 'expired'])
            ->count();

        if ($quiz->retry_type === 'once' && $count > 0) {
            throw ValidationException::withMessages([
                'quiz' => 'この小テストは再受験できません。',
            ]);
        }

        if (
            $quiz->retry_type === 'limited'
            && $count >= (int)$quiz->retry_limit
        ) {
            throw ValidationException::withMessages([
                'quiz' => '受験可能回数の上限に達しています。',
            ]);
        }
    }

    private function isPublishedNow(Quizzes $quiz)
    {
        if (!in_array($quiz->status, ['public', 'published'], true)) {
            return false;
        }

        if ($quiz->publish_start_at && now()->lessThan($quiz->publish_start_at)) {
            return false;
        }

        if ($quiz->publish_end_at && now()->greaterThan($quiz->publish_end_at)) {
            return false;
        }

        return true;
    }
}
