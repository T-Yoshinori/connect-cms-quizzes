<?php

namespace App\Plugins\User\Quizzes\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

use App\Models\User\Quizzes\Quizzes;
use App\Models\User\Quizzes\QuizzesCategoryGroups;
use App\Models\User\Quizzes\QuizzesCategories;

class QuizCategoryService
{
    public function save(int $quiz_id, array $groups): void
    {
        DB::transaction(function () use ($quiz_id, $groups) {
            $quiz = Quizzes::lockForUpdate()->findOrFail($quiz_id);

            if (!$quiz->use_category_scoring) {
                throw ValidationException::withMessages([
                    'groups' => 'カテゴリー別採点が「使用しない」のため保存できません。',
                ]);
            }

            foreach (array_values($groups) as $group_index => $group_data) {
                $group_id = !empty($group_data['id'])
                    ? (int) $group_data['id']
                    : null;

                if ($group_id) {
                    $group = QuizzesCategoryGroups::query()
                        ->where('quiz_id', $quiz->id)
                        ->lockForUpdate()
                        ->find($group_id);

                    if (!$group) {
                        throw ValidationException::withMessages([
                            "groups.{$group_index}.id" =>
                                '他の小テストのカテゴリーグループは変更できません。',
                        ]);
                    }
                } else {
                    $group = new QuizzesCategoryGroups();
                    $group->quiz_id = $quiz->id;
                }

                $group->name = trim((string) $group_data['name']);
                $group->sequence = max(1, (int) $group_data['sequence']);
                $group->is_active = !empty($group_data['is_active']);
                $group->save();

                foreach (array_values($group_data['categories'] ?? []) as $category_index => $category_data) {
                    $category_id = !empty($category_data['id'])
                        ? (int) $category_data['id']
                        : null;

                    if ($category_id) {
                        $category = QuizzesCategories::query()
                            ->where('quiz_category_group_id', $group->id)
                            ->lockForUpdate()
                            ->find($category_id);

                        if (!$category) {
                            throw ValidationException::withMessages([
                                "groups.{$group_index}.categories.{$category_index}.id" =>
                                    '他のグループのカテゴリー項目は変更できません。',
                            ]);
                        }
                    } else {
                        $category = new QuizzesCategories();
                        $category->quiz_category_group_id = $group->id;
                    }

                    $category->name = trim((string) $category_data['name']);
                    $category->sequence = max(1, (int) $category_data['sequence']);
                    $category->is_active = !empty($category_data['is_active']);
                    $category->save();
                }
            }
        });
    }

    public function unassignedQuestionCount(int $quiz_id): int
    {
        return (int) DB::table('quiz_questions')
            ->join('quiz_pages', 'quiz_pages.id', '=', 'quiz_questions.quiz_page_id')
            ->leftJoin(
                'quiz_question_revision_categories',
                'quiz_question_revision_categories.question_revision_id',
                '=',
                'quiz_questions.current_revision_id'
            )
            ->where('quiz_pages.quiz_id', $quiz_id)
            ->whereNull('quiz_pages.deleted_at')
            ->whereNull('quiz_questions.deleted_at')
            ->where('quiz_questions.status', 'active')
            ->whereNull('quiz_question_revision_categories.id')
            ->distinct()
            ->count('quiz_questions.id');
    }
}
