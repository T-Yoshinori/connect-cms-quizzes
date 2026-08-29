@extends('core.cms_frame_base')

@section("plugin_contents_$frame->id")

@include('plugins.common.errors_form_line')

{{-- 問題文・解説用WYSIWYG（同一画面に複数あるため固有クラスで初期化） --}}
@include('plugins.user.quizzes.default.quizzes_mathjax', ['math_editor_tools' => true])
@include('plugins.common.wysiwyg', [
    'target_class' => 'wysiwyg-question-' . $frame->id,
    'use_mathjax' => true,
])
@include('plugins.common.wysiwyg', [
    'target_class' => 'wysiwyg-commentary-' . $frame->id,
    'use_mathjax' => true,
])

@php
    $selectedType = old('question_type', optional($revision)->question_type ?? 'single_choice');
    $oldChoices = old('choices');
    if (is_null($oldChoices)) {
        $oldChoices = !empty($revision)
            ? $revision->choices->map(function ($choice) {
                return [
                    'text' => $choice->label,
                    'is_correct' => $choice->is_correct ? 1 : 0,
                ];
            })->values()->all()
            : [
                ['text' => '', 'is_correct' => 1],
                ['text' => '', 'is_correct' => 0],
            ];
    }

    $oldCorrectAnswers = old('correct_answers');
    if (is_null($oldCorrectAnswers)) {
        $oldCorrectAnswers = !empty($revision)
            ? $revision->correct_answers->map(function ($answer) {
                return [
                    'answer_group' => $answer->answer_group,
                    'answer_text' => $answer->answer_text,
                    'sequence' => $answer->sequence,
                ];
            })->values()->all()
            : [
                ['answer_group' => 1, 'answer_text' => '', 'sequence' => 1],
            ];
    }

    $selectedCategoryIds = old('category_ids');
    if (is_null($selectedCategoryIds)) {
        $selectedCategoryIds = !empty($revision)
            ? $revision->categories->pluck('id')->map(function ($id) {
                return (int) $id;
            })->all()
            : [];
    }
    $selectedCategoryIds = array_map('intval', (array) $selectedCategoryIds);

    $answerOrderFixed = (bool) old(
        'answer_order_fixed',
        is_null(optional($revision)->answer_order_fixed)
            ? true
            : optional($revision)->answer_order_fixed
    );

    $normalPagePath = URL::to($page->permanent_link) . '#frame-' . $frame->id;
@endphp

