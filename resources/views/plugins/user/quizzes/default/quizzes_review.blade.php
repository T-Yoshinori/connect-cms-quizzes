@php
    $is_expired = $attempt->expires_at && now()->greaterThanOrEqualTo($attempt->expires_at);
@endphp

<div class="card">
    <div class="card-header">提出確認</div>
    <div class="card-body">
        @if ($is_expired)
            <div class="alert alert-warning">
                制限時間が終了しています。保存済みの回答を提出してください。回答を変更することはできません。
            </div>
        @else
            <p>回答内容を確認し、小テストを提出します。提出後は回答を変更できません。</p>
        @endif

        <div class="text-center">
            @if (!$is_expired)
                <a class="btn btn-secondary"
                   href="{{ url('/') }}/plugin/quizzes/answer/{{ $page->id }}/{{ $frame->id }}/{{ $attempt->id }}#frame-{{ $frame->id }}">
                    回答に戻る
                </a>
            @endif
            <form class="d-inline"
                  action="{{ url('/') }}/redirect/plugin/quizzes/submitAttempt/{{ $page->id }}/{{ $frame->id }}/{{ $attempt->id }}#frame-{{ $frame->id }}"
                  method="POST"
                  onsubmit="return confirm('提出後は回答を変更できません。提出しますか？');">
                {{ csrf_field() }}
                <button class="btn btn-primary" type="submit">
                    {{ $is_expired ? '保存済みの回答を提出する' : '提出する' }}
                </button>
            </form>
        </div>
    </div>
</div>
