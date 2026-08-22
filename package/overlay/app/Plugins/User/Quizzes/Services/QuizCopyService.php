<?php

namespace App\Plugins\User\Quizzes\Services;

use Illuminate\Support\Facades\DB;

use App\Models\User\Quizzes\Quizzes;
use App\Models\User\Quizzes\QuizzesFrames;
use App\Models\User\Quizzes\QuizzesQuestionRevisionCategories;

/**
 * 小テスト一式の複製を担当します。
 *
 * 受験履歴・回答・採点結果は複製しません。
 */
class QuizCopyService
{
    /**
     * 小テストの設定・カテゴリー・ページ・問題・全Revisionを複製し、
     * 現在のフレームだけを複製先へ割り当てます。
     */
    public function copyForFrame(int $quiz_id, int $frame_id, ?int $user_id = null): Quizzes
    {
        return DB::transaction(function () use ($quiz_id, $frame_id, $user_id) {
            $source = Quizzes::query()
                ->with([
                    'category_groups.categories',
                    'pages.questions.revisions.choices',
                    'pages.questions.revisions.correct_answers',
                    'pages.questions.revisions.category_assignments',
                ])
                ->lockForUpdate()
                ->findOrFail($quiz_id);

            $copy = $source->replicate([
                'id',
                'created_at',
                'updated_at',
                'deleted_at',
            ]);
            $copy->title = $source->title . '（コピー）';
            $copy->created_id = $user_id;
            $copy->updated_id = $user_id;
            $copy->save();

            // 問題への割当が複製元のカテゴリーを参照しないよう、新旧IDを対応付ける。
            $category_id_map = [];

            foreach ($source->category_groups as $category_group) {
                $category_group_copy = $category_group->replicate([
                    'id',
                    'quiz_id',
                    'created_at',
                    'updated_at',
                ]);
                $category_group_copy->quiz_id = $copy->id;
                $category_group_copy->save();

                foreach ($category_group->categories as $category) {
                    $category_copy = $category->replicate([
                        'id',
                        'quiz_category_group_id',
                        'created_at',
                        'updated_at',
                    ]);
                    $category_copy->quiz_category_group_id = $category_group_copy->id;
                    $category_copy->save();

                    $category_id_map[$category->id] = $category_copy->id;
                }
            }

            foreach ($source->pages as $page) {
                $page_copy = $page->replicate([
                    'id',
                    'quiz_id',
                    'created_at',
                    'updated_at',
                    'deleted_at',
                ]);
                $page_copy->quiz_id = $copy->id;
                $page_copy->save();

                foreach ($page->questions as $question) {
                    $question_copy = $question->replicate([
                        'id',
                        'quiz_page_id',
                        'current_revision_id',
                        'created_at',
                        'updated_at',
                        'deleted_at',
                    ]);
                    $question_copy->quiz_page_id = $page_copy->id;
                    $question_copy->current_revision_id = null;
                    $question_copy->save();

                    $revision_id_map = [];

                    foreach ($question->revisions as $revision) {
                        $revision_copy = $revision->replicate([
                            'id',
                            'quiz_question_id',
                            'created_at',
                            'updated_at',
                        ]);
                        $revision_copy->quiz_question_id = $question_copy->id;
                        $revision_copy->created_id = $user_id;
                        $revision_copy->save();

                        $revision_id_map[$revision->id] = $revision_copy->id;

                        foreach ($revision->choices as $choice) {
                            $choice_copy = $choice->replicate([
                                'id',
                                'question_revision_id',
                                'created_at',
                                'updated_at',
                            ]);
                            $choice_copy->question_revision_id = $revision_copy->id;
                            $choice_copy->save();
                        }

                        foreach ($revision->correct_answers as $correct_answer) {
                            $correct_answer_copy = $correct_answer->replicate([
                                'id',
                                'question_revision_id',
                                'created_at',
                                'updated_at',
                            ]);
                            $correct_answer_copy->question_revision_id = $revision_copy->id;
                            $correct_answer_copy->save();
                        }

                        foreach ($revision->category_assignments as $category_assignment) {
                            $category_id = $category_id_map[$category_assignment->quiz_category_id] ?? null;

                            if ($category_id === null) {
                                continue;
                            }

                            QuizzesQuestionRevisionCategories::create([
                                'question_revision_id' => $revision_copy->id,
                                'quiz_category_id' => $category_id,
                            ]);
                        }
                    }

                    if (!empty($question->current_revision_id)) {
                        $question_copy->current_revision_id =
                            $revision_id_map[$question->current_revision_id] ?? null;
                        $question_copy->save();
                    }
                }
            }

            // 同一フレームの古い・重複した割当を残さず、複製先1件だけにする。
            QuizzesFrames::where('frame_id', $frame_id)->delete();
            QuizzesFrames::create([
                'frame_id' => $frame_id,
                'quiz_id' => $copy->id,
            ]);

            return $copy->fresh();
        });
    }
}
