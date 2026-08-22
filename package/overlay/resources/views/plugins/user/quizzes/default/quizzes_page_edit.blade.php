@extends('core.cms_frame_base')

@section("plugin_contents_$frame->id")

@include('plugins.common.errors_form_line')

{{-- 共通問題文・資料用WYSIWYG・数式入力 --}}
@include('plugins.user.quizzes.default.quizzes_mathjax', ['math_editor_tools' => true])
@include('plugins.common.wysiwyg', [
    'target_class' => 'wysiwyg-page-description-' . $frame->id,
    'use_mathjax' => true,
])

<form action="{{ url('/') }}/redirect/plugin/quizzes/savePage/{{ $page->id }}/{{ $frame->id }}@if (!$is_create)/{{ $quiz_page->id }}@endif#frame-{{ $frame->id }}"
      method="POST">

    {{ csrf_field() }}

    <input type="hidden" name="quiz_id" value="{{ $quiz->id }}">

    <input type="hidden"
           name="normal_page_path"
           value="{{ URL::to($page->permanent_link) }}#frame-{{ $frame->id }}">

    <div class="card">
        <div class="card-header">
            {{ $is_create ? 'ページ追加' : 'ページ編集' }}
        </div>

        <div class="card-body">

            <div class="form-group row">
                <label class="col-md-3 col-form-label text-md-right">
                    小テスト名
                </label>

                <div class="col-md-9">
                    <p class="form-control-plaintext mb-0">
                        {{ $quiz->title }}
                    </p>
                </div>
            </div>

            <div class="form-group row">
                <label for="quiz_page_title"
                       class="col-md-3 col-form-label text-md-right">
                    ページタイトル
                </label>

                <div class="col-md-9">
                    <input type="text"
                           id="quiz_page_title"
                           name="title"
                           value="{{ old('title', $quiz_page->title) }}"
                           maxlength="191"
                           class="form-control @if ($errors->has('title')) border-danger @endif">

                    <small class="form-text text-muted">
                        未入力の場合は、通常画面で「ページ1」「ページ2」のように表示します。
                    </small>

                    @include('plugins.common.errors_inline', ['name' => 'title'])
                </div>
            </div>

            <div class="form-group row">
                <label for="quiz_page_description"
                       class="col-md-3 col-form-label text-md-right">
                    共通問題文・資料
                </label>

                <div class="col-md-9">
                    <textarea id="quiz_page_description"
                              name="description"
                              rows="12"
                              class="form-control wysiwyg-page-description-{{ $frame->id }} @if ($errors->has('description')) border-danger @endif">{!! old('description', $quiz_page->description) !!}</textarea>

                    <small class="form-text text-muted">
                        このページ内の全問題に共通する文章、画像、表、動画、注意事項などを入力します。
                    </small>

                    @include('plugins.common.errors_inline', ['name' => 'description'])
                </div>
            </div>

            <div class="form-group text-center mb-0">
                <button type="button"
                        class="btn btn-secondary mr-2"
                        onclick="location.href='{{ URL::to($page->permanent_link) }}#frame-{{ $frame->id }}'">
                    <i class="fas fa-times"></i>
                    キャンセル
                </button>

                <button type="submit"
                        name="after_save"
                        value="continue"
                        class="btn btn-outline-primary mr-2">
                    <i class="fas fa-save"></i>
                    保存して続ける
                </button>

                <button type="submit"
                        name="after_save"
                        value="back"
                        class="btn btn-primary">
                    <i class="fas fa-check"></i>
                    保存して戻る
                </button>
            </div>

        </div>
    </div>

</form>

@endsection
