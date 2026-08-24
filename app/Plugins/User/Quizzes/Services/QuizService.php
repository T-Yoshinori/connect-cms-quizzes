<?php

namespace App\Plugins\User\Quizzes\Services;

use Illuminate\Support\Facades\DB;

use App\Models\User\Quizzes\Quizzes;
use App\Models\User\Quizzes\QuizzesFrames;
use App\Models\User\Quizzes\QuizzesPages;
use App\Models\User\Quizzes\QuizzesQuestions;

/**
 * 小テスト本体の作成・更新・削除を担当します。
 */
class QuizService
{
    /**
     * 小テスト設定を保存します。
     *
     * 新規作成時には、最初の問題ページを1件作成し、
     * 指定フレームへ小テストを割り当てます。
     */
    public function saveQuiz(array $data, $frame_id, $quiz_id = null, $user_id = null)
    {
        return DB::transaction(function () use ($data, $frame_id, $quiz_id, $user_id) {
            $quiz = $quiz_id
                ? Quizzes::lockForUpdate()->findOrFail($quiz_id)
                : new Quizzes();

            $quiz->fill($this->makeQuizAttributes($data));

            if (!$quiz->exists) {
                $quiz->created_id = $user_id;
            }
            $quiz->updated_id = $user_id;
            $quiz->save();

            QuizzesFrames::where('frame_id', (int) $frame_id)->delete();
            QuizzesFrames::create([
                'frame_id' => (int) $frame_id,
                'quiz_id' => $quiz->id,
            ]);

            if (!$quiz->pages()->exists()) {
                QuizzesPages::create([
                    'quiz_id' => $quiz->id,
                    'title' => '問題ページ 1',
                    'description' => null,
                    'sequence' => 1,
                ]);
            }

            $this->recalculatePerfectScore($quiz->id);

            return $quiz->fresh();
        });
    }

    /**
     * 小テストを論理削除します。
     *
     * 受験履歴は保持します。フレームとの割当だけ解除します。
     */
    public function deleteQuiz($quiz_id, $frame_id = null)
    {
        return DB::transaction(function () use ($quiz_id, $frame_id) {
            $quiz = Quizzes::lockForUpdate()->findOrFail($quiz_id);

            $frame_query = QuizzesFrames::where('quiz_id', $quiz->id);
            if (!empty($frame_id)) {
                $frame_query->where('frame_id', (int)$frame_id);
            }
            $frame_query->delete();

            $quiz->delete();

            return true;
        });
    }

    /**
     * 現在有効な問題Revisionの合計点を再計算します。
     */
    public function recalculatePerfectScore($quiz_id)
    {
        $score = QuizzesQuestions::query()
            ->join('quiz_pages', 'quiz_pages.id', '=', 'quiz_questions.quiz_page_id')
            ->join(
                'quiz_question_revisions',
                'quiz_question_revisions.id',
                '=',
                'quiz_questions.current_revision_id'
            )
            ->where('quiz_pages.quiz_id', $quiz_id)
            ->whereNull('quiz_pages.deleted_at')
            ->whereNull('quiz_questions.deleted_at')
            ->where('quiz_questions.status', 'active')
            ->sum('quiz_question_revisions.points');

        Quizzes::whereKey($quiz_id)->update([
            'perfect_score' => $score ?: 0,
        ]);

        return (float)$score;
    }

    private function makeQuizAttributes(array $data)
    {
        $boolean_fields = [
            'use_category_scoring',
            'show_score',
            'show_pass_status',
            'show_question_result',
            'show_correct_answer',
            'show_commentary',
            'show_grading_comment',
        ];

        foreach ($boolean_fields as $field) {
            $data[$field] = !empty($data[$field]);
        }

        if (($data['retry_type'] ?? null) !== 'limited') {
            $data['retry_limit'] = null;
        }

        if (($data['passing_type'] ?? null) !== 'score') {
            $data['passing_score'] = null;
        }

        if (($data['passing_type'] ?? null) !== 'rate') {
            $data['passing_rate'] = null;
        }

        return $data;
    }
}
