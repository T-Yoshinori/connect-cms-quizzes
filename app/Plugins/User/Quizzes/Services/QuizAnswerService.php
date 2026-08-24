<?php

namespace App\Plugins\User\Quizzes\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

use App\Models\User\Quizzes\QuizzesAttempts;
use App\Models\User\Quizzes\QuizzesAttemptQuestions;
use App\Models\User\Quizzes\QuizzesAnswers;

/**
 * 受験中の回答を保存します。
 */
class QuizAnswerService
{
    public function saveAnswer(
        $attempt_id,
        $attempt_question_id,
        $answer_data,
        $user_id
    ) {
        return DB::transaction(function () use (
            $attempt_id,
            $attempt_question_id,
            $answer_data,
            $user_id
        ) {
            $attempt = QuizzesAttempts::lockForUpdate()
                ->whereKey($attempt_id)
                ->where('user_id', $user_id)
                ->firstOrFail();

            if ($attempt->status !== 'in_progress') {
                throw ValidationException::withMessages([
                    'attempt' => '提出済みの小テストは変更できません。',
                ]);
            }

            if ($attempt->expires_at && now()->greaterThan($attempt->expires_at)) {
                throw ValidationException::withMessages([
                    'attempt' => '制限時間を過ぎているため、回答を保存できません。',
                ]);
            }

            $attempt_question = QuizzesAttemptQuestions::query()
                ->join(
                    'quiz_attempt_pages',
                    'quiz_attempt_pages.id',
                    '=',
                    'quiz_attempt_questions.quiz_attempt_page_id'
                )
                ->where('quiz_attempt_questions.id', $attempt_question_id)
                ->where('quiz_attempt_pages.quiz_attempt_id', $attempt->id)
                ->select('quiz_attempt_questions.*')
                ->firstOrFail();

            $normalized = $this->normalizeAnswerData($answer_data);
            $is_empty = $this->isEmptyAnswer($normalized);

            $answer = QuizzesAnswers::updateOrCreate(
                [
                    'quiz_attempt_id' => $attempt->id,
                    'quiz_attempt_question_id' => $attempt_question->id,
                ],
                [
                    'answer_data' => $is_empty ? null : $normalized,
                    'current_score' => null,
                    'correctness' => $is_empty ? 'unanswered' : 'answered',
                    'grading_status' => 'ungraded',
                    'answered_at' => $is_empty ? null : now(),
                ]
            );

            return $answer->fresh();
        });
    }

    private function normalizeAnswerData($answer_data)
    {
        if (is_array($answer_data)) {
            return $answer_data;
        }

        if (is_null($answer_data) || $answer_data === '') {
            return null;
        }

        return ['text' => (string)$answer_data];
    }

    private function isEmptyAnswer($answer_data)
    {
        if (is_null($answer_data)) {
            return true;
        }

        if (!is_array($answer_data)) {
            return trim((string)$answer_data) === '';
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveArrayIterator($answer_data)
        );

        foreach ($iterator as $value) {
            if (!is_null($value) && trim((string)$value) !== '') {
                return false;
            }
        }

        return true;
    }
}
