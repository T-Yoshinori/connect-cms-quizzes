@extends('core.cms_frame_base')

@section("plugin_contents_$frame->id")

@php
    $format_seconds = function ($seconds) {
        if (is_null($seconds)) {
            return '―';
        }

        $seconds = max(0, (int) $seconds);
        return sprintf('%d分%02d秒', intdiv($seconds, 60), $seconds % 60);
    };

    $pass_labels = [
        'passed' => '合格',
        'failed' => '不合格',
        'pending' => '判定待ち',
        'not_applicable' => '判定なし',
    ];
@endphp

<section class="quiz-admin-results">
    <div class="d-flex flex-wrap justify-content-between align-items-start mb-3">
        <div>
            <div class="small text-muted mb-1">管理者向け結果表示</div>
            <h2 class="h3 mb-0">{{ $quiz->title }}</h2>
        </div>
        <div class="d-flex flex-wrap justify-content-end mt-2 mt-sm-0">
            @if (!$results->attempts->isEmpty())
                <a href="{{ url('/') }}/download/plugin/quizzes/exportItemResponseCsv/{{ $page->id }}/{{ $frame->id }}/{{ $quiz->id }}"
                   class="btn btn-outline-success mr-2 mb-2">
                    <i class="fas fa-file-csv"></i>
                    小問別反応表CSV
                </a>
                <a href="{{ url('/') }}/download/plugin/quizzes/exportSpTableCsv/{{ $page->id }}/{{ $frame->id }}/{{ $quiz->id }}"
                   class="btn btn-outline-success mr-2 mb-2">
                    <i class="fas fa-table"></i>
                    SP表CSV
                </a>
                @if ($quiz->use_category_scoring)
                    <a href="{{ url('/') }}/download/plugin/quizzes/exportCategoryResultsCsv/{{ $page->id }}/{{ $frame->id }}/{{ $quiz->id }}"
                       class="btn btn-outline-success mr-2 mb-2">
                        <i class="fas fa-chart-pie"></i>
                        カテゴリー別結果CSV
                    </a>
                @endif
            @endif
            <a href="{{ URL::to($page->permanent_link) }}#frame-{{ $frame->id }}"
               class="btn btn-outline-primary mb-2">
                <i class="fas fa-arrow-left"></i>
                小テストの初期画面へ戻る
            </a>
        </div>
    </div>

    @if ($results->attempts->isEmpty())
        <div class="alert alert-info">
            集計対象となる採点済みの受験結果はありません。
        </div>
    @else
        <div class="card mb-4">
            <div class="card-header bg-white">
                <h3 class="h5 mb-0">全体成績</h3>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">総受験者数</dt>
                    <dd class="col-sm-8">{{ number_format($results->participant_count) }}人</dd>
                    <dt class="col-sm-4">平均所要時間</dt>
                    <dd class="col-sm-8">{{ $format_seconds($results->average_elapsed_seconds) }}</dd>
                    <dt class="col-sm-4">平均点</dt>
                    <dd class="col-sm-8">{{ number_format($results->average_score, 2) }}点</dd>
                    <dt class="col-sm-4">最高点</dt>
                    <dd class="col-sm-8">{{ number_format($results->highest_score, 2) }}点</dd>
                    <dt class="col-sm-4">最低点</dt>
                    <dd class="col-sm-8">{{ number_format($results->lowest_score, 2) }}点</dd>
                    <dt class="col-sm-4">分散</dt>
                    <dd class="col-sm-8">{{ number_format($results->variance, 2) }}</dd>
                </dl>
            </div>
        </div>

        @if ($quiz->use_category_scoring && !empty($category_averages))
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h3 class="h5 mb-0">カテゴリー別全体平均</h3>
                </div>
                <div class="card-body">
                    @foreach ($category_averages as $category_group)
                        <section class="mb-4">
                            <h4 class="h6 mb-2">{{ $category_group['name'] }}</h4>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered mb-0">
                                    <thead class="thead-light"><tr>
                                        <th scope="col">カテゴリー</th>
                                        <th scope="col" class="text-right">平均得点率</th>
                                        <th scope="col" class="text-right">集計人数</th>
                                    </tr></thead>
                                    <tbody>
                                        @foreach ($category_group['categories'] as $category)
                                            <tr>
                                                <th scope="row">{{ $category['name'] }}</th>
                                                <td class="text-right">
                                                    {{ $category['average_score_rate'] === null ? '対象なし' : number_format((float) $category['average_score_rate'], 1) . '%' }}
                                                </td>
                                                <td class="text-right">{{ number_format((int) $category['participant_count']) }}人</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </section>
                    @endforeach
                </div>
            </div>
        @endif

        @if (!empty($selected_attempt))
            <div class="card mb-4 border-primary">
                <div class="card-header bg-light">
                    <h3 class="h5 mb-0">選択した受験者の成績</h3>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">受験者</dt>
                        <dd class="col-sm-8">
                            {{ optional($selected_attempt->user)->name ?? 'ユーザーID ' . $selected_attempt->user_id }}
                        </dd>
                        <dt class="col-sm-4">得点</dt>
                        <dd class="col-sm-8">
                            {{ number_format((float) $selected_attempt->total_score, 2) }}
                            /
                            {{ number_format((float) $selected_attempt->effective_max_score, 2) }}点
                        </dd>
                        <dt class="col-sm-4">合否</dt>
                        <dd class="col-sm-8">
                            {{ $pass_labels[$selected_attempt->pass_status] ?? $selected_attempt->pass_status }}
                        </dd>
                        <dt class="col-sm-4">所要時間</dt>
                        <dd class="col-sm-8">{{ $format_seconds($selected_attempt->elapsed_seconds) }}</dd>
                        <dt class="col-sm-4">受験日時</dt>
                        <dd class="col-sm-8">
                            {{ $selected_attempt->submitted_at
                                ? $selected_attempt->submitted_at->format('Y年n月j日 H:i')
                                : '―' }}
                        </dd>
                    </dl>
                </div>
            </div>
        @endif

        @if (!empty($selected_attempt) && $quiz->use_category_scoring && empty($selected_category_results))
            <div class="alert alert-warning">
                選択した受験結果には、受験開始時のカテゴリー情報が保存されていません。
            </div>
        @endif

        @if (!empty($selected_attempt) && !empty($selected_category_results))
            <div class="card mb-4 border-primary">
                <div class="card-header bg-light">
                    <h3 class="h5 mb-0">選択した受験者のカテゴリー別結果</h3>
                </div>
                <div class="card-body">
                    @foreach ($selected_category_results as $category_group)
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
                                                <td class="text-right">{{ $category['status'] === 'pending' ? '―' : number_format((float) $category['earned_score'], 2) }}</td>
                                                <td class="text-right">{{ number_format((float) $category['max_score'], 2) }}</td>
                                                <td class="text-right">{{ $category['status'] === 'graded' ? number_format((float) $category['score_rate'], 1) . '%' : '―' }}</td>
                                                <td class="text-right">{{ $category['average_score_rate'] === null ? '―' : number_format((float) $category['average_score_rate'], 1) . '%' }}</td>
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
                                    <canvas class="quiz-admin-category-radar"
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
                </div>
            </div>
        @endif

        <div class="card mb-4">
            <div class="card-header bg-white">
                <h3 class="h5 mb-0">度数分布</h3>
            </div>
            <div class="card-body">
                <p class="mb-3">
                    <span class="mr-4">
                        平均点：{{ number_format($results->average_score, 2) }}点
                    </span>
                    @if (!empty($selected_attempt))
                        <span>
                            選択した受験者：
                            {{ number_format((float) $selected_attempt->total_score, 2) }}点
                        </span>
                    @else
                        <span class="text-muted">
                            解答履歴から受験者を選択すると、その得点位置を表示します。
                        </span>
                    @endif
                </p>
                <div style="position: relative; height: 320px;">
                    <canvas id="quiz-admin-score-distribution-{{ $frame->id }}-{{ $quiz->id }}"
                            aria-label="全受験者の得点分布"
                            role="img"></canvas>
                </div>
                <div class="small mt-2">
                    <span class="mr-4" style="color: #007bff;">- - 平均点</span>
                    @if (!empty($selected_attempt))
                        <span style="color: #dc3545;">― 選択した受験者</span>
                    @endif
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header bg-white">
                <h3 class="h5 mb-0">解答履歴</h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-bordered mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th scope="col">受験者</th>
                                <th scope="col" class="text-center">回数</th>
                                <th scope="col" class="text-center">完答</th>
                                <th scope="col" class="text-center">合格</th>
                                <th scope="col" class="text-center">時間内</th>
                                <th scope="col">日時</th>
                                <th scope="col" class="text-right">時間</th>
                                <th scope="col" class="text-right">得点</th>
                                <th scope="col" class="text-center">採点</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($results->attempts as $result_attempt)
                                @php
                                    $is_selected = !empty($selected_attempt)
                                        && $selected_attempt->id === $result_attempt->id;
                                    $is_complete = $result_attempt->answers->isNotEmpty()
                                        && !$result_attempt->answers->contains(function ($answer) {
                                            return $answer->correctness === 'unanswered';
                                        });
                                    $within_time = empty($result_attempt->time_limit_minutes_snapshot)
                                        || (
                                            !is_null($result_attempt->elapsed_seconds)
                                            && $result_attempt->elapsed_seconds
                                                <= $result_attempt->time_limit_minutes_snapshot * 60
                                        );
                                    $select_url = url('/')
                                        . '/plugin/quizzes/adminResults/'
                                        . $page->id . '/'
                                        . $frame->id . '/'
                                        . $quiz->id
                                        . '?attempt_id=' . $result_attempt->id
                                        . '#frame-' . $frame->id;
                                @endphp
                                <tr class="{{ $is_selected ? 'table-primary' : '' }}"
                                    data-select-url="{{ $select_url }}"
                                    style="cursor: pointer;"
                                    onclick="window.location.href=this.dataset.selectUrl">
                                    <td>
                                        <a href="{{ $select_url }}">
                                            {{ optional($result_attempt->user)->name
                                                ?? 'ユーザーID ' . $result_attempt->user_id }}
                                        </a>
                                    </td>
                                    <td class="text-center">{{ $result_attempt->attempt_no }}</td>
                                    <td class="text-center">{{ $is_complete ? '○' : '―' }}</td>
                                    <td class="text-center">
                                        @if ($result_attempt->pass_status === 'passed')
                                            ○
                                        @elseif ($result_attempt->pass_status === 'failed')
                                            ×
                                        @else
                                            ―
                                        @endif
                                    </td>
                                    <td class="text-center">{{ $within_time ? '○' : '×' }}</td>
                                    <td>
                                        {{ $result_attempt->submitted_at
                                            ? $result_attempt->submitted_at->format('Y年n月j日 H:i')
                                            : '―' }}
                                    </td>
                                    <td class="text-right">{{ $format_seconds($result_attempt->elapsed_seconds) }}</td>
                                    <td class="text-right">
                                        {{ number_format((float) $result_attempt->total_score, 2) }}
                                        /
                                        {{ number_format((float) $result_attempt->effective_max_score, 2) }}
                                    </td>
                                    <td class="text-center">完了</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
