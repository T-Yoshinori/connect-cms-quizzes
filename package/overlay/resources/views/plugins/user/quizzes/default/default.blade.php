@extends('core.cms_frame_base')

@section("plugin_contents_$frame->id")

@include('plugins.user.quizzes.default.quizzes_mathjax')

@php
    $quiz = $quiz ?? null;
    $latest_result_attempt = $latest_result_attempt ?? null;
    $can_manage_quiz = $can_manage_quiz ?? false;
    $can_view_admin_results = $can_view_admin_results ?? false;
@endphp

@if ($can_manage_quiz)
@if (empty($quiz))
    <div class="alert alert-info mb-0">
        <p class="mb-3">
            このフレームに小テストが設定されていません。
        </p>

        <a href="{{ url('/') }}/plugin/quizzes/listQuizzes/{{ $page->id }}/{{ $frame->id }}#frame-{{ $frame->id }}"
           class="btn btn-primary">
            <i class="fas fa-list"></i>
            小テストを選択・作成する
        </a>
    </div>
@else
    @if (!empty($quiz) && $can_view_admin_results)
        <section class="quiz-results-access mb-3 px-3 py-3 bg-light border-left border-info">
            <div class="d-flex flex-wrap justify-content-between align-items-center">
                <div class="mr-3 mb-2">
                    <div class="small text-muted mb-1">小テスト</div>
                    <h2 class="h4 mb-0">{{ $quiz->title }}</h2>
                </div>
                <a href="{{ url('/') }}/plugin/quizzes/adminResults/{{ $page->id }}/{{ $frame->id }}/{{ $quiz->id }}#frame-{{ $frame->id }}"
                   class="btn btn-outline-info mb-2">
                    <i class="fas fa-chart-bar"></i>
                    管理者向け結果
                </a>
            </div>
        </section>
    @endif

    @include('plugins.user.quizzes.default.quizzes_shared_warning')

    <section class="quiz-admin-header mb-4 px-3 py-3 bg-light border-left border-primary">
        <div class="d-flex flex-wrap justify-content-between align-items-start">
            <div class="mr-3">
                <div class="small text-muted mb-1">小テスト</div>
                <h2 class="h3 mb-0">
                    {{ $quiz->title }}
                </h2>
            </div>

            <div class="mt-2 mt-sm-0">
                @if (in_array($quiz->status, ['public', 'published'], true))
                    <span class="badge badge-success">公開</span>
                @elseif ($quiz->status === 'closed')
                    <span class="badge badge-secondary">公開終了</span>
                @else
                    <span class="badge badge-warning">下書き</span>
                @endif
            </div>
        </div>

        @if (!empty($quiz->description))
            <div class="quiz-description mt-3 mb-3">
                {!! nl2br(e($quiz->description)) !!}
            </div>
        @else
            <p class="text-muted mt-3 mb-3">
                小テストの説明は登録されていません。
            </p>
        @endif

        <div class="d-flex flex-wrap">
            <a href="{{ url('/') }}/plugin/quizzes/editQuizSettings/{{ $page->id }}/{{ $frame->id }}/{{ $quiz->id }}#frame-{{ $frame->id }}"
               class="btn btn-sm btn-outline-primary mr-2 mb-2">
                <i class="fas fa-cog"></i>
                設定変更
            </a>

            <a href="{{ url('/') }}/plugin/quizzes/adminResults/{{ $page->id }}/{{ $frame->id }}/{{ $quiz->id }}#frame-{{ $frame->id }}"
               class="btn btn-sm btn-outline-info mr-2 mb-2">
                <i class="fas fa-chart-bar"></i>
                管理者向け結果
            </a>

            @if (!empty($latest_result_attempt))
                <a href="{{ url('/') }}/plugin/quizzes/result/{{ $page->id }}/{{ $frame->id }}/{{ $latest_result_attempt->id }}#frame-{{ $frame->id }}"
                   class="btn btn-sm btn-outline-primary mr-2 mb-2">
                    <i class="fas fa-poll"></i>
                    結果を見る
                </a>
            @endif

            @if (in_array($quiz->status, ['public', 'published'], true))
                <a href="{{ url('/') }}/plugin/quizzes/start/{{ $page->id }}/{{ $frame->id }}/{{ $quiz->id }}#frame-{{ $frame->id }}"
                   class="btn btn-sm btn-success mb-2">
                    <i class="fas fa-play"></i>
                    受験する
                </a>
            @endif
        </div>
    </section>

    @if ($quiz->pages->isEmpty())
        <div class="alert alert-secondary text-center">
            <p class="mb-3">
                問題はまだ登録されていません。
            </p>
            <a href="{{ url('/') }}/plugin/quizzes/createFirstQuestion/{{ $page->id }}/{{ $frame->id }}/{{ $quiz->id }}#frame-{{ $frame->id }}"
               class="btn btn-primary">
                <i class="fas fa-plus"></i>
                問題を作成
            </a>
        </div>
    @else
        @php
            $question_number = 0;
        @endphp

        @foreach ($quiz->pages as $quiz_page)
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center">
                    <div>
                        <div class="small text-muted mb-1">
                            出題ページ {{ $loop->iteration }}
                        </div>
                        <h3 class="h5 mb-0">
                            {{ !empty($quiz_page->title)
                                ? $quiz_page->title
                                : '出題ページ' . $loop->iteration }}
                        </h3>
                    </div>

                    <a href="{{ url('/') }}/plugin/quizzes/editPage/{{ $page->id }}/{{ $frame->id }}/{{ $quiz_page->id }}#frame-{{ $frame->id }}"
                       class="btn btn-sm btn-outline-primary mt-2 mt-sm-0">
                        <i class="fas fa-edit"></i>
                        出題ページ設定
                    </a>
                </div>

                <div class="card-body">
                    @if (!empty($quiz_page->description))
                        <div class="quiz-page-description mb-4">
                            {!! $quiz_page->description !!}
                        </div>
                    @else
                        <div class="alert alert-light border text-muted mb-4">
                            この出題ページの共通問題文・資料は登録されていません。
                        </div>
                    @endif

                    @if ($quiz_page->questions->isEmpty())
                        <div class="alert alert-secondary mb-0">
                            この出題ページには問題が登録されていません。
                        </div>
                    @else
                        <div class="list-group list-group-flush">
                            @foreach ($quiz_page->questions as $question)
                                @php
                                    $question_number++;
                                    $revision = $question->current_revision;
                                @endphp

                                <div class="list-group-item px-0">
                                    <div class="d-flex align-items-start">
                                        @if (($quiz->question_number_format ?? 'numeric') !== 'none')
                                            <div class="font-weight-bold mr-3 text-nowrap">
                                                @if (($quiz->question_number_format ?? 'numeric') === 'q')
                                                    Q{{ $question_number }}
                                                @else
                                                    {{ $question_number }}.
                                                @endif
                                            </div>
                                        @endif

                                        <div class="flex-grow-1">
                                            <div class="text-right mb-2">
                                                <a href="{{ url('/') }}/plugin/quizzes/editQuestion/{{ $page->id }}/{{ $frame->id }}/{{ $question->id }}#frame-{{ $frame->id }}" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-edit"></i> 編集
                                                </a>
                                                @if (!empty($revision) && $revision->question_type === 'essay')
                                                    @if ($question->pending_manual_answers_count > 0)
                                                        <a href="{{ url('/') }}/plugin/quizzes/grading/{{ $page->id }}/{{ $frame->id }}/{{ $question->id }}#frame-{{ $frame->id }}"
                                                           class="btn btn-sm btn-outline-success">
                                                            <i class="fas fa-pen"></i>
                                                            採点（{{ $question->pending_manual_answers_count }}件）
                                                        </a>
                                                    @else
                                                        <button type="button"
                                                                class="btn btn-sm btn-outline-secondary"
                                                                disabled
                                                                aria-disabled="true">
                                                            <i class="fas fa-pen"></i> 採点（0件）
                                                        </button>
                                                    @endif
                                                @endif
                                                <form action="{{ url('/') }}/redirect/plugin/quizzes/deleteQuestion/{{ $page->id }}/{{ $frame->id }}/{{ $question->id }}#frame-{{ $frame->id }}" method="POST" class="d-inline" onsubmit="return confirm('この問題を削除します。よろしいですか？');">
                                                    {{ csrf_field() }}
                                                    <input type="hidden" name="normal_page_path" value="{{ URL::to($page->permanent_link) }}#frame-{{ $frame->id }}">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i> 削除</button>
                                                </form>
                                            </div>
                                            @if (!empty($revision))
                                                <div class="quiz-question-text">
                                                    {!! $revision->question_text !!}
                                                </div>

                                                @if (in_array($revision->question_type, ['single_choice', 'multiple_choice'], true))
                                                    <ol class="quiz-choice-list mt-3 mb-2">
                                                        @foreach ($revision->choices as $choice)
                                                            <li class="mb-1">
                                                                {{ $choice->label }}
                                                                @if ($choice->is_correct)
                                                                    <span class="badge badge-success ml-1">正解</span>
                                                                @endif
                                                            </li>
                                                        @endforeach
                                                    </ol>
                                                @elseif (in_array($revision->question_type, ['word', 'multiple_word'], true))
                                                    <div class="mt-3">
                                                        <span class="small text-muted">正解候補：</span>
                                                        @foreach ($revision->correct_answers as $answer)
                                                            <span class="badge badge-light border mr-1">
                                                                @if ($revision->question_type === 'multiple_word')
                                                                    回答{{ $answer->answer_group }}：
                                                                @endif
                                                                {{ $answer->answer_text }}
                                                            </span>
                                                        @endforeach
                                                    </div>
                                                @endif

                                                <div class="small text-muted mt-2">
                                                    配点：{{ number_format((float) $revision->points, 2) }}点
                                                </div>
                                            @else
                                                <div class="text-danger">
                                                    現在使用する問題リビジョンが設定されていません。
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div class="text-center mt-3">
                        <a href="{{ url('/') }}/plugin/quizzes/createQuestion/{{ $page->id }}/{{ $frame->id }}/{{ $quiz_page->id }}#frame-{{ $frame->id }}" class="btn btn-outline-primary">
                            <i class="fas fa-plus"></i> 問題を追加
                        </a>
                    </div>
                </div>
            </div>
        @endforeach
    @endif

    @if ($quiz->pages->isNotEmpty())
        <div class="text-center mb-4">
            <a href="{{ url('/') }}/plugin/quizzes/createPage/{{ $page->id }}/{{ $frame->id }}/{{ $quiz->id }}#frame-{{ $frame->id }}"
               class="btn btn-primary">
                <i class="fas fa-plus"></i>
                出題ページを追加
            </a>
        </div>
    @endif
