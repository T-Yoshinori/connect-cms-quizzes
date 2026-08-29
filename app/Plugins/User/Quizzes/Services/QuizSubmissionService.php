<?php

namespace App\Plugins\User\Quizzes\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

use App\Models\User\Quizzes\QuizzesAttempts;
use App\Models\User\Quizzes\QuizzesAttemptQuestions;
use App\Models\User\Quizzes\QuizzesAnswers;
use App\Models\User\Quizzes\QuizzesAnswerGrades;

/**
 * 小テスト提出、自動採点、Attempt集計を担当します。
 *
 * AI採点は行いません。essayは必ず手動採点待ちになります。
 */
class QuizSubmissionService
{
    public function submitAttempt($attempt_id, $user_id)
    {
        return DB::transaction(function () use ($attempt_id, $user_id) {
            $attempt = QuizzesAttempts::lockForUpdate()
                ->whereKey($attempt_id)
                ->where('user_id', $user_id)
                ->firstOrFail();

            if ($attempt->status !== 'in_progress') {
                throw ValidationException::withMessages([
                    'attempt' => 'この小テストはすでに提出されています。',
                ]);
            }

            $attempt_questions = QuizzesAttemptQuestions::with([
                'question_revision.choices',
                'question_revision.correct_answers',
                'choices.choice_revision',
                'answer',
            ])
                ->join(
                    'quiz_attempt_pages',
                    'quiz_attempt_pages.id',
                    '=',
                    'quiz_attempt_questions.quiz_attempt_page_id'
                )
                ->where('quiz_attempt_pages.quiz_attempt_id', $attempt->id)
                ->select('quiz_attempt_questions.*')
                ->orderBy('quiz_attempt_pages.display_sequence')
                ->orderBy('quiz_attempt_questions.display_sequence')
                ->get();

            if ($attempt_questions->isEmpty()) {
                throw ValidationException::withMessages([
                    'attempt' => '採点対象の問題がありません。',
                ]);
            }

            $has_manual_pending = false;

            foreach ($attempt_questions as $attempt_question) {
                $answer = $attempt_question->answer;

                if (empty($answer)) {
                    $answer = QuizzesAnswers::create([
                        'quiz_attempt_id' => $attempt->id,
                        'quiz_attempt_question_id' => $attempt_question->id,
                        'answer_data' => null,
                        'current_score' => null,
                        'correctness' => 'unanswered',
                        'grading_status' => 'ungraded',
                        'answered_at' => null,
                    ]);
                }

                $revision = $attempt_question->question_revision;

                if ($revision->question_type === 'essay') {
                    $answer->current_score = null;
                    $answer->correctness = $answer->answered_at
                        ? 'not_applicable'
                        : 'unanswered';
                    $answer->grading_status = 'manual_pending';
                    $answer->save();

                    $attempt_question->scoring_status = 'manual_pending';
                    $attempt_question->save();

                    $has_manual_pending = true;
                    continue;
                }

                $grade = $this->scoreAutomatically($attempt_question, $answer);
                $this->storeGrade(
                    $answer,
                    $grade['score'],
                    $grade['correctness'],
                    'automatic',
                    $grade['reason'],
                    null
                );

                $answer->current_score = $grade['score'];
                $answer->correctness = $grade['correctness'];
                $answer->grading_status = 'graded';
                $answer->save();

                $attempt_question->scoring_status = 'scored';
                $attempt_question->save();
            }

            $attempt->submitted_at = now();
            $attempt->elapsed_seconds = max(
                0,
                $attempt->started_at
                    ? $attempt->started_at->diffInSeconds($attempt->submitted_at)
                    : 0
            );

            $this->recalculateAttempt($attempt, $has_manual_pending);

            return $attempt->fresh([
                'quiz',
                'attempt_pages.attempt_questions.question_revision',
                'attempt_pages.attempt_questions.answer.current_grade',
            ]);
        });
    }

