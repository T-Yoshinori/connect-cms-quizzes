<?php

namespace App\Plugins\User\Yuyuquizzes\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

use App\Models\User\YuyuQuizzes\YuyuQuizQuestion;
use App\Models\User\YuyuQuizzes\YuyuQuizAnswer;
use App\Models\User\YuyuQuizzes\YuyuQuizAnswerGrade;
use App\Models\User\YuyuQuizzes\YuyuQuizAttempt;

/**
 * 記述式回答の問題別採点を担当します。
 */
class QuizManualGradeService
{
    private $submission_service;

    public function __construct(QuizSubmissionService $submission_service)
    {
        $this->submission_service = $submission_service;
    }

    /**
     * 指定問題に対する手動採点待ち回答を取得します。
     *
     * 過去Revisionに対する回答も同じ問題として一覧に含めます。
     */
    public function getPendingAnswersByQuestion($question_id)
    {
        YuyuQuizQuestion::withTrashed()->findOrFail($question_id);

        return YuyuQuizAnswer::with([
            'attempt.user',
            'attempt_question.question_revision',
            'attempt_question.quiz_question',
            'current_grade',
        ])
            ->join(
                'yuyu_quiz_attempt_questions',
                'yuyu_quiz_attempt_questions.id',
                '=',
                'yuyu_quiz_answers.quiz_attempt_question_id'
            )
            ->join(
                'yuyu_quiz_attempt_pages',
                'yuyu_quiz_attempt_pages.id',
                '=',
                'yuyu_quiz_attempt_questions.quiz_attempt_page_id'
            )
            ->join(
                'yuyu_quiz_attempts',
                'yuyu_quiz_attempts.id',
                '=',
                'yuyu_quiz_answers.quiz_attempt_id'
            )
            ->where('yuyu_quiz_attempt_questions.quiz_question_id', $question_id)
            ->where('yuyu_quiz_answers.grading_status', 'manual_pending')
            ->where('yuyu_quiz_attempts.is_preview', false)
            ->select('yuyu_quiz_answers.*')
            ->orderBy('yuyu_quiz_attempts.submitted_at')
            ->orderBy('yuyu_quiz_answers.id')
            ->paginate(30);
    }

    /**
     * 回答を手動採点し、採点履歴とAttempt集計を更新します。
     */
    public function gradeAnswer($answer_id, array $data, $grader_id = null)
    {
        return DB::transaction(function () use ($answer_id, $data, $grader_id) {
            $answer = YuyuQuizAnswer::with([
                'attempt_question.question_revision',
            ])->lockForUpdate()->findOrFail($answer_id);

            $max_score = (float)$answer->attempt_question->points;
            $score = (float)$data['score'];

            if ($score > $max_score) {
                throw ValidationException::withMessages([
                    'score' => '得点は配点（'
                        . $max_score
                        . '点）以下で入力してください。',
                ]);
            }

            YuyuQuizAnswerGrade::where('quiz_answer_id', $answer->id)
                ->where('is_current', true)
                ->update(['is_current' => false]);

            $grade = YuyuQuizAnswerGrade::create([
                'quiz_answer_id' => $answer->id,
                'score' => $score,
                'correctness' => $data['correctness'],
                'grading_type' => 'manual',
                'reason' => $data['reason'] ?? null,
                'comment' => $data['comment'] ?? null,
                'internal_comment' => $data['internal_comment'] ?? null,
                'graded_by' => $grader_id,
                'graded_at' => now(),
                'is_current' => true,
            ]);

            $answer->current_score = $score;
            $answer->correctness = $data['correctness'];
            $answer->grading_status = 'graded';
            $answer->save();

            $answer->attempt_question->scoring_status = 'scored';
            $answer->attempt_question->save();

            $attempt = YuyuQuizAttempt::lockForUpdate()
                ->findOrFail($answer->quiz_attempt_id);

            $this->submission_service->recalculateAttempt($attempt);

            return $grade->fresh([
                'answer.attempt',
                'grader',
            ]);
        });
    }
}
