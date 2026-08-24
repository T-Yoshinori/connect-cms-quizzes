<?php

namespace App\Plugins\User\Quizzes\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

use App\Models\User\Quizzes\QuizzesPages;
use App\Models\User\Quizzes\QuizzesQuestions;
use App\Models\User\Quizzes\QuizzesQuestionRevisions;
use App\Models\User\Quizzes\QuizzesChoiceRevisions;
use App\Models\User\Quizzes\QuizzesCorrectAnswerRevisions;
use App\Models\User\Quizzes\QuizzesCategories;
use App\Models\User\Quizzes\QuizzesAnswers;

/**
 * 問題・Revision・選択肢・正解候補を保存します。
 */
class QuizQuestionService
{
    private $quiz_service;

    public function __construct(QuizService $quiz_service)
    {
        $this->quiz_service = $quiz_service;
    }

    /**
     * 問題を保存します。
     *
     * 回答が存在しない場合:
     *   現行Revisionを更新
     *
     * 回答が存在する場合:
     *   新しいRevisionを作成し、current_revision_idを切り替え
     */
    public function saveQuestion(array $data, $question_id = null, $user_id = null)
    {
        return DB::transaction(function () use ($data, $question_id, $user_id) {
            $page = QuizzesPages::lockForUpdate()->findOrFail($data['quiz_page_id']);

            if ((int)$page->quiz_id !== (int)$data['quiz_id']) {
                throw ValidationException::withMessages([
                    'quiz_page_id' => '指定された問題ページは、この小テストに属していません。',
                ]);
            }

            if ($question_id) {
                $question = QuizzesQuestions::withTrashed()
                    ->lockForUpdate()
                    ->findOrFail($question_id);

                if ((int)$question->quiz_page_id !== (int)$page->id) {
                    $question->quiz_page_id = $page->id;
                }

                if ($question->trashed()) {
                    $question->restore();
                }
            } else {
                $question = new QuizzesQuestions();
                $question->quiz_page_id = $page->id;
                $question->status = 'active';
            }

            // 問題編集では表示順を一切変更しません。
            // 表示順の採番は、新規作成時だけ行います。
            if (empty($question_id)) {
                $question->sequence = $this->nextQuestionSequence($page->id);
            }

            $question->save();

            $current_revision = $question->current_revision_id
                ? QuizzesQuestionRevisions::lockForUpdate()->find($question->current_revision_id)
                : null;

            $source_category_ids = $current_revision
                ? $current_revision->categories()
                    ->pluck('quiz_categories.id')
                    ->map(function ($id) {
                        return (int) $id;
                    })
                    ->all()
                : [];

            $has_answers = $this->questionHasAnswers($question->id);

            if (empty($current_revision) || $has_answers) {
                $revision = new QuizzesQuestionRevisions();
                $revision->quiz_question_id = $question->id;
                $revision->revision_no = $this->nextRevisionNo($question->id);
                $revision->created_id = $user_id;
            } else {
                $revision = $current_revision;
            }

            $revision->fill($this->makeRevisionAttributes($data));
            $revision->save();

            $this->replaceChoices($revision, $data);
            $this->replaceCorrectAnswers($revision, $data);
            $this->validateQuestionDefinition($revision);
            $this->replaceCategories(
                $revision,
                $page->quiz_id,
                $data,
                $source_category_ids
            );

            $question->current_revision_id = $revision->id;
            $question->save();

            $this->quiz_service->recalculatePerfectScore($page->quiz_id);

            return $question->fresh([
                'quiz_page',
                'current_revision.choices',
                'current_revision.correct_answers',
            ]);
        });
    }

    /**
     * 問題を論理削除し、小テストIDを返します。
     */
    public function deleteQuestion($question_id)
    {
        return DB::transaction(function () use ($question_id) {
            $question = QuizzesQuestions::with('quiz_page')
                ->lockForUpdate()
                ->findOrFail($question_id);

            $quiz_id = $question->quiz_page->quiz_id;
            $question->delete();

            $this->quiz_service->recalculatePerfectScore($quiz_id);

            return $quiz_id;
        });
    }

    private function questionHasAnswers($question_id)
    {
        return QuizzesAnswers::query()
            ->join(
                'quiz_attempt_questions',
                'quiz_attempt_questions.id',
                '=',
                'quiz_answers.quiz_attempt_question_id'
            )
            ->where('quiz_attempt_questions.quiz_question_id', $question_id)
            ->exists();
    }

    private function nextRevisionNo($question_id)
    {
        return (int)QuizzesQuestionRevisions::where(
            'quiz_question_id',
            $question_id
        )->max('revision_no') + 1;
    }

    private function nextQuestionSequence($page_id, $question_id = null)
    {
        $query = QuizzesQuestions::withTrashed()->where('quiz_page_id', $page_id);

        if ($question_id) {
            $query->where('id', '<>', $question_id);
        }

        return (int)$query->max('sequence') + 1;
    }