    /**
     * 手動採点後にも利用するAttempt再集計処理です。
     */
    public function recalculateAttempt(
        QuizzesAttempts $attempt,
        $force_manual_pending = null
    ) {
        $answers = QuizzesAnswers::where('quiz_attempt_id', $attempt->id)->get();

        $total_score = (float)$answers
            ->whereNotNull('current_score')
            ->sum('current_score');

        $manual_pending = is_null($force_manual_pending)
            ? $answers->contains(function ($answer) {
                return $answer->grading_status === 'manual_pending';
            })
            : (bool)$force_manual_pending;

        $attempt->total_score = $total_score;

        $max_score = (float)$attempt->effective_max_score;
        $attempt->score_rate = $max_score > 0
            ? round(($total_score / $max_score) * 100, 2)
            : null;

        if ($manual_pending) {
            $attempt->status = 'submitted';
            $attempt->grading_status = 'manual_pending';
            $attempt->pass_status = 'pending';
            $attempt->graded_at = null;
        } else {
            $attempt->status = 'graded';
            $attempt->grading_status = 'graded';
            $attempt->pass_status = $this->judgePassStatus($attempt);
            $attempt->graded_at = now();
        }

        $attempt->save();

        return $attempt;
    }

    private function scoreAutomatically(
        QuizzesAttemptQuestions $attempt_question,
        QuizzesAnswers $answer
    ) {
        $revision = $attempt_question->question_revision;
        $answer_data = $answer->answer_data ?: [];

        if (empty($answer->answered_at) || empty($answer_data)) {
            return [
                'score' => 0,
                'correctness' => 'incorrect',
                'reason' => '未回答です。',
            ];
        }

        switch ($revision->question_type) {
            case 'single_choice':
                return $this->scoreSingleChoice(
                    $attempt_question,
                    $answer_data
                );

            case 'multiple_choice':
                return $this->scoreMultipleChoice(
                    $attempt_question,
                    $answer_data
                );

            case 'word':
                return $this->scoreWord($revision, $answer_data);

            case 'multiple_word':
                return $this->scoreMultipleWord($revision, $answer_data);

            default:
                return [
                    'score' => 0,
                    'correctness' => 'not_applicable',
                    'reason' => '自動採点対象外の問題形式です。',
                ];
        }
    }

    private function scoreSingleChoice(
        QuizzesAttemptQuestions $attempt_question,
        array $answer_data
    ) {
        $selected = $this->extractChoiceIds($attempt_question, $answer_data);
        $correct = $attempt_question->question_revision->choices
            ->where('is_correct', true)
            ->pluck('id')
            ->map(function ($id) {
                return (int)$id;
            })
            ->values()
            ->all();

        sort($selected);
        sort($correct);

        $is_correct = $selected === $correct;

        return [
            'score' => $is_correct ? (float)$attempt_question->points : 0,
            'correctness' => $is_correct ? 'correct' : 'incorrect',
            'reason' => $is_correct ? '正解です。' : '選択した答えが正解と一致しません。',
        ];
    }

    private function scoreMultipleChoice(
        QuizzesAttemptQuestions $attempt_question,
        array $answer_data
    ) {
        $selected = $this->extractChoiceIds($attempt_question, $answer_data);
        $correct = $attempt_question->question_revision->choices
            ->where('is_correct', true)
            ->pluck('id')
            ->map(function ($id) {
                return (int)$id;
            })
            ->values()
            ->all();

        sort($selected);
        sort($correct);

        $is_correct = $selected === $correct;

        return [
            'score' => $is_correct ? (float)$attempt_question->points : 0,
            'correctness' => $is_correct ? 'correct' : 'incorrect',
            'reason' => $is_correct
                ? 'すべての選択が正解です。'
                : '選択した組み合わせが正解と一致しません。',
        ];
    }

    private function scoreWord($revision, array $answer_data)
    {
        $input = $this->extractText($answer_data);
        $normalized_input = $this->normalizeText(
            $input,
            $revision->normalization_options
        );

        $accepted = $revision->correct_answers
            ->pluck('answer_text')
            ->map(function ($text) use ($revision) {
                return $this->normalizeText(
                    $text,
                    $revision->normalization_options
                );
            })
            ->all();

        $is_correct = in_array($normalized_input, $accepted, true);

        return [
            'score' => $is_correct ? (float)$revision->points : 0,
            'correctness' => $is_correct ? 'correct' : 'incorrect',
            'reason' => $is_correct ? '正解です。' : '入力内容が正解候補と一致しません。',
        ];
    }

