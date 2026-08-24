@extends('core.cms_frame_base')

@section("plugin_contents_$frame->id")

@include('plugins.user.quizzes.default.quizzes_mathjax')

@php
    $is_grading_pending = $attempt->grading_status === 'manual_pending';
@endphp

<div class="card">
    <div class="card-header">
        <h2 class="h4 mb-0">{{ $attempt->quiz->title }} 結果</h2>
    </div>
    <div class="card-body">
        @if ($is_grading_pending)
            <div class="alert alert-info">
                記述式問題を採点中です。採点完了後に結果を表示します。
            </div>
        @endif

        @if ($attempt->quiz->show_score)
            @if ($is_grading_pending)
                <p class="h5">得点：採点待ち</p>
            @else
                <p class="h5">
                    得点：{{ number_format((float) $attempt->total_score, 2) }}
                    /
                    {{ number_format((float) $attempt->effective_max_score, 2) }}
                </p>
            @endif
        @endif

        @if (
            !$is_grading_pending
            && !empty($statistics)
            && (
                $attempt->quiz->show_average_score
                || $attempt->quiz->show_highest_score
                || $attempt->quiz->show_lowest_score
                || $attempt->quiz->show_participant_count
            )
        )
            <div class="card bg-light border mb-3">
                <div class="card-body py-3">
                    <h3 class="h6 mb-3">採点済み受験者の集計</h3>
                    <dl class="row mb-0">
                        @if ($attempt->quiz->show_average_score)
                            <dt class="col-sm-6">平均点</dt>
                            <dd class="col-sm-6">{{ number_format((float) $statistics->average_score, 2) }}点</dd>
                        @endif
                        @if ($attempt->quiz->show_highest_score)
                            <dt class="col-sm-6">最高得点</dt>
                            <dd class="col-sm-6">{{ number_format((float) $statistics->highest_score, 2) }}点</dd>
                        @endif
                        @if ($attempt->quiz->show_lowest_score)
                            <dt class="col-sm-6">最低得点</dt>
                            <dd class="col-sm-6">{{ number_format((float) $statistics->lowest_score, 2) }}点</dd>
                        @endif
                        @if ($attempt->quiz->show_participant_count)
                            <dt class="col-sm-6">集計人数</dt>
                            <dd class="col-sm-6">{{ number_format((int) $statistics->participant_count) }}人</dd>
                        @endif
                    </dl>
                </div>
            </div>
        @endif

        @if (
            !$is_grading_pending
            && $attempt->quiz->show_score_distribution
            && !empty($statistics)
            && !empty($statistics->distribution)
        )
            <div class="card border mb-3">
                <div class="card-body">
                    <h3 class="h6 mb-2">得点分布</h3>
                    <p class="mb-3">
                        <span class="mr-4">
                            平均点：{{ number_format((float) $statistics->average_score, 2) }}点
                        </span>
                        <span>
                            あなたの得点：{{ number_format((float) $attempt->total_score, 2) }}点
                        </span>
                    </p>
                    <div style="position: relative; height: 320px;">
                        <canvas id="quiz-score-distribution-{{ $frame->id }}-{{ $attempt->id }}"
                                aria-label="採点済み受験者の得点分布"
                                role="img"></canvas>
                    </div>
                    <div class="small mt-2">
                        <span class="mr-4" style="color: #dc3545;">― あなたの得点</span>
                        <span style="color: #007bff;">- - 平均点</span>
                    </div>
                </div>
            </div>
        @endif

        @if ($attempt->quiz->use_category_scoring && empty($category_results))
            <div class="alert alert-warning">
                カテゴリー別結果を表示できません。この受験には、受験開始時のカテゴリー情報が保存されていません。
            </div>
        @endif

        @if (!empty($category_results))
            <div class="card border mb-3"><div class="card-body">
                <h3 class="h5 mb-3">カテゴリー別結果</h3>
                @foreach ($category_results as $category_group)
                    @php
                        $chart_categories = collect($category_group['categories'])->filter(function ($category) {
                            return $category['status'] === 'graded' && $category['score_rate'] !== null;
                        })->values();
                    @endphp
                    <section class="mb-4">
                        <h4 class="h6 mb-2">{{ $category_group['name'] }}</h4>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-3">
                                <thead class="thead-light"><tr>
                                    <th scope="col">カテゴリー</th>
                                    <th scope="col" class="text-right">獲得点</th>
                                    <th scope="col" class="text-right">配点</th>
                                    <th scope="col" class="text-right">得点率</th>
                                    <th scope="col" class="text-right">全体平均</th>
                                    <th scope="col">採点状態</th>
                                </tr></thead>
                                <tbody>
                                    @foreach ($category_group['categories'] as $category)
                                        <tr>
                                            <th scope="row">{{ $category['name'] }}</th>
                                            <td class="text-right">
                                                {{ $category['status'] === 'pending' ? '―' : number_format((float) $category['earned_score'], 2) }}
                                            </td>
                                            <td class="text-right">{{ number_format((float) $category['max_score'], 2) }}</td>
                                            <td class="text-right">
                                                {{ $category['status'] === 'graded' ? number_format((float) $category['score_rate'], 1) . '%' : '―' }}
                                            </td>
                                            <td class="text-right">
                                                {{ $category['average_score_rate'] === null ? '―' : number_format((float) $category['average_score_rate'], 1) . '%' }}
                                            </td>
                                            <td>
                                                @if ($category['status'] === 'pending')
                                                    採点待ち
                                                @elseif ($category['status'] === 'not_applicable')
                                                    対象なし
                                                @else
                                                    採点済み
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @if ($chart_categories->count() >= 3)
                            <div style="position: relative; height: 360px;">
                                <canvas class="quiz-category-radar"
                                        data-labels="{{ json_encode($chart_categories->pluck('name')->values()) }}"
                                        data-user-rates="{{ json_encode($chart_categories->pluck('score_rate')->values()) }}"
                                        data-average-rates="{{ json_encode($chart_categories->pluck('average_score_rate')->values()) }}"
                                        aria-label="{{ $category_group['name'] }}のカテゴリー別得点率"
                                        role="img"></canvas>
                            </div>
                        @else
                            <p class="small text-muted mb-3">グラフに表示できるカテゴリーが3項目未満のため、数値表のみ表示しています。</p>
                        @endif
                    </section>
                @endforeach
            </div></div>
        @endif

        @if ($attempt->quiz->show_pass_status)
            <p>
                判定：
                @if ($is_grading_pending || $attempt->pass_status === 'pending')
                    判定待ち
                @elseif ($attempt->pass_status === 'passed')
                    合格
                @else
                    不合格
                @endif
            </p>
        @endif

        @if (
            $attempt->quiz->show_question_result
            || $attempt->quiz->show_user_answer
            || $attempt->quiz->show_commentary
            || $attempt->quiz->show_grading_comment
        )
            @php $question_number = 0; @endphp
            @foreach ($attempt->attempt_pages as $attempt_page)
                @foreach ($attempt_page->attempt_questions as $attempt_question)
                    @php
                        $question_number++;
                        $answer = $attempt_question->answer;
                        $answer_data = optional($answer)->answer_data ?? [];
                        $question_type = $attempt_question->question_revision->question_type;
                    @endphp
                    <div class="border rounded p-3 mb-2">
                        <div class="font-weight-bold">問{{ $question_number }}</div>
                        <div>{!! $attempt_question->question_revision->question_text !!}</div>

                        @if ($attempt->quiz->show_user_answer)
                            @php
                                $user_answer_lines = [];

                                if (in_array($question_type, ['single_choice', 'multiple_choice'], true)) {
                                    $selected_choice_ids = array_map(
                                        'intval',
                                        $answer_data['attempt_choice_ids']
                                            ?? ($answer_data['choice_ids'] ?? [])
                                    );
                                    $user_answer_lines = $attempt_question->choices
                                        ->filter(function ($choice) use ($selected_choice_ids) {
                                            return in_array((int) $choice->id, $selected_choice_ids, true);
                                        })
                                        ->map(function ($choice) {
                                            return optional($choice->choice_revision)->label;
                                        })
                                        ->filter()
                                        ->values()
                                        ->all();
                                } elseif ($question_type === 'multiple_word') {
                                    $user_answer_lines = collect($answer_data['texts'] ?? [])
                                        ->map(function ($text) {
                                            return trim((string) $text);
                                        })
                                        ->filter(function ($text) {
                                            return $text !== '';
                                        })
                                        ->values()
                                        ->all();
                                } else {
                                    $text = trim((string) ($answer_data['text'] ?? ''));
                                    if ($text !== '') {
                                        $user_answer_lines = [$text];
                                    }
                                }
                            @endphp

                            <div class="alert alert-light border mt-2 mb-0">
                                <strong>あなたの解答</strong>
                                @if (empty($user_answer_lines))
                                    <div class="text-muted">未回答</div>
                                @else
                                    @foreach ($user_answer_lines as $user_answer_line)
                                        <div>{!! nl2br(e($user_answer_line)) !!}</div>
                                    @endforeach
                                @endif
                            </div>
                        @endif

                        @if ($attempt->quiz->show_question_result)
                            <div class="mt-2">
                                得点：
                                @if (empty($answer) || $answer->grading_status === 'manual_pending')
                                    採点待ち
                                @else
                                    {{ number_format((float) $answer->current_score, 2) }}
                                    / {{ number_format((float) $attempt_question->points, 2) }}
                                @endif
                            </div>
                        @endif

                        @if (
                            $attempt->quiz->show_commentary
                            && !$is_grading_pending
                            && !empty($attempt_question->question_revision->commentary)
                        )
                            <div class="mt-2">
                                {!! $attempt_question->question_revision->commentary !!}
                            </div>
                        @endif

                        @if (
                            $attempt->quiz->show_grading_comment
                            && !empty(optional(optional($answer)->current_grade)->comment)
                        )
                            <div class="alert alert-light border mt-2 mb-0">
                                <strong>採点コメント</strong>
                                <div>{!! nl2br(e($answer->current_grade->comment)) !!}</div>
                            </div>
                        @endif
                    </div>
                @endforeach
            @endforeach
        @endif

        <div class="text-center mt-4">
            <a href="{{ URL::to($page->permanent_link) }}#frame-{{ $frame->id }}"
               class="btn btn-primary">
                <i class="fas fa-arrow-left"></i>
                小テストの初期画面へ戻る
            </a>
        </div>
    </div>