@endif

@else
    @if (empty($quiz))
        <div class="alert alert-info mb-0">
            現在、受験できる小テストはありません。
        </div>
    @elseif (in_array($quiz->status, ['public', 'published'], true))
        <div class="text-center py-3">
            <h2 class="h4 mb-3">{{ $quiz->title }}</h2>
            @if (!empty($quiz->description))
                <div class="mb-4">{!! nl2br(e($quiz->description)) !!}</div>
            @endif
            @if (!empty($latest_result_attempt))
                <div class="alert alert-light border mb-3">
                    この小テストは受験済みです。
                    @if ($latest_result_attempt->status === 'submitted')
                        現在、採点中です。
                    @endif
                </div>
            @endif

            <div class="d-flex flex-wrap justify-content-center">
                @if (!empty($latest_result_attempt))
                    <a href="{{ url('/') }}/plugin/quizzes/result/{{ $page->id }}/{{ $frame->id }}/{{ $latest_result_attempt->id }}#frame-{{ $frame->id }}"
                       class="btn btn-outline-primary mr-2 mb-2">
                        <i class="fas fa-poll"></i>
                        結果を見る
                    </a>
                @endif

                <a href="{{ url('/') }}/plugin/quizzes/start/{{ $page->id }}/{{ $frame->id }}/{{ $quiz->id }}#frame-{{ $frame->id }}"
                   class="btn btn-primary mb-2">
                    <i class="fas fa-play"></i>
                    受験案内を確認する
                </a>
            </div>
        </div>
    @else
        <div class="alert alert-info mb-0">
            現在、この小テストは公開されていません。
        </div>
    @endif
@endif

@endsection
