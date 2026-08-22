@extends('core.cms_frame_base_setting')

@section("core.cms_frame_edit_tab_$frame->id")
    @include('plugins.user.quizzes.quizzes_frame_edit_tab')
@endsection

@section("plugin_setting_$frame->id")

@include('plugins.common.errors_form_line')

@if (session('quizzes_category_success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle"></i>
        {{ session('quizzes_category_success') }}
        <button type="button"
                class="close"
                data-dismiss="alert"
                aria-label="閉じる">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif

<div class="card">
    <div class="card-header">
        カテゴリー管理
    </div>

    <div class="card-body">
        <div class="mb-3">
            <div><strong>小テスト：</strong>{{ $quiz->title }}</div>
            <div class="mt-2">
                未設定問題：
                @if ($unassigned_question_count > 0)
                    <span class="badge badge-warning">{{ $unassigned_question_count }}問</span>
                @else
                    <span class="badge badge-success">0問</span>
                @endif
            </div>
            <small class="form-text text-muted">
                未設定問題も総合点には含まれますが、カテゴリー集計には含まれません。
            </small>
        </div>

        <form action="{{ url('/') }}/redirect/plugin/quizzes/saveCategories/{{ $page->id }}/{{ $frame->id }}/{{ $quiz->id }}#frame-{{ $frame->id }}"
              method="POST"
              id="quiz-category-form">
            @csrf

            <input type="hidden"
                   name="back_path"
                   value="{{ url('/') }}/plugin/quizzes/manageCategories/{{ $page->id }}/{{ $frame->id }}/{{ $quiz->id }}#frame-{{ $frame->id }}">

            <div id="category-groups">
                @foreach ($category_groups as $group_index => $group)
                    <div class="card mb-3 category-group" data-group-index="{{ $group_index }}">
                        <div class="card-header">
                            カテゴリーグループ
                        </div>
                        <div class="card-body">
                            <input type="hidden"
                                   name="groups[{{ $group_index }}][id]"
                                   value="{{ $group->id }}">

                            <div class="form-row align-items-end">
                                <div class="form-group col-md-6">
                                    <label>グループ名</label>
                                    <input type="text"
                                           name="groups[{{ $group_index }}][name]"
                                           value="{{ old("groups.$group_index.name", $group->name) }}"
                                           maxlength="255"
                                           required
                                           class="form-control">
                                </div>
                                <div class="form-group col-md-2">
                                    <label>表示順</label>
                                    <input type="number"
                                           name="groups[{{ $group_index }}][sequence]"
                                           value="{{ old("groups.$group_index.sequence", $group->sequence) }}"
                                           min="1"
                                           required
                                           class="form-control">
                                </div>
                                <div class="form-group col-md-4">
                                    <div class="custom-control custom-checkbox mb-2">
                                        <input type="checkbox"
                                               class="custom-control-input"
                                               id="group-active-{{ $group_index }}"
                                               name="groups[{{ $group_index }}][is_active]"
                                               value="1"
                                               @if (old("groups.$group_index.is_active", $group->is_active)) checked @endif>
                                        <label class="custom-control-label"
                                               for="group-active-{{ $group_index }}">
                                            使用する
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="categories">
                                @foreach ($group->categories as $category_index => $category)
                                    <div class="form-row align-items-end category-row">
                                        <input type="hidden"
                                               name="groups[{{ $group_index }}][categories][{{ $category_index }}][id]"
                                               value="{{ $category->id }}">
                                        <div class="form-group col-md-6">
                                            <label>カテゴリー項目</label>
                                            <input type="text"
                                                   name="groups[{{ $group_index }}][categories][{{ $category_index }}][name]"
                                                   value="{{ old("groups.$group_index.categories.$category_index.name", $category->name) }}"
                                                   maxlength="255"
                                                   required
                                                   class="form-control">
                                        </div>
                                        <div class="form-group col-md-2">
                                            <label>表示順</label>
                                            <input type="number"
                                                   name="groups[{{ $group_index }}][categories][{{ $category_index }}][sequence]"
                                                   value="{{ old("groups.$group_index.categories.$category_index.sequence", $category->sequence) }}"
                                                   min="1"
                                                   required
                                                   class="form-control">
                                        </div>
                                        <div class="form-group col-md-4">
                                            <div class="custom-control custom-checkbox mb-2">
                                                <input type="checkbox"
                                                       class="custom-control-input"
                                                       id="category-active-{{ $group_index }}-{{ $category_index }}"
                                                       name="groups[{{ $group_index }}][categories][{{ $category_index }}][is_active]"
                                                       value="1"
                                                       @if (old("groups.$group_index.categories.$category_index.is_active", $category->is_active)) checked @endif>
                                                <label class="custom-control-label"
                                                       for="category-active-{{ $group_index }}-{{ $category_index }}">
                                                    使用する
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <button type="button"
                                    class="btn btn-sm btn-outline-primary add-category">
                                <i class="fas fa-plus"></i> カテゴリー項目を追加
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>

            <button type="button"
                    id="add-category-group"
                    class="btn btn-outline-primary mb-4">
                <i class="fas fa-plus"></i> カテゴリーグループを追加
            </button>

            <div class="alert alert-info">
                使用中のグループ・項目は削除せず、「使用する」のチェックを外して無効化します。
                過去の問題割当は保持されます。
            </div>

            <div class="text-center">
                <button type="button"
                        class="btn btn-secondary mr-2"
                        onclick="location.href='{{ url('/') }}/plugin/quizzes/editQuizSettings/{{ $page->id }}/{{ $frame->id }}/{{ $quiz->id }}#frame-{{ $frame->id }}'">
                    キャンセル
                </button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> 保存
                </button>
            </div>
        </form>
    </div>
</div>

<template id="category-group-template">
    <div class="card mb-3 category-group">
        <div class="card-header">カテゴリーグループ</div>
        <div class="card-body">
            <div class="form-row align-items-end">
                <div class="form-group col-md-6">
                    <label>グループ名</label>
                    <input type="text" data-field="group-name" maxlength="255" required class="form-control">
                </div>
                <div class="form-group col-md-2">
                    <label>表示順</label>
                    <input type="number" data-field="group-sequence" min="1" required class="form-control">
                </div>
                <div class="form-group col-md-4">
                    <div class="custom-control custom-checkbox mb-2">
                        <input type="checkbox" data-field="group-active" value="1" checked class="custom-control-input">
                        <label data-field="group-active-label" class="custom-control-label">使用する</label>
                    </div>
                </div>
            </div>
            <div class="categories"></div>
            <button type="button" class="btn btn-sm btn-outline-primary add-category">
                <i class="fas fa-plus"></i> カテゴリー項目を追加
            </button>
        </div>
    </div>
</template>

<template id="category-row-template">
    <div class="form-row align-items-end category-row">
        <div class="form-group col-md-6">
            <label>カテゴリー項目</label>
            <input type="text" data-field="category-name" maxlength="255" required class="form-control">
        </div>
        <div class="form-group col-md-2">
            <label>表示順</label>
            <input type="number" data-field="category-sequence" min="1" required class="form-control">
        </div>
        <div class="form-group col-md-4">
            <div class="custom-control custom-checkbox mb-2">
                <input type="checkbox" data-field="category-active" value="1" checked class="custom-control-input">
                <label data-field="category-active-label" class="custom-control-label">使用する</label>
            </div>
        </div>
    </div>
</template>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const groups = document.getElementById('category-groups');
    const groupTemplate = document.getElementById('category-group-template');
    const categoryTemplate = document.getElementById('category-row-template');

    function addCategory(group) {
        const groupIndex = group.dataset.groupIndex;
        const container = group.querySelector('.categories');
        const categoryIndex = container.querySelectorAll('.category-row').length;
        const row = categoryTemplate.content.firstElementChild.cloneNode(true);
        const prefix = 'groups[' + groupIndex + '][categories][' + categoryIndex + ']';
        const activeId = 'category-active-' + groupIndex + '-' + categoryIndex;

        row.querySelector('[data-field="category-name"]').name = prefix + '[name]';
        row.querySelector('[data-field="category-sequence"]').name = prefix + '[sequence]';
        row.querySelector('[data-field="category-sequence"]').value = categoryIndex + 1;
        row.querySelector('[data-field="category-active"]').name = prefix + '[is_active]';
        row.querySelector('[data-field="category-active"]').id = activeId;
        row.querySelector('[data-field="category-active-label"]').htmlFor = activeId;
        container.appendChild(row);
    }

    function bindGroup(group) {
        group.querySelector('.add-category').addEventListener('click', function () {
            addCategory(group);
        });
    }

    groups.querySelectorAll('.category-group').forEach(bindGroup);

    document.getElementById('add-category-group').addEventListener('click', function () {
        const groupIndex = groups.querySelectorAll('.category-group').length;
        const group = groupTemplate.content.firstElementChild.cloneNode(true);
        const activeId = 'group-active-' + groupIndex;

        group.dataset.groupIndex = groupIndex;
        group.querySelector('[data-field="group-name"]').name = 'groups[' + groupIndex + '][name]';
        group.querySelector('[data-field="group-sequence"]').name = 'groups[' + groupIndex + '][sequence]';
        group.querySelector('[data-field="group-sequence"]').value = groupIndex + 1;
        group.querySelector('[data-field="group-active"]').name = 'groups[' + groupIndex + '][is_active]';
        group.querySelector('[data-field="group-active"]').id = activeId;
        group.querySelector('[data-field="group-active-label"]').htmlFor = activeId;

        groups.appendChild(group);
        bindGroup(group);
        addCategory(group);
    });
});
</script>

@endsection
