<?php

namespace App\Plugins\User\Quizzes;

use Illuminate\Support\Facades\Auth;

use App\Models\User\Quizzes\Quizzes;
use App\Models\User\Quizzes\QuizzesAttempts;

/**
 * 小テスト画面用の権限・状態判定クラス
 */
class QuizzesTool
{
    private $request;
    private $page_id;
    private $frame_id;
    private $quiz;
    private $user;

    public function __construct($request, $page_id, $frame_id, Quizzes $quiz = null)
    {
        $this->request = $request;
        $this->page_id = (int)$page_id;
        $this->frame_id = (int)$frame_id;
        $this->quiz = $quiz;
        $this->user = Auth::user();
    }

    public function getQuiz()
    {
        return $this->quiz;
    }

    public function getUserId()
    {
        return $this->user ? $this->user->id : null;
    }

    public function isLogin()
    {
        return !empty($this->user);
    }

    public function isPublished()
    {
        if (empty($this->quiz)) {
            return false;
        }

        return Quizzes::query()
            ->whereKey($this->quiz->id)
            ->published()
            ->exists();
    }

    public function latestAttempt()
    {
        if (!$this->isLogin() || empty($this->quiz)) {
            return null;
        }

        return $this->attemptsQuery()
            ->latest('attempt_no')
            ->first();
    }

    public function inProgressAttempt()
    {
        if (!$this->isLogin() || empty($this->quiz)) {
            return null;
        }

        return $this->attemptsQuery()
            ->where('status', 'in_progress')
            ->latest('attempt_no')
            ->first();
    }

    /**
     * 結果画面を再表示できる最新の完了済み受験を取得します。
     */
    public function latestResultAttempt()
    {
        if (!$this->isLogin() || empty($this->quiz)) {
            return null;
        }

        return $this->attemptsQuery()
            ->whereIn('status', ['submitted', 'graded', 'expired'])
            ->latest('attempt_no')
            ->first();
    }

    public function completedAttemptCount()
    {
        if (!$this->isLogin() || empty($this->quiz)) {
            return 0;
        }

        return $this->attemptsQuery()
            ->whereIn('status', ['submitted', 'graded', 'expired'])
            ->count();
    }

    public function canStart()
    {
        if (!$this->isLogin() || !$this->isPublished()) {
            return false;
        }

        if ($this->inProgressAttempt()) {
            return true;
        }

        $attempt_count = $this->completedAttemptCount();

        if ($this->quiz->retry_type === 'once') {
            return $attempt_count === 0;
        }

        if ($this->quiz->retry_type === 'limited') {
            return $attempt_count < (int)$this->quiz->retry_limit;
        }

        return true;
    }

    public function remainingAttempts()
    {
        if (empty($this->quiz) || $this->quiz->retry_type !== 'limited') {
            return null;
        }

        if (!$this->isLogin()) {
            return 0;
        }

        return max(
            0,
            (int)$this->quiz->retry_limit - $this->completedAttemptCount()
        );
    }

    private function attemptsQuery()
    {
        return QuizzesAttempts::query()
            ->where('quiz_id', $this->quiz->id)
            ->where('user_id', $this->user->id)
            ->where('is_preview', false);
    }
}
