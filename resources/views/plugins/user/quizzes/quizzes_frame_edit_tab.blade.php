{{--
 * 小テスト設定画面タブ
 * Bbses の bbses_frame_edit_tab.blade.php と同じ構造。
 --}}
@if ($action == 'editQuizSettings')
    <li role="presentation" class="nav-item">
        <span class="nav-link"><span class="active">設定変更</span></span>
    </li>
@else
    <li role="presentation" class="nav-item">
        <a href="{{ url('/') }}/plugin/quizzes/editQuizSettings/{{ $page->id }}/{{ $frame->id }}#frame-{{ $frame->id }}" class="nav-link">設定変更</a>
    </li>
@endif

@if ($action == 'createQuiz')
    <li role="presentation" class="nav-item">
        <span class="nav-link"><span class="active">新規作成</span></span>
    </li>
@else
    <li role="presentation" class="nav-item">
        <a href="{{ url('/') }}/plugin/quizzes/createQuiz/{{ $page->id }}/{{ $frame->id }}#frame-{{ $frame->id }}" class="nav-link">新規作成</a>
    </li>
@endif

@if ($action == 'editView')
    <li role="presentation" class="nav-item">
        <span class="nav-link"><span class="active">出題・表示設定</span></span>
    </li>
@else
    <li role="presentation" class="nav-item">
        <a href="{{ url('/') }}/plugin/quizzes/editView/{{ $page->id }}/{{ $frame->id }}#frame-{{ $frame->id }}" class="nav-link">出題・表示設定</a>
    </li>
@endif

@if ($action == 'listQuizzes')
    <li role="presentation" class="nav-item">
        <span class="nav-link"><span class="active">小テスト選択</span></span>
    </li>
@else
    <li role="presentation" class="nav-item">
        <a href="{{ url('/') }}/plugin/quizzes/listQuizzes/{{ $page->id }}/{{ $frame->id }}#frame-{{ $frame->id }}" class="nav-link">小テスト選択</a>
    </li>
@endif

@if ($action == 'editBucketsRoles' || $action == 'saveBucketsRoles')
    <li role="presentation" class="nav-item">
        <span class="nav-link"><span class="active">権限設定</span></span>
    </li>
@else
    <li role="presentation" class="nav-item">
        <a href="{{ url('/') }}/plugin/quizzes/editBucketsRoles/{{ $page->id }}/{{ $frame->id }}#frame-{{ $frame->id }}" class="nav-link">権限設定</a>
    </li>
@endif

@if ($action == 'editBucketsMails' || $action == 'saveBucketsMails')
    <li role="presentation" class="nav-item">
        <span class="nav-link"><span class="active">メール設定</span></span>
    </li>
@else
    <li role="presentation" class="nav-item">
        <a href="{{ url('/') }}/plugin/quizzes/editBucketsMails/{{ $page->id }}/{{ $frame->id }}#frame-{{ $frame->id }}" class="nav-link">メール設定</a>
    </li>
@endif