<form action="{{ url('/') }}/redirect/plugin/quizzes/saveQuestion/{{ $page->id }}/{{ $frame->id }}@if (!$is_create)/{{ $question->id }}@endif#frame-{{ $frame->id }}"
      method="POST"
      id="quiz-question-form">

    @csrf

    <input type="hidden" name="quiz_id" value="{{ $quiz->id }}">
    <input type="hidden" name="quiz_page_id" value="{{ $quiz_page->id }}">
    <input type="hidden" name="normal_page_path" value="{{ $normalPagePath }}">

    <div class="card">
        <div class="card-header">
            {{ $is_create ? '問題作成' : '問題編集' }}
        </div>

        <div class="card-body">
            <div class="form-group row">
                <label class="col-md-3 col-form-label text-md-right">小テスト名</label>
                <div class="col-md-9">
                    <p class="form-control-plaintext mb-0">{{ $quiz->title }}</p>
                </div>
            </div>

            <div class="form-group row">
                <label class="col-md-3 col-form-label text-md-right">出題ページ</label>
                <div class="col-md-9">
                    <p class="form-control-plaintext mb-0">
                        {{ !empty($quiz_page->title) ? $quiz_page->title : '出題ページ' . ((int) $quiz_page->sequence + 1) }}
                    </p>
                </div>
            </div>

            <hr>

            <div class="form-group row">
                <label for="points" class="col-md-3 col-form-label text-md-right">
                    配点 <span class="badge badge-danger">必須</span>
                </label>
                <div class="col-md-3">
                    <div class="input-group">
                        <input type="number"
                               id="points"
                               name="points"
                               value="{{ old('points', optional($revision)->points ?? 10) }}"
                               min="0"
                               step="0.01"
                               class="form-control @if ($errors->has('points')) border-danger @endif"
                               required>
                        <div class="input-group-append">
                            <span class="input-group-text">点</span>
                        </div>
                    </div>
                    @include('plugins.common.errors_inline', ['name' => 'points'])
                </div>
            </div>

            <div class="form-group row">
                <label for="question_text" class="col-md-3 col-form-label text-md-right">
                    問題文 <span class="badge badge-danger">必須</span>
                </label>
                <div class="col-md-9">
                    <textarea id="question_text"
                              name="question_text"
                              rows="8"
                              class="form-control wysiwyg-question-{{ $frame->id }} @if ($errors->has('question_text')) border-danger @endif">{!! old('question_text', optional($revision)->question_text) !!}</textarea>
                    @include('plugins.common.errors_inline', ['name' => 'question_text'])
                </div>
            </div>

            <div class="form-group row">
                <label for="question_type" class="col-md-3 col-form-label text-md-right">
                    問題形式 <span class="badge badge-danger">必須</span>
                </label>
                <div class="col-md-5">
                    <select id="question_type"
                            name="question_type"
                            class="form-control @if ($errors->has('question_type')) border-danger @endif"
                            required>
                        <option value="single_choice" @if ($selectedType === 'single_choice') selected @endif>単一選択</option>
                        <option value="multiple_choice" @if ($selectedType === 'multiple_choice') selected @endif>複数選択</option>
                        <option value="word" @if ($selectedType === 'word') selected @endif>単語入力</option>
                        <option value="multiple_word" @if ($selectedType === 'multiple_word') selected @endif>複数単語入力</option>
                        <option value="essay" @if ($selectedType === 'essay') selected @endif>記述式</option>
                    </select>
                    @include('plugins.common.errors_inline', ['name' => 'question_type'])
                </div>
            </div>

            <div id="choice-options" class="question-option-panel border rounded p-3 mb-4">
                <h4 class="h6">選択肢</h4>
                <p class="small text-muted">
                    正解欄を選択してください。単一選択では正解は1件、複数選択では1件以上指定します。
                </p>

                <div id="choice-list">
                    @foreach ($oldChoices as $choiceIndex => $choice)
                        <div class="choice-row form-row align-items-center mb-2">
                            <div class="col-auto">
                                <div class="form-check">
                                    <input type="checkbox"
                                           name="choices[{{ $choiceIndex }}][is_correct]"
                                           value="1"
                                           class="form-check-input choice-correct"
                                           @if (!empty($choice['is_correct'])) checked @endif>
                                    <label class="form-check-label">正解</label>
                                </div>
                            </div>
                            <div class="col">
                                <input type="text"
                                       name="choices[{{ $choiceIndex }}][text]"
                                       value="{{ $choice['text'] ?? '' }}"
                                       class="form-control"
                                       placeholder="選択肢を入力">
                            </div>
                            <div class="col-auto">
                                <button type="button" class="btn btn-sm btn-outline-danger remove-choice">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>

                @include('plugins.common.errors_inline', ['name' => 'choices'])

                <button type="button" id="add-choice" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-plus"></i>
                    選択肢を追加
                </button>

                <div class="form-check mt-3">
                    <input type="hidden" name="choice_random" value="0">
                    <input type="checkbox"
                           id="choice_random"
                           name="choice_random"
                           value="1"
                           class="form-check-input"
                           @if (old('choice_random', optional($revision)->choice_random)) checked @endif>
                    <label for="choice_random" class="form-check-label">受験時に選択肢の順番をランダムにする</label>
                </div>
            </div>

            <div id="word-options" class="question-option-panel border rounded p-3 mb-4">
                <h4 class="h6">正解候補</h4>
                <p class="small text-muted">
                    表記違いを正解とする場合は、正解候補を追加します。複数単語入力では回答欄番号を指定します。
                </p>

                <div id="correct-answer-list">
                    @foreach ($oldCorrectAnswers as $answerIndex => $answer)
                        <div class="correct-answer-row form-row align-items-center mb-2">
                            <div class="col-md-2 answer-group-column">
                                <input type="number"
                                       name="correct_answers[{{ $answerIndex }}][answer_group]"
                                       value="{{ $answer['answer_group'] ?? 1 }}"
                                       min="1"
                                       class="form-control"
                                       aria-label="回答欄番号">
                            </div>
                            <div class="col">
                                <input type="text"
                                       name="correct_answers[{{ $answerIndex }}][answer_text]"
                                       value="{{ $answer['answer_text'] ?? '' }}"
                                       class="form-control"
                                       placeholder="正解候補を入力">
                                <input type="hidden"
                                       name="correct_answers[{{ $answerIndex }}][sequence]"
                                       value="{{ $answer['sequence'] ?? ($answerIndex + 1) }}">
                            </div>
                            <div class="col-auto">
                                <button type="button" class="btn btn-sm btn-outline-danger remove-correct-answer">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>

                @include('plugins.common.errors_inline', ['name' => 'correct_answers'])

                <button type="button" id="add-correct-answer" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-plus"></i>
                    正解候補を追加
                </button>

                <div id="multiple-word-scoring-options" class="mt-4">
                    <h5 class="h6">解答順の判定</h5>
                    <div class="form-check mb-2">
                        <input class="form-check-input"
                               type="radio"
                               id="answer_order_fixed_1"
                               name="answer_order_fixed"
                               value="1"
                               @if ($answerOrderFixed) checked @endif>
                        <label class="form-check-label" for="answer_order_fixed_1">
                            解答欄ごとに正解を判定する
                        </label>
                        <small class="form-text text-muted">
                            空欄補充など、解答位置に意味がある問題で使用します。
                        </small>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input"
                               type="radio"
                               id="answer_order_fixed_0"
                               name="answer_order_fixed"
                               value="0"
                               @if (!$answerOrderFixed) checked @endif>
                        <label class="form-check-label" for="answer_order_fixed_0">
                            解答の順序を問わない
                        </label>
                        <small class="form-text text-muted">
                            名称や項目を複数列挙する問題で使用します。同じ正解は重複して使用されません。
                        </small>
                    </div>
                </div>
            </div>

            <div id="essay-options" class="question-option-panel border rounded p-3 mb-4">
                <h4 class="h6">記述式の設定</h4>

                <div class="form-group row mb-2">
                    <label for="answer_rows" class="col-md-4 col-form-label">回答欄の行数</label>
                    <div class="col-md-4">
                        <input type="number"
                               id="answer_rows"
                               name="answer_rows"
                               value="{{ old('answer_rows', optional($revision)->answer_rows ?? 5) }}"
                               min="1"
                               max="100"
                               class="form-control">
                    </div>
                </div>

                <div class="form-group row mb-0">
                    <label for="character_limit" class="col-md-4 col-form-label">文字数上限</label>
                    <div class="col-md-4">
                        <input type="number"
                               id="character_limit"
                               name="character_limit"
                               value="{{ old('character_limit', optional($revision)->character_limit) }}"
                               min="1"
                               class="form-control">
                    </div>
                    <div class="col-md-4">
                        <small class="form-text text-muted">空欄の場合は制限しません。</small>
                    </div>
                </div>

                <hr>

                <div class="form-group row mb-2">
                    <label for="model_answer" class="col-md-4 col-form-label">模範解答</label>
                    <div class="col-md-8">
                        <textarea id="model_answer"
                                  name="model_answer"
                                  rows="5"
                                  class="form-control @if ($errors->has('model_answer')) border-danger @endif">{{ old('model_answer', optional($revision)->model_answer) }}</textarea>
                        <small class="form-text text-muted">記述式の採点や採点基準作成支援に使用します。</small>
                        @include('plugins.common.errors_inline', ['name' => 'model_answer'])
                    </div>
                </div>

                <div class="form-group row mb-0">
                    <label for="grading_guide" class="col-md-4 col-form-label">採点基準</label>
                    <div class="col-md-8">
                        <textarea id="grading_guide"
                                  name="grading_guide"
                                  rows="5"
                                  class="form-control @if ($errors->has('grading_guide')) border-danger @endif">{{ old('grading_guide', optional($revision)->grading_guide) }}</textarea>
                        <small class="form-text text-muted">管理者用。受験者には表示しません。</small>
                        @include('plugins.common.errors_inline', ['name' => 'grading_guide'])
                    </div>
                </div>
            </div>

            @if ($quiz->use_category_scoring)
                <hr>

                <input type="hidden" name="category_assignment_present" value="1">

                <div class="form-group row">
                    <label class="col-md-3 col-form-label text-md-right">
                        カテゴリー
                    </label>
                    <div class="col-md-9">
                        @if ($category_groups->isEmpty())
                            <div class="alert alert-warning mb-0">
                                カテゴリー項目がありません。
                                <a href="{{ url('/') }}/plugin/quizzes/manageCategories/{{ $page->id }}/{{ $frame->id }}/{{ $quiz->id }}#frame-{{ $frame->id }}">
                                    カテゴリー管理
                                </a>
                                で作成してください。
                            </div>
                        @else
                            @foreach ($category_groups as $group)
                                <fieldset class="border rounded p-3 mb-3">
                                    <legend class="w-auto px-2 mb-0" style="font-size: 1rem;">
                                        {{ $group->name }}
                                    </legend>
                                    @forelse ($group->active_categories as $category)
                                        <div class="custom-control custom-checkbox mb-2">
                                            <input type="checkbox"
                                                   class="custom-control-input"
                                                   id="question-category-{{ $category->id }}"
                                                   name="category_ids[]"
                                                   value="{{ $category->id }}"
                                                   @if (in_array((int) $category->id, $selectedCategoryIds, true)) checked @endif>
                                            <label class="custom-control-label"
                                                   for="question-category-{{ $category->id }}">
                                                {{ $category->name }}
                                            </label>
                                        </div>
                                    @empty
                                        <span class="text-muted">使用できるカテゴリー項目がありません。</span>
                                    @endforelse
                                </fieldset>
                            @endforeach
                            <small class="form-text text-muted">
                                複数のグループ・項目を選択できます。同じ問題を複数項目に割り当てた場合、
                                各項目へ得点と配点を分割せず計上します。
                            </small>
                        @endif
                        @include('plugins.common.errors_inline', ['name' => 'category_ids'])
                    </div>
                </div>
            @endif

            <hr>

            <div class="form-group row">
                <label for="commentary" class="col-md-3 col-form-label text-md-right">解説</label>
                <div class="col-md-9">
                    <textarea id="commentary"
                              name="commentary"
                              rows="6"
                              class="form-control wysiwyg-commentary-{{ $frame->id }} @if ($errors->has('commentary')) border-danger @endif">{!! old('commentary', optional($revision)->commentary) !!}</textarea>
                    @include('plugins.common.errors_inline', ['name' => 'commentary'])
                </div>
            </div>

            <div class="form-group text-center mb-0">
                <button type="button"
                        class="btn btn-secondary mr-2"
                        onclick="location.href='{{ $normalPagePath }}'">
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