    private function scoreMultipleWord($revision, array $answer_data)
    {
        $texts = $this->extractTexts($answer_data);
        $groups = $revision->correct_answers
            ->groupBy('answer_group')
            ->sortKeys();

        if ($groups->isEmpty()) {
            return [
                'score' => 0,
                'correctness' => 'not_applicable',
                'reason' => '正解候補が設定されていません。',
            ];
        }

        $total = $groups->count();
        $normalized_groups = $groups->values()->map(function ($answers) use ($revision) {
            return $answers
                ->pluck('answer_text')
                ->map(function ($text) use ($revision) {
                    return $this->normalizeText(
                        $text,
                        $revision->normalization_options
                    );
                })
                ->unique()
                ->values()
                ->all();
        })->all();

        $normalized_inputs = array_map(function ($text) use ($revision) {
            return $this->normalizeText(
                $text,
                $revision->normalization_options
            );
        }, $texts);

        $matched = $revision->answer_order_fixed
            ? $this->countOrderedMultipleWordMatches(
                $normalized_inputs,
                $normalized_groups
            )
            : $this->countUnorderedMultipleWordMatches(
                $normalized_inputs,
                $normalized_groups
            );

        $score = round(
            ((float)$revision->points * $matched) / $total,
            2
        );

        if ($matched === $total) {
            $correctness = 'correct';
        } elseif ($matched > 0) {
            $correctness = 'partial';
        } else {
            $correctness = 'incorrect';
        }

        return [
            'score' => $score,
            'correctness' => $correctness,
            'reason' => $matched . '件／' . $total . '件が正解です。'
                . ($revision->answer_order_fixed ? '' : '（順不同）'),
        ];
    }

    private function countOrderedMultipleWordMatches(
        array $inputs,
        array $groups
    ) {
        $matched = 0;

        foreach ($groups as $index => $accepted) {
            $input = $inputs[$index] ?? '';

            if ($input !== '' && in_array($input, $accepted, true)) {
                $matched++;
            }
        }

        return $matched;
    }

    /**
     * 入力と正解グループの一対一対応について最大一致数を求めます。
     * 同じ正解グループを複数の入力へ重複使用しません。
     */
    private function countUnorderedMultipleWordMatches(
        array $inputs,
        array $groups
    ) {
        $candidates = [];

        foreach ($inputs as $input_index => $input) {
            if ($input === '') {
                $candidates[$input_index] = [];
                continue;
            }

            $candidates[$input_index] = [];
            foreach ($groups as $group_index => $accepted) {
                if (in_array($input, $accepted, true)) {
                    $candidates[$input_index][] = $group_index;
                }
            }
        }

        $group_matches = [];
        $matched = 0;

        foreach (array_keys($candidates) as $input_index) {
            $visited_groups = [];
            if ($this->assignMultipleWordMatch(
                $input_index,
                $candidates,
                $group_matches,
                $visited_groups
            )) {
                $matched++;
            }
        }

        return $matched;
    }

    private function assignMultipleWordMatch(
        $input_index,
        array $candidates,
        array &$group_matches,
        array &$visited_groups
    ) {
        foreach ($candidates[$input_index] ?? [] as $group_index) {
            if (!empty($visited_groups[$group_index])) {
                continue;
            }

            $visited_groups[$group_index] = true;

            if (
                !array_key_exists($group_index, $group_matches)
                || $this->assignMultipleWordMatch(
                    $group_matches[$group_index],
                    $candidates,
                    $group_matches,
                    $visited_groups
                )
            ) {
                $group_matches[$group_index] = $input_index;
                return true;
            }
        }

        return false;
    }

