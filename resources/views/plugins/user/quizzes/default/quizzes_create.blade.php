@extends('core.cms_frame_base_setting')

@section("core.cms_frame_edit_tab_$frame->id")
    @include('plugins.user.quizzes.quizzes_frame_edit_tab')
@endsection

@section("plugin_setting_$frame->id")

{{-- 入力エラー --}}
@include('plugins.common.errors_form_line')

<form action="{{ url('/') }}/redirect/plugin/quizzes/saveQuizSettings/{{ $page->id }}/{{ $frame->id }}#frame-{{ $frame->id }}"
      method="POST">

    {{ csrf_field() }}

    {{-- 新規作成であることをControllerへ通知 --}}
    <input type="hidden" name="form_mode" value="create">

    {{-- 通常画面へ戻るURL --}}
    <input type="hidden"
           name="normal_page_path"
           value="{{ URL::to($page->permanent_link) }}#frame-{{ $frame->id }}">

    <div class="card">
        <div class="card-header">
            新規作成
        </div>

        <div class="card-body">

            <div class="form-group row">
                <label class="{{ $frame->getSettingLabelClass() }}">
                    小テスト名
                    <span class="badge badge-danger">必須</span>
                </label>

                <div class="{{ $frame->getSettingInputClass() }}">
                    <input type="text"
                           name="title"
                           value="{{ old('title') }}"
                           class="form-control @if ($errors->has('title')) border-danger @endif">

                    @include('plugins.common.errors_inline', [
                        'name' => 'title'
                    ])
                </div>
            </div>

            <div class="form-group row">
                <label class="{{ $frame->getSettingLabelClass() }}">
                    説明
                </label>

                <div class="{{ $frame->getSettingInputClass() }}">
                    <textarea name="description"
                              rows="5"
                              class="form-control @if ($errors->has('description')) border-danger @endif">{{ old('description') }}</textarea>

                    @include('plugins.common.errors_inline', [
                        'name' => 'description'
                    ])
                </div>
            </div>

            <div class="form-group text-center mb-0">

                {{-- 保存せず通常画面へ戻る --}}
                <button type="button"
                        class="btn btn-secondary mr-2"
                        onclick="location.href='{{ URL::to($page->permanent_link) }}#frame-{{ $frame->id }}'">

                    <i class="fas fa-times"></i>

                    <span class="{{ $frame->getSettingButtonCaptionClass('md') }}">
                        キャンセル
                    </span>
                </button>

                {{-- 作成後、設定変更画面へ進む --}}
                <button type="submit"
                        name="after_save"
                        value="continue"
                        class="btn btn-primary">

                    <i class="fas fa-check"></i>

                    <span class="{{ $frame->getSettingButtonCaptionClass() }}">
                        作成して次へ
                    </span>
                </button>

            </div>

        </div>
    </div>

</form>

@endsection