</div>

@if (
    !$is_grading_pending
    && $attempt->quiz->show_score_distribution
    && !empty($statistics)
    && !empty($statistics->distribution)
)
<script>
(function () {
    var canvasId = @json('quiz-score-distribution-' . $frame->id . '-' . $attempt->id);
    var distribution = @json($statistics->distribution);
    var averageScore = {{ json_encode((float) $statistics->average_score) }};
    var userScore = {{ json_encode((float) $attempt->total_score) }};

    function renderScoreDistribution() {
        var canvas = document.getElementById(canvasId);
        if (!canvas || typeof Chart === 'undefined') {
            return;
        }

        var scorePositionPlugin = {
            id: 'quizScorePositions',
            afterDatasetsDraw: function (chart) {
                var area = chart.chartArea;
                var ctx = chart.ctx;
                var maxScore = Number(distribution.max_score) || 1;

                function drawPosition(score, color, dashed, label) {
                    var ratio = Math.max(0, Math.min(1, Number(score) / maxScore));
                    var x = area.left + (area.right - area.left) * ratio;

                    ctx.save();
                    ctx.beginPath();
                    ctx.setLineDash(dashed ? [6, 4] : []);
                    ctx.strokeStyle = color;
                    ctx.lineWidth = 2;
                    ctx.moveTo(x, area.top);
                    ctx.lineTo(x, area.bottom);
                    ctx.stroke();
                    ctx.setLineDash([]);
                    ctx.fillStyle = color;
                    ctx.font = 'bold 12px sans-serif';
                    ctx.textAlign = ratio > 0.82 ? 'right' : 'left';
                    ctx.fillText(label, x + (ratio > 0.82 ? -4 : 4), area.top + 14);
                    ctx.restore();
                }

                drawPosition(averageScore, '#007bff', true, '平均');
                drawPosition(userScore, '#dc3545', false, 'あなた');
            }
        };

        new Chart(canvas.getContext('2d'), {
            type: 'bar',
            data: {
                labels: distribution.labels,
                datasets: [{
                    label: '人数',
                    data: distribution.counts,
                    backgroundColor: 'rgba(40, 167, 69, 0.55)',
                    borderColor: '#28a745',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: false,
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: '得点帯'
                        }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0,
                            stepSize: 1
                        },
                        title: {
                            display: true,
                            text: '人数'
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                return context.parsed.y + '人';
                            }
                        }
                    }
                }
            },
            plugins: [scorePositionPlugin]
        });
    }

    if (typeof Chart !== 'undefined') {
        renderScoreDistribution();
        return;
    }

    var existingScript = document.querySelector('script[data-quiz-chartjs]');
    if (existingScript) {
        existingScript.addEventListener('load', renderScoreDistribution, { once: true });
        return;
    }

    var script = document.createElement('script');
    script.src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js';
    script.dataset.quizChartjs = '1';
    script.onload = renderScoreDistribution;
    document.head.appendChild(script);
})();
</script>
@endif