    /**
     * 画面がchoice_revision_idまたはattempt_choice_idの
     * どちらを送っても採点できるように変換します。
     */
    private function extractChoiceIds(
        QuizzesAttemptQuestions $attempt_question,
        array $answer_data
    ) {
        $values = [];

        if (isset($answer_data['attempt_choice_ids'])) {
            $attempt_choice_ids = array_map(
                'intval',
                (array)$answer_data['attempt_choice_ids']
            );

            $values = $attempt_question->choices
                ->whereIn('id', $attempt_choice_ids)
                ->pluck('choice_revision_id')
                ->all();
        } elseif (isset($answer_data['choice_ids'])) {
            // 旧画面は受験時選択肢IDをchoice_idsという名前で保存していた。
            // 全件が受験時選択肢IDに一致する場合はRevision IDへ変換する。
            $legacy_values = array_map(
                'intval',
                (array)$answer_data['choice_ids']
            );
            $attempt_choice_ids = $attempt_question->choices
                ->pluck('id')
                ->map(function ($id) {
                    return (int)$id;
                })
                ->all();

            if (empty(array_diff($legacy_values, $attempt_choice_ids))) {
                $values = $attempt_question->choices
                    ->whereIn('id', $legacy_values)
                    ->pluck('choice_revision_id')
                    ->all();
            } else {
                // さらに古い保存データがRevision IDを保持している場合の互換処理。
                $values = $legacy_values;
            }
        } elseif (isset($answer_data['choice_id'])) {
            $values = [$answer_data['choice_id']];
        }

        return array_values(array_unique(array_map('intval', $values)));
    }

    private function extractText(array $answer_data)
    {
        if (array_key_exists('text', $answer_data)) {
            return (string)$answer_data['text'];
        }

        if (array_key_exists('answer', $answer_data)) {
            return (string)$answer_data['answer'];
        }

        return '';
    }

    private function extractTexts(array $answer_data)
    {
        if (isset($answer_data['texts'])) {
            return array_values((array)$answer_data['texts']);
        }

        if (isset($answer_data['answers'])) {
            return array_values((array)$answer_data['answers']);
        }

        return [];
    }

    private function normalizeText($text, $options)
    {
        $options = is_array($options) ? $options : [];
        $text = (string)$text;

        if (!array_key_exists('trim', $options) || !empty($options['trim'])) {
            $text = trim($text);
        }

        if (!empty($options['normalize_width']) && function_exists('mb_convert_kana')) {
            $text = mb_convert_kana($text, 'asKV', 'UTF-8');
        }

        if (!empty($options['collapse_spaces'])) {
            $text = preg_replace('/[\s　]+/u', ' ', $text);
        }

        if (!empty($options['ignore_spaces'])) {
            $text = preg_replace('/[\s　]+/u', '', $text);
        }

        if (!empty($options['ignore_case']) && function_exists('mb_strtolower')) {
            $text = mb_strtolower($text, 'UTF-8');
        }

        return $text;
    }

    private function storeGrade(
        QuizzesAnswers $answer,
        $score,
        $correctness,
        $grading_type,
        $reason = null,
        $grader_id = null
    ) {
        QuizzesAnswerGrades::where('quiz_answer_id', $answer->id)
            ->where('is_current', true)
            ->update(['is_current' => false]);

        return QuizzesAnswerGrades::create([
            'quiz_answer_id' => $answer->id,
            'score' => $score,
            'correctness' => $correctness,
            'grading_type' => $grading_type,
            'reason' => $reason,
            'comment' => null,
            'internal_comment' => null,
            'graded_by' => $grader_id,
            'graded_at' => now(),
            'is_current' => true,
        ]);
    }

    private function judgePassStatus(QuizzesAttempts $attempt)
    {
        switch ($attempt->passing_type_snapshot) {
            case 'score':
                return (float)$attempt->total_score
                    >= (float)$attempt->passing_score_snapshot
                        ? 'passed'
                        : 'failed';

            case 'rate':
                return !is_null($attempt->score_rate)
                    && (float)$attempt->score_rate
                        >= (float)$attempt->passing_rate_snapshot
                        ? 'passed'
                        : 'failed';

            case 'none':
            default:
                return 'not_applicable';
        }
    }
}