</section>

@if (!empty($selected_attempt) && !empty($selected_category_results))
<script>
(function () {
    function renderAdminCategoryRadars() {
        if (typeof Chart === 'undefined') return;
        document.querySelectorAll('.quiz-admin-category-radar').forEach(function (canvas) {
            if (canvas.dataset.rendered === '1') return;
            var labels = JSON.parse(canvas.dataset.labels || '[]');
            if (labels.length < 3) return;
            canvas.dataset.rendered = '1';
            new Chart(canvas.getContext('2d'), {
                type: 'radar',
                data: {
                    labels: labels,
                    datasets: [
                        { label: '選択した受験者', data: JSON.parse(canvas.dataset.userRates || '[]'), borderColor: '#dc3545', backgroundColor: 'rgba(220,53,69,.12)', pointBackgroundColor: '#dc3545', borderWidth: 2 },
                        { label: '全体平均', data: JSON.parse(canvas.dataset.averageRates || '[]'), borderColor: '#007bff', backgroundColor: 'rgba(0,123,255,.08)', pointBackgroundColor: '#007bff', borderWidth: 2 }
                    ]
                },
                options: {
                    responsive: true, maintainAspectRatio: false, animation: false,
                    scales: { r: { beginAtZero: true, min: 0, max: 100, ticks: { stepSize: 20, callback: function (value) { return value + '%'; } } } }
                }
            });
        });
    }
    if (typeof Chart !== 'undefined') {
        renderAdminCategoryRadars();
        return;
    }
    var existingScript = document.querySelector('script[data-quiz-chartjs]');
    if (existingScript) {
        existingScript.addEventListener('load', renderAdminCategoryRadars, { once: true });
        return;
    }
    var script = document.createElement('script');
    script.src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js';
    script.dataset.quizChartjs = '1';
    script.onload = renderAdminCategoryRadars;
    document.head.appendChild(script);
})();
</script>
@endif

