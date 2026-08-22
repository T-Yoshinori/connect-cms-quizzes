<?php

namespace App\Plugins\User\Quizzes\Services;

use Illuminate\Support\Facades\DB;

class QuizCategoryResultService
{
    /**
     * 受験時スナップショットを使って、本人のカテゴリー結果と
     * 採点済み受験者（各ユーザーの最新1件）のカテゴリー平均を返します。
     */
    public function forAttempt(int $attempt_id, ?int $quiz_id = null): array
    {
        $average_stats = $quiz_id === null
            ? collect()
            : $this->averageStatsForQuiz($quiz_id);

        return $this->buildResults(
            $this->rowsForAttempts([$attempt_id]),
            $average_stats
        )->get($attempt_id, []);
    }

    /**
     * 複数受験のカテゴリー結果を、受験IDをキーにして一括取得します。
     */
    public function forAttempts(array $attempt_ids): array
    {
        $attempt_ids = collect($attempt_ids)
            ->map(function ($id) {
                return (int) $id;
            })
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($attempt_ids)) {
            return [];
        }

        return $this->buildResults(
            $this->rowsForAttempts($attempt_ids),
            collect()
        )->all();
    }

    /**
     * 現在の有効なカテゴリーを表示順に並べ、得点率の全体平均と集計人数を返します。
     */
    public function averagesForQuiz(int $quiz_id): array
    {
        $average_stats = $this->averageStatsForQuiz($quiz_id);

        $rows = DB::table('quiz_category_groups as category_group')
            ->join('quiz_categories as category', 'category.quiz_category_group_id', '=', 'category_group.id')
            ->where('category_group.quiz_id', $quiz_id)
            ->where('category_group.is_active', true)
            ->where('category.is_active', true)
            ->orderBy('category_group.sequence')
            ->orderBy('category_group.id')
            ->orderBy('category.sequence')
            ->orderBy('category.id')
            ->get([
                'category_group.id as group_id',
                'category_group.name as group_name',
                'category.id as category_id',
                'category.name as category_name',
            ]);

        return $rows->groupBy('group_id')->map(function ($group_rows) use ($average_stats) {
            return [
                'id' => (int) $group_rows->first()->group_id,
                'name' => $group_rows->first()->group_name,
                'categories' => $group_rows->map(function ($row) use ($average_stats) {
                    $stats = $average_stats->get((int) $row->category_id);

                    return [
                        'id' => (int) $row->category_id,
                        'name' => $row->category_name,
                        'average_score_rate' => $stats
                            ? round((float) $stats->average_score_rate, 1)
                            : null,
                        'participant_count' => $stats
                            ? (int) $stats->participant_count
                            : 0,
                    ];
                })->values()->all(),
            ];
        })->values()->all();
    }

    private function rowsForAttempts(array $attempt_ids)
    {
        return DB::table('quiz_attempt_categories as category')
            ->join('quiz_attempt_category_groups as category_group', 'category_group.id', '=', 'category.quiz_attempt_category_group_id')
            ->leftJoin('quiz_attempt_question_categories as assignment', 'assignment.quiz_attempt_category_id', '=', 'category.id')
            ->leftJoin('quiz_attempt_questions as question', 'question.id', '=', 'assignment.quiz_attempt_question_id')
            ->leftJoin('quiz_answers as answer', 'answer.quiz_attempt_question_id', '=', 'question.id')
            ->whereIn('category_group.quiz_attempt_id', $attempt_ids)
            ->groupBy(
                'category_group.quiz_attempt_id',
                'category_group.id',
                'category_group.source_category_group_id',
                'category_group.name',
                'category_group.display_sequence',
                'category.id',
                'category.source_category_id',
                'category.name',
                'category.display_sequence'
            )
            ->orderBy('category_group.quiz_attempt_id')
            ->orderBy('category_group.display_sequence')
            ->orderBy('category.display_sequence')
            ->selectRaw('category_group.quiz_attempt_id as attempt_id, category_group.id as group_id, category_group.source_category_group_id, category_group.name as group_name')
            ->selectRaw('category.id as category_id, category.source_category_id, category.name as category_name')
            ->selectRaw('COALESCE(SUM(question.points), 0) as max_score')
            ->selectRaw('COALESCE(SUM(CASE WHEN answer.grading_status = ? THEN answer.current_score ELSE 0 END), 0) as earned_score', ['graded'])
            ->selectRaw('SUM(CASE WHEN question.scoring_status = ? OR answer.grading_status = ? THEN 1 ELSE 0 END) as pending_count', ['manual_pending', 'manual_pending'])
            ->get();
    }

    private function buildResults($rows, $average_stats)
    {
        return $rows->groupBy('attempt_id')->map(function ($attempt_rows) use ($average_stats) {
            return $attempt_rows->groupBy('group_id')->map(function ($group_rows) use ($average_stats) {
                $first = $group_rows->first();

                return [
                    'id' => (int) $first->group_id,
                    'source_id' => $first->source_category_group_id === null
                        ? null
                        : (int) $first->source_category_group_id,
                    'name' => $first->group_name,
                    'categories' => $group_rows->map(function ($row) use ($average_stats) {
                        $max = (float) $row->max_score;
                        $pending = (int) $row->pending_count > 0;
                        $source_category_id = $row->source_category_id === null
                            ? null
                            : (int) $row->source_category_id;
                        $stats = $source_category_id === null
                            ? null
                            : $average_stats->get($source_category_id);

                        return [
                            'id' => (int) $row->category_id,
                            'source_id' => $source_category_id,
                            'name' => $row->category_name,
                            'earned_score' => (float) $row->earned_score,
                            'max_score' => $max,
                            'score_rate' => !$pending && $max > 0
                                ? round(((float) $row->earned_score / $max) * 100, 1)
                                : null,
                            'average_score_rate' => $stats
                                ? round((float) $stats->average_score_rate, 1)
                                : null,
                            'status' => $pending
                                ? 'pending'
                                : ($max > 0 ? 'graded' : 'not_applicable'),
                        ];
                    })->values()->all(),
                ];
            })->values()->all();
        });
    }

    /**
     * 採点済み・非プレビュー・ログインユーザーの最新受験1件を母集団にし、
     * 各受験者の丸め前得点率をカテゴリー項目ごとに平均します。
     */
    private function averageStatsForQuiz(int $quiz_id)
    {
        $latest_attempts = DB::table('quiz_attempts')
            ->select('user_id')
            ->selectRaw('MAX(attempt_no) as latest_attempt_no')
            ->where('quiz_id', $quiz_id)
            ->where('status', 'graded')
            ->where('is_preview', false)
            ->whereNotNull('user_id')
            ->groupBy('user_id');

        $category_rates = DB::table('quiz_attempts as attempt')
            ->joinSub($latest_attempts, 'latest_attempt', function ($join) {
                $join->on('attempt.user_id', '=', 'latest_attempt.user_id')
                    ->on('attempt.attempt_no', '=', 'latest_attempt.latest_attempt_no');
            })
            ->join('quiz_attempt_category_groups as category_group', 'category_group.quiz_attempt_id', '=', 'attempt.id')
            ->join('quiz_attempt_categories as category', 'category.quiz_attempt_category_group_id', '=', 'category_group.id')
            ->leftJoin('quiz_attempt_question_categories as assignment', 'assignment.quiz_attempt_category_id', '=', 'category.id')
            ->leftJoin('quiz_attempt_questions as question', 'question.id', '=', 'assignment.quiz_attempt_question_id')
            ->leftJoin('quiz_answers as answer', 'answer.quiz_attempt_question_id', '=', 'question.id')
            ->where('attempt.quiz_id', $quiz_id)
            ->where('attempt.status', 'graded')
            ->where('attempt.is_preview', false)
            ->whereNotNull('category.source_category_id')
            ->groupBy('attempt.id', 'category.source_category_id')
            ->selectRaw('category.source_category_id')
            ->selectRaw('SUM(CASE WHEN answer.grading_status = ? THEN answer.current_score ELSE 0 END) / NULLIF(SUM(question.points), 0) * 100 as score_rate', ['graded'])
            ->havingRaw('SUM(question.points) > 0')
            ->havingRaw('SUM(CASE WHEN question.scoring_status = ? OR answer.grading_status = ? THEN 1 ELSE 0 END) = 0', ['manual_pending', 'manual_pending']);

        return DB::query()
            ->fromSub($category_rates, 'category_rate')
            ->selectRaw('source_category_id, AVG(score_rate) as average_score_rate, COUNT(*) as participant_count')
            ->groupBy('source_category_id')
            ->get()
            ->keyBy(function ($row) {
                return (int) $row->source_category_id;
            });
    }
}
