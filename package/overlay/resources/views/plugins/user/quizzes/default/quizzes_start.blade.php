@extends('core.cms_frame_base')

@section("plugin_contents_$frame->id")

@php
    $can_start = $tool->canStart();
    $remaining_attempts = $tool->remainingAttempts();
    $is_resume = !empty($in_progress_attempt);
    $has_completed = !empty($latest_attempt)
        && in_array($latest_attempt->status, ['submitted', 'graded', 'expired'], true);
@endphp

<div class="card shadow-sm">
    <div class="card-header bg-white">
        <div class="small text-muted mb-1">受験案内</div>
        <h2 class="h4 mb-0">{{ $quiz->title }}</h2>
    </div>

    <div class="card-body">
        @if (!empty($quiz->description))
            <div class="mb-4">{!! nl2br(e($quiz->description)) !!}</div>
        @endif

        <dl class="row mb-4">
            <dt class="col-sm-4">受験可能期間</dt>
            <dd class="col-sm-8">
                @if ($quiz->publish_start_at || $quiz->publish_end_at)
                    {{ $quiz->publish_start_at ? $quiz->publish_start_at->format('Y年n月j日 H:i') : '指定なし' }}
                    ～
                    {{ $quiz->publish_end_at ? $quiz->publish_end_at->format('Y年n月j日 H:i') : '指定なし' }}
                @else
                    指定なし
                @endif
            </dd>

            <dt class="col-sm-4">問題数</dt>
            <dd class="col-sm-8">{{ number_format($question_count) }}問</dd>

            <dt class="col-sm-4">合計点</dt>
            <dd class="col-sm-8">{{ number_format((float)$total_points, 2) }}点</dd>

            <dt class="col-sm-4">所要時間の目安</dt>
            <dd class="col-sm-8">
                {{ $quiz->estimated_minutes ? $quiz->estimated_minutes . '分' : '指定なし' }}
            </dd>

            <dt class="col-sm-4">制限時間</dt>
            <dd class="col-sm-8">
                {{ $quiz->time_limit_minutes ? $quiz->time_limit_minutes . '分' : '制限なし' }}
            </dd>

            <dt class="col-sm-4">合格条件</dt>
            <dd class="col-sm-8">
                @if ($quiz->passing_type === 'score')
                    {{ number_format((float)$quiz->passing_score, 2) }}点以上
                @elseif ($quiz->passing_type === 'rate')
                    正答率{{ number_format((float)$quiz->passing_rate, 2) }}％以上
                @else
                    合否判定なし
                @endif
            </dd>

            <dt class="col-sm-4">受験回数</dt>
            <dd class="col-sm-8">
                @if ($quiz->retry_type === 'once')
                    1回のみ
                @elseif ($quiz->retry_type === 'limited')
                    全{{ $quiz->retry_limit }}回
                    @if (!is_null($remaining_attempts))
                        （残り{{ $remaining_attempts }}回）
                    @endif
                @else
                    制限なし
                @endif
            </dd>
        </dl>

        @if (!$tool->isLogin())
            <div class="alert alert-warning mb-0">
                受験するにはログインが必要です。
            </div>
        @elseif ($is_resume)
            <div class="alert alert-info">
                受験途中の回答があります。続きから再開できます。
            </div>
        @elseif (!$can_start)
            <div class="alert alert-warning mb-0">
                受験可能回数の上限に達しているため、新しい受験は開始できません。
            </div>
        @elseif ($question_count === 0)
            <div class="alert alert-warning mb-0">
                受験できる問題が登録されていません。
            </div>
        @else
            @if ($has_completed)
                <div class="alert alert-light border">
                    前回の受験は完了しています。
                </div>
            @endif
        @endif

        @if ($can_start && $question_count > 0)
            <form action="{{ url('/') }}/redirect/plugin/quizzes/startAttempt/{{ $page->id }}/{{ $frame->id }}/{{ $quiz->id }}#frame-{{ $frame->id }}"
                  method="POST"
                  class="text-center mb-0">
                {{ csrf_field() }}
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-play"></i>
                    @if ($is_resume)
                        受験を再開する
                    @elseif ($has_completed)
                        もう一度受験する
                    @else
                        受験を開始する
                    @endif
                </button>
            </form>
        @endif
    </div>
</div>

@endsection
