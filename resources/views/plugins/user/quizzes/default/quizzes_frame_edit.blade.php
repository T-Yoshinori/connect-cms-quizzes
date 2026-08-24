@extends('core.cms_frame_base_setting')

@section("core.cms_frame_edit_tab_$frame->id")
    @include('plugins.user.quizzes.quizzes_frame_edit_tab')
@endsection

@section("plugin_setting_$frame->id")

@include('plugins.common.errors_form_line')

@if (empty($quiz))
    <div class="alert alert-warning mb-0">
        このフレームで使用する小テストが選択されていません。
        「小テスト選択」または「新規作成」から小テストを設定してください。
    </div>
@else
    @include('plugins.user.quizzes.default.quizzes_shared_warning')

    <form action="{{ url('/') }}/redirect/plugin/quizzes/saveView/{{ $page->id }}/{{ $frame->id }}#frame-{{ $frame->id }}"
          method="POST">

        {{ csrf_field() }}

        <input type="hidden"
               name="normal_page_path"
               value="{{ URL::to($page->permanent_link) }}#frame-{{ $frame->id }}">

        <div class="card">
            <div class="card-header">
                出題・表示設定
            </div>

            <div class="card-body">

                <div class="form-group row pb-4 mb-4 border-bottom">
                    <label class="{{ $frame->getSettingLabelClass(true) }}">
                        出題順
                    </label>

                    <div class="{{ $frame->getSettingInputClass(true) }}">
                        <div class="d-flex flex-column flex-md-row flex-wrap align-items-start">
                            <div class="custom-control custom-radio mb-2 mr-md-4">
                            <input type="radio"
                                   id="question_order_registered"
                                   name="question_order"
                                   value="registered"
                                   class="custom-control-input"
                                   @if (old('question_order', $quiz->question_order ?? 'registered') === 'registered') checked @endif>
                            <label class="custom-control-label"
                                   for="question_order_registered">
                                登録順
                            </label>
                            </div>

                            <div class="custom-control custom-radio mb-2 mr-md-4">
                            <input type="radio"
                                   id="question_order_random"
                                   name="question_order"
                                   value="random"
                                   class="custom-control-input"
                                   @if (old('question_order', $quiz->question_order ?? 'registered') === 'random') checked @endif>
                            <label class="custom-control-label"
                                   for="question_order_random">
                                出題ページ内でランダム
                            </label>
                            </div>
                        </div>

                        <small class="form-text text-muted d-block mt-2 pl-1">
                            ランダムを選んでも、共通問題文と配下の問題の関係は維持します。
                        </small>

                        @include('plugins.common.errors_inline', [
                            'name' => 'question_order'
                        ])
                    </div>
                </div>

                <div class="form-group row pb-4 mb-4 border-bottom">
                    <label class="{{ $frame->getSettingLabelClass(true) }}">
                        問題の表示単位
                    </label>

                    <div class="{{ $frame->getSettingInputClass(true) }}">
                        <div class="d-flex flex-column flex-md-row flex-wrap align-items-start">
                            <div class="custom-control custom-radio mb-2 mr-md-4">
                            <input type="radio"
                                   id="question_display_page"
                                   name="question_display"
                                   value="page"
                                   class="custom-control-input"
                                   @if (old('question_display', $quiz->question_display ?? 'page') === 'page') checked @endif>
                            <label class="custom-control-label"
                                   for="question_display_page">
                                出題ページ単位
                            </label>
                            </div>

                            <div class="custom-control custom-radio mb-2 mr-md-4">
                            <input type="radio"
                                   id="question_display_one_by_one"
                                   name="question_display"
                                   value="one_by_one"
                                   class="custom-control-input"
                                   @if (old('question_display', $quiz->question_display ?? 'page') === 'one_by_one') checked @endif>
                            <label class="custom-control-label"
                                   for="question_display_one_by_one">
                                1問ずつ
                            </label>
                            </div>
                        </div>

                        <small class="form-text text-muted d-block mt-2 pl-1">
                            「1問ずつ」では、受験画面に1画面1問で表示します。
                        </small>

                        @include('plugins.common.errors_inline', [
                            'name' => 'question_display'
                        ])
                    </div>
                </div>

                <div class="form-group row pb-3 mb-3">
                    <label class="{{ $frame->getSettingLabelClass(true) }}">
                        受験画面の問題番号
                    </label>

                    <div class="{{ $frame->getSettingInputClass(true) }}">
                        <div class="d-flex flex-column flex-md-row flex-wrap align-items-start">
                            <div class="custom-control custom-radio mb-2 mr-md-4">
                            <input type="radio"
                                   id="question_number_numeric"
                                   name="question_number_format"
                                   value="numeric"
                                   class="custom-control-input"
                                   @if (old('question_number_format', $quiz->question_number_format ?? 'numeric') === 'numeric') checked @endif>
                            <label class="custom-control-label"
                                   for="question_number_numeric">
                                1. 2. 3.
                            </label>
                            </div>

                            <div class="custom-control custom-radio mb-2 mr-md-4">
                            <input type="radio"
                                   id="question_number_q"
                                   name="question_number_format"
                                   value="q"
                                   class="custom-control-input"
                                   @if (old('question_number_format', $quiz->question_number_format ?? 'numeric') === 'q') checked @endif>
                            <label class="custom-control-label"
                                   for="question_number_q">
                                Q1 Q2 Q3
                            </label>
                            </div>

                            <div class="custom-control custom-radio mb-2 mr-md-4">
                            <input type="radio"
                                   id="question_number_none"
                                   name="question_number_format"
                                   value="none"
                                   class="custom-control-input"
                                   @if (old('question_number_format', $quiz->question_number_format ?? 'numeric') === 'none') checked @endif>
                            <label class="custom-control-label"
                                   for="question_number_none">
                                表示しない
                            </label>
                            </div>
                        </div>

                        @include('plugins.common.errors_inline', [
                            'name' => 'question_number_format'
                        ])
                    </div>
                </div>

                <div class="form-group text-center mt-4 mb-0">
                    <button type="button"
                            class="btn btn-secondary mr-2"
                            onclick="location.href='{{ URL::to($page->permanent_link) }}#frame-{{ $frame->id }}'">
                        <i class="fas fa-times"></i>
                        <span class="{{ $frame->getSettingButtonCaptionClass('md') }}">
                            キャンセル
                        </span>
                    </button>

                    <button type="submit"
                            name="after_save"
                            value="continue"
                            class="btn btn-outline-primary mr-2">
                        <i class="fas fa-save"></i>
                        <span class="{{ $frame->getSettingButtonCaptionClass('lg') }}">
                            保存して続ける
                        </span>
                    </button>

                    <button type="submit"
                            name="after_save"
                            value="back"
                            class="btn btn-primary">
                        <i class="fas fa-check"></i>
                        <span class="{{ $frame->getSettingButtonCaptionClass('lg') }}">
                            保存して戻る
                        </span>
                    </button>
                </div>

            </div>
        </div>
    </form>
@endif

@endsection