@if (!$results->attempts->isEmpty() && !empty($results->distribution))
<script>
(function () {
    var canvasId = @json('quiz-admin-score-distribution-' . $frame->id . '-' . $quiz->id);
    var distribution = @json($results->distribution);
    var averageScore = {{ json_encode((float) $results->average_score) }};
    var selectedScore = @json(!empty($selected_attempt) ? (float) $selected_attempt->total_score : null);

    function renderScoreDistribution() {
        var canvas = document.getElementById(canvasId);
        if (!canvas || typeof Chart === 'undefined' || canvas.dataset.rendered === '1') {
            return;
        }
        canvas.dataset.rendered = '1';

        var scorePositionPlugin = {
            id: 'quizAdminScorePositions',
            afterDatasetsDraw: function (chart) {
                var area = chart.chartArea;
                var ctx = chart.ctx;
                var maxScore = Number(distribution.max_score) || 1;

                function drawPosition(score, color, dashed, label) {
                    if (score === null || typeof score === 'undefined') {
                        return;
                    }

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
                drawPosition(selectedScore, '#dc3545', false, '選択');
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
                        title: { display: true, text: '得点帯' }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: { precision: 0, stepSize: 1 },
                        title: { display: true, text: '人数' }
                    }
                },
                plugins: {
                    legend: { display: false },
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

@endsection
