<?php

namespace App\Plugins\User\Quizzes\Services;

use Illuminate\Support\Facades\DB;

/**
 * 受験者向けの小テスト結果集計
 */
class QuizResultStatisticsService
{
    /**
     * 各ユーザーの最新の採点済み結果1件を対象に集計します。
     */
    public function forQuiz(int $quiz_id, float $max_score)
    {
        $latest_attempts = DB::table('quiz_attempts')
            ->select('user_id')
            ->selectRaw('MAX(attempt_no) as latest_attempt_no')
            ->where('quiz_id', $quiz_id)
            ->where('status', 'graded')
            ->where('is_preview', false)
            ->whereNotNull('user_id')
            ->groupBy('user_id');

        $scores = DB::table('quiz_attempts as attempts')
            ->joinSub($latest_attempts, 'latest_attempts', function ($join) {
                $join->on('attempts.user_id', '=', 'latest_attempts.user_id')
                    ->on('attempts.attempt_no', '=', 'latest_attempts.latest_attempt_no');
            })
            ->where('attempts.quiz_id', $quiz_id)
            ->where('attempts.status', 'graded')
            ->where('attempts.is_preview', false)
            ->whereNotNull('attempts.total_score')
            ->pluck('attempts.total_score')
            ->map(function ($score) {
                return (float) $score;
            });

        if ($scores->isEmpty()) {
            return null;
        }

        $statistics = (object) [
            'participant_count' => $scores->count(),
            'average_score' => $scores->avg(),
            'highest_score' => $scores->max(),
            'lowest_score' => $scores->min(),
        ];

        $statistics->distribution = $this->buildDistribution($scores->all(), $max_score);

        return $statistics;
    }

    /**
     * 満点を10分割し、得点帯ごとの人数を作成します。
     */
    private function buildDistribution(array $scores, float $max_score): array
    {
        $bin_count = 10;
        $chart_max_score = max($max_score, max($scores), 1);
        $bin_width = $chart_max_score / $bin_count;
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
            'max_score' => $chart_max_score,
        ];
    }
}
