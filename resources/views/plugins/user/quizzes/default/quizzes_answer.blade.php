@include('plugins.user.quizzes.default.quizzes_mathjax')

<form action="{{ url('/') }}/redirect/plugin/quizzes/saveAnswer/{{ $page->id }}/{{ $frame->id }}/{{ $attempt->id }}#frame-{{ $frame->id }}"
      method="POST"
      onsubmit="setTimeout(() => this.querySelectorAll('button[type=submit]').forEach(button => button.disabled = true), 0);">
    {{ csrf_field() }}
    <input type="hidden" name="attempt_id" value="{{ $attempt->id }}">

    <div class="card mb-3">
        <div class="card-header">
            <h2 class="h4 mb-0">{{ $attempt->quiz->title }}</h2>
        </div>
    </div>

    @php $question_number = 0; @endphp
    @foreach ($attempt->attempt_pages as $attempt_page)
        <div class="card mb-4">
            <div class="card-header">
                {{ $attempt_page->title ?: 'ページ' . $loop->iteration }}
            </div>
            <div class="card-body">
                @if ($attempt_page->description)
                    <div class="mb-4">{!! $attempt_page->description !!}</div>
                @endif

                @foreach ($attempt_page->attempt_questions as $attempt_question)
                    @php
                        $question_number++;
                        $revision = $attempt_question->question_revision;
                        $saved = $attempt_question->answer->answer_data ?? [];
                    @endphp

                    <div class="border rounded p-3 mb-3">
                        <input type="hidden"
                               name="answers[{{ $attempt_question->id }}][_present]"
                               value="1">

                        <div class="font-weight-bold mb-2">
                            問{{ $question_number }}（{{ $attempt_question->points }}点）
                        </div>
                        <div class="mb-3">{!! $revision->question_text !!}</div>

                        @if ($revision->question_type === 'single_choice')
                            @foreach ($attempt_question->choices as $choice)
                                <div class="custom-control custom-radio">
                                    <input class="custom-control-input"
                                           id="c{{ $attempt_question->id }}_{{ $choice->id }}"
                                           type="radio"
                                           name="answers[{{ $attempt_question->id }}][attempt_choice_ids][]"
                                           value="{{ $choice->id }}"
                                           @if (in_array($choice->id, $saved['attempt_choice_ids'] ?? ($saved['choice_ids'] ?? []))) checked @endif>
                                    <label class="custom-control-label"
                                           for="c{{ $attempt_question->id }}_{{ $choice->id }}">
                                        {{ $choice->choice_revision->label }}
                                    </label>
                                </div>
                            @endforeach
                        @elseif ($revision->question_type === 'multiple_choice')
                            @foreach ($attempt_question->choices as $choice)
                                <div class="custom-control custom-checkbox">
                                    <input class="custom-control-input"
                                           id="c{{ $attempt_question->id }}_{{ $choice->id }}"
                                           type="checkbox"
                                           name="answers[{{ $attempt_question->id }}][attempt_choice_ids][]"
                                           value="{{ $choice->id }}"
                                           @if (in_array($choice->id, $saved['attempt_choice_ids'] ?? ($saved['choice_ids'] ?? []))) checked @endif>
                                    <label class="custom-control-label"
                                           for="c{{ $attempt_question->id }}_{{ $choice->id }}">
                                        {{ $choice->choice_revision->label }}
                                    </label>
                                </div>
                            @endforeach
                        @elseif ($revision->question_type === 'multiple_word')
                            @php
                                $groups = $revision->correct_answers
                                    ->pluck('answer_group')
                                    ->unique()
                                    ->sort()
                                    ->values();
                            @endphp
                            @foreach ($groups as $group)
                                <div class="form-group">
                                    <label>回答{{ $loop->iteration }}</label>
                                    <input class="form-control"
                                           name="answers[{{ $attempt_question->id }}][texts][]"
                                           value="{{ ($saved['texts'] ?? [])[$loop->index] ?? '' }}">
                                </div>
                            @endforeach
                        @elseif ($revision->question_type === 'essay')
                            <textarea class="form-control"
                                      rows="{{ $revision->answer_rows ?: 5 }}"
                                      name="answers[{{ $attempt_question->id }}][text]">{{ $saved['text'] ?? '' }}</textarea>
                        @else
                            <input class="form-control"
                                   name="answers[{{ $attempt_question->id }}][text]"
                                   value="{{ $saved['text'] ?? '' }}">
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach

    <div class="d-flex flex-wrap justify-content-center">
        <button class="btn btn-outline-primary mr-2 mb-2"
                type="submit"
                name="after_save"
                value="stay">
            <i class="fas fa-save"></i>
            この画面の回答を保存
        </button>
        <button class="btn btn-primary mb-2"
                type="submit"
                name="after_save"
                value="review">
            提出内容を確認する
            <i class="fas fa-arrow-right"></i>
        </button>
    </div>
</form>