    private function makeRevisionAttributes(array $data)
    {
        return [
            'question_type' => $data['question_type'],
            'question_text' => $data['question_text'],
            'points' => $data['points'],
            'commentary' => $data['commentary'] ?? null,
            'model_answer' => $data['model_answer'] ?? null,
            'grading_guide' => $data['grading_guide'] ?? null,
            'choice_random' => !empty($data['choice_random']),
            'answer_order_fixed' => array_key_exists('answer_order_fixed', $data)
                ? !empty($data['answer_order_fixed'])
                : true,
            'normalization_options' => $data['normalization_options'] ?? null,
            'answer_rows' => $data['answer_rows'] ?? null,
            'character_limit' => $data['character_limit'] ?? null,
        ];
    }

    private function replaceChoices(
        QuizzesQuestionRevisions $revision,
        array $data
    ) {
        $revision->choices()->delete();

        if (!in_array($revision->question_type, [
            'single_choice',
            'multiple_choice',
        ], true)) {
            return;
        }

        $choices = $data['choices'] ?? [];

        foreach (array_values($choices) as $index => $choice) {
            $label = trim((string)($choice['label'] ?? $choice['text'] ?? ''));

            if ($label === '') {
                continue;
            }

            QuizzesChoiceRevisions::create([
                'question_revision_id' => $revision->id,
                'label' => $label,
                'sequence' => $index + 1,
                'is_correct' => !empty($choice['is_correct']),
            ]);
        }
    }

    private function replaceCorrectAnswers(
        QuizzesQuestionRevisions $revision,
        array $data
    ) {
        $revision->correct_answers()->delete();

        if (!in_array($revision->question_type, [
            'word',
            'multiple_word',
        ], true)) {
            return;
        }

        $answers = $data['correct_answers'] ?? [];

        foreach (array_values($answers) as $index => $answer) {
            if (is_string($answer) || is_numeric($answer)) {
                $answer_text = trim((string)$answer);
                $answer_group = 1;
                $sequence = $index + 1;
            } else {
                $answer_text = trim((string)($answer['answer_text'] ?? $answer['text'] ?? ''));
                $answer_group = max(1, (int)($answer['answer_group'] ?? $answer['group'] ?? 1));
                $sequence = max(1, (int)($answer['sequence'] ?? $index + 1));
            }

            if ($answer_text === '') {
                continue;
            }

            QuizzesCorrectAnswerRevisions::create([
                'question_revision_id' => $revision->id,
                'answer_group' => $answer_group,
                'answer_text' => $answer_text,
                'sequence' => $sequence,
            ]);
        }
    }

    private function replaceCategories(
        QuizzesQuestionRevisions $revision,
        int $quiz_id,
        array $data,
        array $source_category_ids
    ): void {
        if (empty($data['category_assignment_present'])) {
            if ($revision->wasRecentlyCreated && !empty($source_category_ids)) {
                $revision->categories()->sync($source_category_ids);
            }
            return;
        }

        $category_ids = array_values(array_unique(array_map(
            'intval',
            $data['category_ids'] ?? []
        )));

        if (empty($category_ids)) {
            $revision->categories()->sync([]);
            return;
        }

        $valid_ids = QuizzesCategories::query()
            ->join(
                'quiz_category_groups',
                'quiz_category_groups.id',
                '=',
                'quiz_categories.quiz_category_group_id'
            )
            ->where('quiz_category_groups.quiz_id', $quiz_id)
            ->where('quiz_category_groups.is_active', true)
            ->where('quiz_categories.is_active', true)
            ->whereIn('quiz_categories.id', $category_ids)
            ->pluck('quiz_categories.id')
            ->map(function ($id) {
                return (int) $id;
            })
            ->all();

        sort($category_ids);
        sort($valid_ids);

        if ($category_ids !== $valid_ids) {
            throw ValidationException::withMessages([
                'category_ids' =>
                    '指定されたカテゴリーに、この小テストで使用できない項目が含まれています。',
            ]);
        }

        $revision->categories()->sync($category_ids);
    }

    private function validateQuestionDefinition(
        QuizzesQuestionRevisions $revision
    ) {
        if (in_array($revision->question_type, [
            'single_choice',
            'multiple_choice',
        ], true)) {
            $choice_count = $revision->choices()->count();
            $correct_count = $revision->choices()->where('is_correct', true)->count();

            if ($choice_count < 2) {
                throw ValidationException::withMessages([
                    'choices' => '選択式問題には、選択肢を2件以上登録してください。',
                ]);
            }

            if ($revision->question_type === 'single_choice' && $correct_count !== 1) {
                throw ValidationException::withMessages([
                    'choices' => '単一選択問題では、正解を1件だけ指定してください。',
                ]);
            }

            if ($revision->question_type === 'multiple_choice' && $correct_count < 1) {
                throw ValidationException::withMessages([
                    'choices' => '複数選択問題では、正解を1件以上指定してください。',
                ]);
            }
        }

        if (in_array($revision->question_type, [
            'word',
            'multiple_word',
        ], true) && !$revision->correct_answers()->exists()) {
            throw ValidationException::withMessages([
                'correct_answers' => '正解候補を1件以上登録してください。',
            ]);
        }
    }
}
