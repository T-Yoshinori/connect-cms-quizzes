@extends('core.cms_frame_base_setting')

@section("core.cms_frame_edit_tab_$frame->id")
    @include('plugins.user.quizzes.quizzes_frame_edit_tab')
@endsection

@section("plugin_setting_$frame->id")

{{-- 共通エラー表示 --}}
@include('plugins.common.errors_form_line')

@if (empty($quiz))

    <div class="alert alert-warning mb-0">
        このフレームには小テストが設定されていません。
    </div>

@else

    @include('plugins.user.quizzes.default.quizzes_shared_warning')

    <form action="{{ url('/') }}/redirect/plugin/quizzes/saveQuizSettings/{{ $page->id }}/{{ $frame->id }}/{{ $quiz->id }}#frame-{{ $frame->id }}"
          method="POST">

        {{ csrf_field() }}

        <input type="hidden" name="form_mode" value="settings">

        <div class="card">

            <div class="card-header">
                設定変更
            </div>

            <div class="card-body">

                {{-- 小テスト名 --}}
                <div class="form-group row">
                    <label class="{{ $frame->getSettingLabelClass() }}">
                        小テスト名
                    </label>

                    <div class="{{ $frame->getSettingInputClass() }}">
                        <p class="form-control-plaintext mb-0">
                            {{ $quiz->title }}
                        </p>
                    </div>
                </div>

                {{-- 説明 --}}
                @if (!empty($quiz->description))
                    <div class="form-group row">
                        <label class="{{ $frame->getSettingLabelClass() }}">
                            説明
                        </label>

                        <div class="{{ $frame->getSettingInputClass() }}">
                            <p class="form-control-plaintext mb-0">
                                {!! nl2br(e($quiz->description)) !!}
                            </p>
                        </div>
                    </div>
                @endif

                {{-- カテゴリー別採点 --}}
                <div class="form-group row">
                    <label class="{{ $frame->getSettingLabelClass() }}">
                        カテゴリー別採点
                    </label>

                    <div class="{{ $frame->getSettingInputClass() }}">
                        <div class="custom-control custom-radio custom-control-inline">
                            <input type="radio"
                                   id="use_category_scoring_0"
                                   name="use_category_scoring"
                                   value="0"
                                   class="custom-control-input"
                                   @if (!(bool) old('use_category_scoring', $quiz->use_category_scoring)) checked @endif>
                            <label class="custom-control-label"
                                   for="use_category_scoring_0">
                                使用しない
                            </label>
                        </div>

                        <div class="custom-control custom-radio custom-control-inline">
                            <input type="radio"
                                   id="use_category_scoring_1"
                                   name="use_category_scoring"
                                   value="1"
                                   class="custom-control-input"
                                   @if ((bool) old('use_category_scoring', $quiz->use_category_scoring)) checked @endif>
                            <label class="custom-control-label"
                                   for="use_category_scoring_1">
                                使用する
                            </label>
                        </div>

                        <small class="form-text text-muted">
                            「使用する」を選ぶと、カテゴリー管理と問題への割当を利用できます。
                            「使用しない」へ戻しても、登録済みのカテゴリー設定は保持されます。
                        </small>

                        @include('plugins.common.errors_inline', [
                            'name' => 'use_category_scoring'
                        ])
                    </div>
                </div>

                @if ($quiz->use_category_scoring)
                    <div class="form-group row">
                        <div class="offset-md-3 {{ $frame->getSettingInputClass() }}">
                            <a href="{{ url('/') }}/plugin/quizzes/manageCategories/{{ $page->id }}/{{ $frame->id }}/{{ $quiz->id }}#frame-{{ $frame->id }}"
                               class="btn btn-outline-primary">
                                <i class="fas fa-tags"></i> カテゴリーを管理する
                            </a>
                        </div>
                    </div>
                @endif

                {{-- 公開状態 --}}
                <div class="form-group row">
                    <label class="{{ $frame->getSettingLabelClass() }}">
                        公開状態
                    </label>

                    <div class="{{ $frame->getSettingInputClass() }}">

                        <div class="custom-control custom-radio custom-control-inline">
                            <input type="radio"
                                   id="status_draft"
                                   name="status"
                                   value="draft"
                                   class="custom-control-input"
                                   @if (old('status', $quiz->status) === 'draft') checked @endif>

                            <label class="custom-control-label"
                                   for="status_draft">
                                下書き
                            </label>
                        </div>

                        <div class="custom-control custom-radio custom-control-inline">
                            <input type="radio"
                                   id="status_published"
                                   name="status"
                                   value="published"
                                   class="custom-control-input"
                                   @if (old('status', $quiz->status) === 'published') checked @endif>

                            <label class="custom-control-label"
                                   for="status_published">
                                公開
                            </label>
                        </div>

                        <div class="custom-control custom-radio custom-control-inline">
                            <input type="radio"
                                   id="status_closed"
                                   name="status"
                                   value="closed"
                                   class="custom-control-input"
                                   @if (old('status', $quiz->status) === 'closed') checked @endif>

                            <label class="custom-control-label"
                                   for="status_closed">
                                公開終了
                            </label>
                        </div>

                        @include('plugins.common.errors_inline', [
                            'name' => 'status'
                        ])
                    </div>
                </div>

                {{-- 公開開始日時 --}}
                <div class="form-group row">
                    <label class="{{ $frame->getSettingLabelClass() }}">
                        公開開始日時
                    </label>

                    <div class="{{ $frame->getSettingInputClass() }}">
                        <input type="datetime-local"
                               name="publish_start_at"
                               value="{{ old(
                                   'publish_start_at',
                                   !empty($quiz->publish_start_at)
                                       ? \Carbon\Carbon::parse($quiz->publish_start_at)->format('Y-m-d\TH:i')
                                       : ''
                               ) }}"
                               class="form-control @if ($errors->has('publish_start_at')) border-danger @endif">

                        @include('plugins.common.errors_inline', [
                            'name' => 'publish_start_at'
                        ])
                    </div>
                </div>

                {{-- 公開終了日時 --}}
                <div class="form-group row">
                    <label class="{{ $frame->getSettingLabelClass() }}">
                        公開終了日時
                    </label>

                    <div class="{{ $frame->getSettingInputClass() }}">
                        <input type="datetime-local"
                               name="publish_end_at"
                               value="{{ old(
                                   'publish_end_at',
                                   !empty($quiz->publish_end_at)
                                       ? \Carbon\Carbon::parse($quiz->publish_end_at)->format('Y-m-d\TH:i')
                                       : ''
                               ) }}"
                               class="form-control @if ($errors->has('publish_end_at')) border-danger @endif">

                        @include('plugins.common.errors_inline', [
                            'name' => 'publish_end_at'
                        ])
                    </div>
                </div>

                {{-- 所要時間の目安 --}}
                <div class="form-group row">
                    <label class="{{ $frame->getSettingLabelClass() }}">
                        所要時間の目安
                    </label>

                    <div class="{{ $frame->getSettingInputClass() }}">
                        <div class="input-group">
                            <input type="number"
                                   name="estimated_minutes"
                                   value="{{ old('estimated_minutes', $quiz->estimated_minutes) }}"
                                   min="1"
                                   class="form-control @if ($errors->has('estimated_minutes')) border-danger @endif">

                            <div class="input-group-append">
                                <span class="input-group-text">分</span>
                            </div>
                        </div>

                        @include('plugins.common.errors_inline', [
                            'name' => 'estimated_minutes'
                        ])
                    </div>
                </div>

                {{-- 制限時間 --}}
                <div class="form-group row">
                    <label class="{{ $frame->getSettingLabelClass() }}">
                        制限時間
                    </label>

                    <div class="{{ $frame->getSettingInputClass() }}">
                        <div class="input-group">
                            <input type="number"
                                   name="time_limit_minutes"
                                   value="{{ old('time_limit_minutes', $quiz->time_limit_minutes) }}"
                                   min="1"
                                   class="form-control @if ($errors->has('time_limit_minutes')) border-danger @endif">

                            <div class="input-group-append">
                                <span class="input-group-text">分</span>
                            </div>
                        </div>

                        <small class="form-text text-muted">
                            空欄の場合、制限時間を設けません。
                        </small>

                        @include('plugins.common.errors_inline', [
                            'name' => 'time_limit_minutes'
                        ])
                    </div>
                </div>

                {{-- 再受験設定 --}}
                <div class="form-group row">
                    <label class="{{ $frame->getSettingLabelClass() }}">
                        再受験設定
                    </label>

                    <div class="{{ $frame->getSettingInputClass() }}">

                        <div class="custom-control custom-radio">
                            <input type="radio"
                                   id="retry_once"
                                   name="retry_type"
                                   value="once"
                                   class="custom-control-input"
                                   @if (old('retry_type', $quiz->retry_type) === 'once') checked @endif>

                            <label class="custom-control-label"
                                   for="retry_once">
                                1回のみ
                            </label>
                        </div>

                        <div class="custom-control custom-radio">
                            <input type="radio"
                                   id="retry_limited"
                                   name="retry_type"
                                   value="limited"
                                   class="custom-control-input"
                                   @if (old('retry_type', $quiz->retry_type) === 'limited') checked @endif>

                            <label class="custom-control-label"
                                   for="retry_limited">
                                回数を指定する
                            </label>
                        </div>

                        <div class="custom-control custom-radio">
                            <input type="radio"
                                   id="retry_unlimited"
                                   name="retry_type"
                                   value="unlimited"
                                   class="custom-control-input"
                                   @if (old('retry_type', $quiz->retry_type) === 'unlimited') checked @endif>

                            <label class="custom-control-label"
                                   for="retry_unlimited">
                                回数制限なし
                            </label>
                        </div>

                        @include('plugins.common.errors_inline', [
                            'name' => 'retry_type'
                        ])
                    </div>
                </div>

                {{-- 受験可能回数 --}}
                <div class="form-group row">
                    <label class="{{ $frame->getSettingLabelClass() }}">
                        受験可能回数
                    </label>

                    <div class="{{ $frame->getSettingInputClass() }}">
                        <div class="input-group">
                            <input type="number"
                                   name="retry_limit"
                                   value="{{ old('retry_limit', $quiz->retry_limit) }}"
                                   min="1"
                                   class="form-control @if ($errors->has('retry_limit')) border-danger @endif">

                            <div class="input-group-append">
                                <span class="input-group-text">回</span>
                            </div>
                        </div>

                        <small class="form-text text-muted">
                            「回数を指定する」を選択した場合に使用します。
                        </small>

                        @include('plugins.common.errors_inline', [
                            'name' => 'retry_limit'
                        ])
                    </div>
                </div>

                {{-- 合格判定方式 --}}
                <div class="form-group row">
                    <label class="{{ $frame->getSettingLabelClass() }}">
                        合格判定
                    </label>

                    <div class="{{ $frame->getSettingInputClass() }}">

                        <div class="custom-control custom-radio">
                            <input type="radio"
                                   id="passing_none"
                                   name="passing_type"
                                   value="none"
                                   class="custom-control-input"
                                   @if (old('passing_type', $quiz->passing_type) === 'none') checked @endif>

                            <label class="custom-control-label"
                                   for="passing_none">
                                合否判定を行わない
                            </label>
                        </div>

                        <div class="custom-control custom-radio">
                            <input type="radio"
                                   id="passing_score"
                                   name="passing_type"
                                   value="score"
                                   class="custom-control-input"
                                   @if (old('passing_type', $quiz->passing_type) === 'score') checked @endif>

                            <label class="custom-control-label"
                                   for="passing_score">
                                合格点で判定する
                            </label>
                        </div>

                        <div class="custom-control custom-radio">
                            <input type="radio"
                                   id="passing_rate"
                                   name="passing_type"
                                   value="rate"
                                   class="custom-control-input"
                                   @if (old('passing_type', $quiz->passing_type) === 'rate') checked @endif>

                            <label class="custom-control-label"
                                   for="passing_rate">
                                正答率で判定する
                            </label>
                        </div>

                        @include('plugins.common.errors_inline', [
                            'name' => 'passing_type'
                        ])
                    </div>
                </div>

                {{-- 合格点 --}}
                <div class="form-group row">
                    <label class="{{ $frame->getSettingLabelClass() }}">
                        合格点
                    </label>

                    <div class="{{ $frame->getSettingInputClass() }}">
                        <div class="input-group">
                            <input type="number"
                                   name="passing_score"
                                   value="{{ old('passing_score', $quiz->passing_score) }}"
                                   min="0"
                                   step="0.01"
                                   class="form-control @if ($errors->has('passing_score')) border-danger @endif">

                            <div class="input-group-append">
                                <span class="input-group-text">点</span>
                            </div>
                        </div>

                        @include('plugins.common.errors_inline', [
                            'name' => 'passing_score'
                        ])
                    </div>
                </div>

                {{-- 合格率 --}}
                <div class="form-group row">
                    <label class="{{ $frame->getSettingLabelClass() }}">
                        合格率
                    </label>

                    <div class="{{ $frame->getSettingInputClass() }}">
                        <div class="input-group">
                            <input type="number"
                                   name="passing_rate"
                                   value="{{ old('passing_rate', $quiz->passing_rate) }}"
                                   min="0"
                                   max="100"
                                   step="0.01"
                                   class="form-control @if ($errors->has('passing_rate')) border-danger @endif">

                            <div class="input-group-append">
                                <span class="input-group-text">％</span>
                            </div>
                        </div>

                        @include('plugins.common.errors_inline', [
                            'name' => 'passing_rate'
                        ])
                    </div>
                </div>

                {{-- 結果表示時期 --}}
                <div class="form-group row">
                    <label class="{{ $frame->getSettingLabelClass() }}">
                        結果表示時期
                    </label>

                    <div class="{{ $frame->getSettingInputClass() }}">

                        <select name="result_display_timing"
                                class="form-control @if ($errors->has('result_display_timing')) border-danger @endif">

                            <option value="immediately"
                                @if (old('result_display_timing', $quiz->result_display_timing) === 'immediately') selected @endif>
                                提出直後
                            </option>

                            <option value="after_grading"
                                @if (old('result_display_timing', $quiz->result_display_timing) === 'after_grading') selected @endif>
                                採点完了後
                            </option>

                            <option value="manual"
                                @if (old('result_display_timing', $quiz->result_display_timing) === 'manual') selected @endif>
                                管理者が公開した後
                            </option>

                        </select>

                        @include('plugins.common.errors_inline', [
                            'name' => 'result_display_timing'
                        ])
                    </div>
                </div>

                {{-- 結果表示項目 --}}
                <div class="form-group row">
                    <label class="{{ $frame->getSettingLabelClass(true) }}">
                        結果に表示する内容
                    </label>

                    <div class="{{ $frame->getSettingInputClass(true) }}">

                        @php
                            $result_items = [
                                'show_score' => '得点',
                                'show_pass_status' => '合否',
                                'show_question_result' => '問題ごとの正誤',
                                'show_user_answer' => '受験者の解答',
                                'show_average_score' => '採点済み受験者の平均点',
                                'show_highest_score' => '採点済み受験者の最高得点',
                                'show_lowest_score' => '採点済み受験者の最低得点',
                                'show_participant_count' => '集計人数',
                                'show_score_distribution' => '得点分布と本人位置',
                                'show_correct_answer' => '正解',
                                'show_commentary' => '解説',
                                'show_grading_comment' => '採点コメント',
                            ];
                        @endphp

                        @foreach ($result_items as $name => $label)
                            <div class="border rounded mb-2"
                                 style="display: block; width: 100%; box-sizing: border-box;">
                                <label for="{{ $name }}"
                                       class="mb-0"
                                       style="display: flex; align-items: center; width: 100%; padding: 0.75rem 1rem; cursor: pointer;">
                                    <input type="checkbox"
                                           id="{{ $name }}"
                                           name="{{ $name }}"
                                           value="1"
                                           style="position: static; margin: 0 0.75rem 0 0; flex: 0 0 auto;"
                                           @if (old($name, $quiz->{$name})) checked @endif>
                                    <span>{{ $label }}</span>
                                </label>
                            </div>
                        @endforeach

                    </div>
                </div>

                {{-- 操作ボタン --}}
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