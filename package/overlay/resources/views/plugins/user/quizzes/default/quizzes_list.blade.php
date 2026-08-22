@extends('core.cms_frame_base_setting')

@section("core.cms_frame_edit_tab_$frame->id")
    @include('plugins.user.quizzes.quizzes_frame_edit_tab')
@endsection

@section("plugin_setting_$frame->id")
    @php
    $quizzes = $quizzes ?? collect();
    $selected_quiz_id = $selected_quiz_id ?? null;
@endphp

<div class="card">

    <div class="card-header">
        小テスト選択
    </div>

    <div class="card-body">

        <p class="text-muted mb-2">
            このフレームで使用する小テストを選択してください。
        </p>
        <ul class="small text-muted mb-4">
            <li>「そのまま使用」では、同じ小テストを複数フレームで共有します。編集内容も共有されます。</li>
            <li>「複製して使用」では、受験履歴を除く小テスト一式を複製し、このフレーム専用にします。</li>
        </ul>

        @if ($quizzes->isEmpty())

            <div class="alert alert-secondary mb-0">
                使用できる小テストはありません。
            </div>

        @else

            <form action="{{ url('/redirect/plugin/quizzes/selectQuiz/'
                . $page_id . '/'
                . $frame_id) }}"
                  method="POST">

                @csrf

                <input type="hidden"
                       name="normal_page_path"
                       value="{{ URL::to($page->permanent_link) }}#frame-{{ $frame->id }}">

                <div class="table-responsive">
                    <table class="table table-bordered table-hover">

                        <thead class="thead-light">
                            <tr>
                                <th style="width: 60px;" class="text-center">
                                    選択
                                </th>
                                <th>
                                    小テスト名
                                </th>
                                <th style="width: 120px;">
                                    公開状態
                                </th>
                                <th style="width: 160px;">
                                    更新日時
                                </th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($quizzes as $quiz)
                                <tr @if ((int) $selected_quiz_id === (int) $quiz->id)
                                        class="table-info"
                                    @endif>

                                    <td class="text-center align-middle">
                                        <input type="radio"
                                               name="quiz_id"
                                               value="{{ $quiz->id }}"
                                               id="quiz_id_{{ $quiz->id }}"
                                               @if ((int) old(
                                                   'quiz_id',
                                                   $selected_quiz_id
                                               ) === (int) $quiz->id)
                                                   checked
                                               @endif>
                                    </td>

                                    <td class="align-middle">
                                        <label for="quiz_id_{{ $quiz->id }}"
                                               class="mb-0">

                                            <span class="font-weight-bold">
                                                {{ $quiz->title }}
                                            </span>

                                            @if ((int) $selected_quiz_id === (int) $quiz->id)
                                                <span class="badge badge-primary ml-1">
                                                    使用中
                                                </span>
                                            @endif

                                            @if (($quiz->frames_count ?? 0) > 1)
                                                <span class="badge badge-warning ml-1">
                                                    {{ $quiz->frames_count }}フレームで共有
                                                </span>
                                            @elseif (($quiz->frames_count ?? 0) === 1)
                                                <span class="badge badge-light border ml-1">
                                                    1フレームで使用
                                                </span>
                                            @endif

                                            @if (!empty($quiz->description))
                                                <div class="small text-muted mt-1">
                                                    {{ \Illuminate\Support\Str::limit(
                                                        strip_tags($quiz->description),
                                                        100
                                                    ) }}
                                                </div>
                                            @endif
                                        </label>
                                    </td>

                                    <td class="align-middle">
                                        @if ($quiz->status === 'published')
                                            <span class="badge badge-success">
                                                公開
                                            </span>
                                        @elseif ($quiz->status === 'limited')
                                            <span class="badge badge-warning">
                                                ログイン限定
                                            </span>
                                        @else
                                            <span class="badge badge-secondary">
                                                非公開
                                            </span>
                                        @endif
                                    </td>

                                    <td class="align-middle">
                                        @if (!empty($quiz->updated_at))
                                            {{ $quiz->updated_at->format('Y/m/d H:i') }}
                                        @endif
                                    </td>

                                </tr>
                            @endforeach
                        </tbody>

                    </table>
                </div>

                @error('quiz_id')
                    <div class="alert alert-danger">
                        {{ $message }}
                    </div>
                @enderror

                @error('selection_mode')
                    <div class="alert alert-danger">
                        {{ $message }}
                    </div>
                @enderror

                <div class="text-center mt-3">
                    <button type="submit"
                            name="selection_mode"
                            value="share"
                            class="btn btn-outline-primary mr-2">
                        <i class="fas fa-link"></i>
                        そのまま使用
                    </button>

                    <button type="submit"
                            name="selection_mode"
                            value="copy"
                            class="btn btn-primary">
                        <i class="fas fa-copy"></i>
                        複製して使用
                    </button>
                </div>

            </form>

        @endif

    </div>
</div>

@endsection