<script>
document.addEventListener('DOMContentLoaded', function () {
    const questionForm = document.getElementById('quiz-question-form');
    const typeSelect = document.getElementById('question_type');

    // 問題文・解説は同一フォーム内の別TinyMCE。
    // 送信直前に両方を元のtextareaへ同期し、LaTeXソースを確実にPOSTする。
    questionForm.addEventListener('submit', function () {
        ['question_text', 'commentary'].forEach(function (editorId) {
            var editor = window.tinymce ? tinymce.get(editorId) : null;
            if (editor) {
                editor.save();
            }
        });
    });
    const choiceOptions = document.getElementById('choice-options');
    const wordOptions = document.getElementById('word-options');
    const multipleWordScoringOptions = document.getElementById('multiple-word-scoring-options');
    const essayOptions = document.getElementById('essay-options');
    const choiceList = document.getElementById('choice-list');
    const answerList = document.getElementById('correct-answer-list');

    function updateOptionPanels() {
        const type = typeSelect.value;
        choiceOptions.style.display = ['single_choice', 'multiple_choice'].includes(type) ? '' : 'none';
        wordOptions.style.display = ['word', 'multiple_word'].includes(type) ? '' : 'none';
        multipleWordScoringOptions.style.display = type === 'multiple_word' ? '' : 'none';
        essayOptions.style.display = type === 'essay' ? '' : 'none';

        document.querySelectorAll('.answer-group-column').forEach(function (column) {
            column.style.display = type === 'multiple_word' ? '' : 'none';
            const input = column.querySelector('input');
            if (type === 'word') {
                input.value = 1;
            }
        });
    }

    function normalizeChoiceNames() {
        choiceList.querySelectorAll('.choice-row').forEach(function (row, index) {
            row.querySelector('.choice-correct').name = `choices[${index}][is_correct]`;
            row.querySelector('input[type="text"]').name = `choices[${index}][text]`;
        });
    }

    function normalizeAnswerNames() {
        answerList.querySelectorAll('.correct-answer-row').forEach(function (row, index) {
            row.querySelector('input[type="number"]').name = `correct_answers[${index}][answer_group]`;
            row.querySelector('input[type="text"]').name = `correct_answers[${index}][answer_text]`;
            const hidden = row.querySelector('input[type="hidden"]');
            hidden.name = `correct_answers[${index}][sequence]`;
            hidden.value = index + 1;
        });
    }

    typeSelect.addEventListener('change', updateOptionPanels);

    choiceList.addEventListener('change', function (event) {
        if (!event.target.classList.contains('choice-correct')) {
            return;
        }
        if (typeSelect.value === 'single_choice' && event.target.checked) {
            choiceList.querySelectorAll('.choice-correct').forEach(function (checkbox) {
                if (checkbox !== event.target) {
                    checkbox.checked = false;
                }
            });
        }
    });

    document.getElementById('add-choice').addEventListener('click', function () {
        const index = choiceList.querySelectorAll('.choice-row').length;
        const row = document.createElement('div');
        row.className = 'choice-row form-row align-items-center mb-2';
        row.innerHTML = `
            <div class="col-auto">
                <div class="form-check">
                    <input type="checkbox" name="choices[${index}][is_correct]" value="1" class="form-check-input choice-correct">
                    <label class="form-check-label">正解</label>
                </div>
            </div>
            <div class="col">
                <input type="text" name="choices[${index}][text]" class="form-control" placeholder="選択肢を入力">
            </div>
            <div class="col-auto">
                <button type="button" class="btn btn-sm btn-outline-danger remove-choice"><i class="fas fa-times"></i></button>
            </div>`;
        choiceList.appendChild(row);
    });

    choiceList.addEventListener('click', function (event) {
        const button = event.target.closest('.remove-choice');
        if (!button) {
            return;
        }
        if (choiceList.querySelectorAll('.choice-row').length <= 2) {
            alert('選択肢は2件以上必要です。');
            return;
        }
        button.closest('.choice-row').remove();
        normalizeChoiceNames();
    });

    document.getElementById('add-correct-answer').addEventListener('click', function () {
        const index = answerList.querySelectorAll('.correct-answer-row').length;
        const row = document.createElement('div');
        row.className = 'correct-answer-row form-row align-items-center mb-2';
        row.innerHTML = `
            <div class="col-md-2 answer-group-column">
                <input type="number" name="correct_answers[${index}][answer_group]" value="1" min="1" class="form-control" aria-label="回答欄番号">
            </div>
            <div class="col">
                <input type="text" name="correct_answers[${index}][answer_text]" class="form-control" placeholder="正解候補を入力">
                <input type="hidden" name="correct_answers[${index}][sequence]" value="${index + 1}">
            </div>
            <div class="col-auto">
                <button type="button" class="btn btn-sm btn-outline-danger remove-correct-answer"><i class="fas fa-times"></i></button>
            </div>`;
        answerList.appendChild(row);
        updateOptionPanels();
    });

    answerList.addEventListener('click', function (event) {
        const button = event.target.closest('.remove-correct-answer');
        if (!button) {
            return;
        }
        if (answerList.querySelectorAll('.correct-answer-row').length <= 1) {
            alert('正解候補は1件以上必要です。');
            return;
        }
        button.closest('.correct-answer-row').remove();
        normalizeAnswerNames();
    });

    updateOptionPanels();
});
</script>

@endsection
