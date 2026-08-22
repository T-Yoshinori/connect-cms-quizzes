@if (!empty($quiz) && ($quiz->frames_count ?? 0) > 1)
    <div class="alert alert-warning">
        <div class="font-weight-bold mb-2">
            この小テストは{{ $quiz->frames_count }}個のフレームで共有されています。
        </div>
        <p class="mb-3">
            このまま編集すると、変更内容はこの小テストを使用するすべてのフレームに反映されます。
            現在のフレームだけを変更する場合は、先に複製してください。
        </p>
        <form action="{{ url('/') }}/redirect/plugin/quizzes/copyQuizForFrame/{{ $page->id }}/{{ $frame->id }}/{{ $quiz->id }}#frame-{{ $frame->id }}"
              method="POST"
              class="d-inline"
              onsubmit="return confirm('この小テストを複製し、現在のフレームを複製先へ切り替えます。よろしいですか？');">
            {{ csrf_field() }}
            <button type="submit" class="btn btn-warning">
                <i class="fas fa-copy"></i>
                複製して編集する
            </button>
        </form>
        <div class="small mt-2">
            共有したまま編集する場合は、下の設定・編集操作をそのまま続けてください。
        </div>
    </div>
@endif