@if (!empty($category_results))
<script>
(function () {
    function renderCategoryRadars() {
        if (typeof Chart === 'undefined') return;
        document.querySelectorAll('.quiz-category-radar').forEach(function (canvas) {
            if (canvas.dataset.rendered === '1') return;
            var labels = JSON.parse(canvas.dataset.labels || '[]');
            var userRates = JSON.parse(canvas.dataset.userRates || '[]');
            var averageRates = JSON.parse(canvas.dataset.averageRates || '[]');
            if (labels.length < 3) return;
            canvas.dataset.rendered = '1';
            new Chart(canvas.getContext('2d'), {
                type: 'radar',
                data: {
                    labels: labels,
                    datasets: [
                        { label: 'あなた', data: userRates, borderColor: '#dc3545', backgroundColor: 'rgba(220,53,69,.12)', pointBackgroundColor: '#dc3545', borderWidth: 2 },
                        { label: '全体平均', data: averageRates, borderColor: '#007bff', backgroundColor: 'rgba(0,123,255,.08)', pointBackgroundColor: '#007bff', borderWidth: 2 }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: false,
                    scales: { r: { beginAtZero: true, min: 0, max: 100, ticks: { stepSize: 20, callback: function (value) { return value + '%'; } } } },
                    plugins: { tooltip: { callbacks: { label: function (context) {
                        return context.raw === null
                            ? context.dataset.label + '：データなし'
                            : context.dataset.label + '：' + Number(context.raw).toFixed(1) + '%';
                    } } } }
                }
            });
        });
    }
    if (typeof Chart !== 'undefined') {
        renderCategoryRadars();
        return;
    }
    var existingScript = document.querySelector('script[data-quiz-chartjs]');
    if (existingScript) {
        existingScript.addEventListener('load', renderCategoryRadars, { once: true });
        return;
    }
    var script = document.createElement('script');
    script.src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js';
    script.dataset.quizChartjs = '1';
    script.onload = renderCategoryRadars;
    document.head.appendChild(script);
})();
</script>
@endif

@endsection
