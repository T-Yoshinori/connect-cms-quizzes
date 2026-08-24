<?php

namespace App\Plugins\User\Quizzes\Services;

use Illuminate\Support\Facades\DB;

use App\Models\User\Quizzes\QuizzesAttempts;

/**
 * 管理者向けの小テスト結果集計
 */
class QuizAdminResultService
{
    /**
     * 各ユーザーの最新の採点済み結果1件と全体集計を取得します。
     */
    public function forQuiz(int $quiz_id)
    {
        $latest_attempts = DB::table('quiz_attempts')
            ->select('user_id')
            ->selectRaw('MAX(attempt_no) as latest_attempt_no')
            ->where('quiz_id', $quiz_id)
            ->where('status', 'graded')
            ->where('is_preview', false)
            ->whereNotNull('user_id')
            ->groupBy('user_id');

        $attempts = QuizzesAttempts::query()
            ->with(['user', 'answers'])
            ->joinSub($latest_attempts, 'latest_attempts', function ($join) {
                $join->on('quiz_attempts.user_id', '=', 'latest_attempts.user_id')
                    ->on('quiz_attempts.attempt_no', '=', 'latest_attempts.latest_attempt_no');
            })
            ->where('quiz_attempts.quiz_id', $quiz_id)
            ->where('quiz_attempts.status', 'graded')
            ->where('quiz_attempts.is_preview', false)
            ->whereNotNull('quiz_attempts.total_score')
            ->select('quiz_attempts.*')
            ->orderByDesc('quiz_attempts.submitted_at')
            ->orderByDesc('quiz_attempts.id')
            ->get();

        if ($attempts->isEmpty()) {
            return (object) [
                'attempts' => $attempts,
                'participant_count' => 0,
                'average_elapsed_seconds' => null,
                'average_score' => null,
                'highest_score' => null,
                'lowest_score' => null,
                'variance' => null,
                'distribution' => null,
            ];
        }

        $scores = $attempts->pluck('total_score')->map(function ($score) {
            return (float) $score;
        });
        $average_score = (float) $scores->avg();
        $elapsed_seconds = $attempts
            ->pluck('elapsed_seconds')
            ->filter(function ($seconds) {
                return !is_null($seconds);
            })
            ->map(function ($seconds) {
                return (int) $seconds;
            });
        $max_score = max(
            1,
            (float) $attempts->max('effective_max_score'),
            (float) $scores->max()
        );

        return (object) [
            'attempts' => $attempts,
            'participant_count' => $attempts->count(),
            'average_elapsed_seconds' => $elapsed_seconds->isEmpty()
                ? null
                : (int) round($elapsed_seconds->avg()),
            'average_score' => $average_score,
            'highest_score' => (float) $scores->max(),
            'lowest_score' => (float) $scores->min(),
            'variance' => (float) $scores->avg(function ($score) use ($average_score) {
                return pow((float) $score - $average_score, 2);
            }),
            'distribution' => $this->buildDistribution($scores->all(), $max_score),
        ];
    }

    /**
     * 満点を10分割し、得点帯ごとの人数を作成します。
     */
    private function buildDistribution(array $scores, float $max_score): array
    {
        $bin_count = 10;
        $bin_width = $max_score / $bin_count;
        $counts = array_fill(0, $bin_count, 0);
        $labels = [];

        foreach ($scores as $score) {
            $index = min(
                $bin_count - 1,
                max(0, (int) floor((float) $score / $bin_width))
            );
            $counts[$index]++;
        }

        for ($index = 0; $index < $bin_count; $index++) {
            $from = $index * $bin_width;
            $to = ($index + 1) * $bin_width;
            $labels[] = number_format($from, 1)
                . '～'
                . number_format($to, 1)
                . ($index === $bin_count - 1 ? '点' : '');
        }

        return [
            'labels' => $labels,
            'counts' => $counts,
            'max_score' => $max_score,
        ];
    }
}
