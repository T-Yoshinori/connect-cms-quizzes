<?php

namespace App\Plugins\User\Quizzes\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

use App\Models\User\Quizzes\QuizzesQuestions;
use App\Models\User\Quizzes\QuizzesAnswers;
use App\Models\User\Quizzes\QuizzesAnswerGrades;
use App\Models\User\Quizzes\QuizzesAttempts;

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
        QuizzesQuestions::withTrashed()->findOrFail($question_id);

        return QuizzesAnswers::with([
            'attempt.user',
            'attempt_question.question_revision',
            'attempt_question.quiz_question',
            'current_grade',
        ])
            ->join(
                'quiz_attempt_questions',
                'quiz_attempt_questions.id',
                '=',
                'quiz_answers.quiz_attempt_question_id'
            )
            ->join(
                'quiz_attempt_pages',
                'quiz_attempt_pages.id',
                '=',
                'quiz_attempt_questions.quiz_attempt_page_id'
            )
            ->join(
                'quiz_attempts',
                'quiz_attempts.id',
                '=',
                'quiz_answers.quiz_attempt_id'
            )
            ->where('quiz_attempt_questions.quiz_question_id', $question_id)
            ->where('quiz_answers.grading_status', 'manual_pending')
            ->where('quiz_attempts.is_preview', false)
            ->select('quiz_answers.*')
            ->orderBy('quiz_attempts.submitted_at')
            ->orderBy('quiz_answers.id')
            ->paginate(30);
    }

    /**
     * 回答を手動採点し、採点履歴とAttempt集計を更新します。
     */
    public function gradeAnswer($answer_id, array $data, $grader_id = null)
    {
        return DB::transaction(function () use ($answer_id, $data, $grader_id) {
            $answer = QuizzesAnswers::with([
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

            QuizzesAnswerGrades::where('quiz_answer_id', $answer->id)
                ->where('is_current', true)
                ->update(['is_current' => false]);

            $grade = QuizzesAnswerGrades::create([
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

            $attempt = QuizzesAttempts::lockForUpdate()
                ->findOrFail($answer->quiz_attempt_id);

            $this->submission_service->recalculateAttempt($attempt);

            return $grade->fresh([
                'answer.attempt',
                'grader',
            ]);
        });
    }
}
