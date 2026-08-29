@include('plugins.user.quizzes.default.quizzes_mathjax')

@php
    $is_expired = $attempt->expires_at && now()->greaterThanOrEqualTo($attempt->expires_at);
    $review_url = url('/') . '/plugin/quizzes/review/' . $page->id . '/' . $frame->id . '/' . $attempt->id . '#frame-' . $frame->id;
@endphp

<div class="card mb-3">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
        <h2 class="h4 mb-0">{{ $attempt->quiz->title }}</h2>
        @if ($attempt->expires_at)
            <div id="quiz-time-remaining-{{ $attempt->id }}"
                 class="badge badge-primary mt-2 mt-sm-0 p-2"
                 aria-live="polite"
                 data-expires-at="{{ $attempt->expires_at->getTimestamp() * 1000 }}"
                 data-server-now="{{ now()->getTimestamp() * 1000 }}">
                残り時間を計算しています
            </div>
        @endif
    </div>
</div>

<div id="quiz-expired-message-{{ $attempt->id }}"
     class="alert alert-warning text-center {{ $is_expired ? '' : 'd-none' }}">
    <h3 class="h5">制限時間が終了しました</h3>
    <p>これ以降、回答は変更できません。保存済みの回答を確認して提出してください。</p>
    <a class="btn btn-primary" href="{{ $review_url }}">
        提出内容を確認する
        <i class="fas fa-arrow-right"></i>
    </a>
</div>

@if (!$is_expired)
    <form id="quiz-answer-form-{{ $attempt->id }}"
          action="{{ url('/') }}/redirect/plugin/quizzes/saveAnswer/{{ $page->id }}/{{ $frame->id }}/{{ $attempt->id }}#frame-{{ $frame->id }}"
          method="POST"
          onsubmit="setTimeout(() => this.querySelectorAll('button[type=submit]').forEach(button => button.disabled = true), 0);">
        {{ csrf_field() }}
        <input type="hidden" name="attempt_id" value="{{ $attempt->id }}">

        <div id="quiz-answer-fields-{{ $attempt->id }}">
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
        </div>

        <div id="quiz-answer-actions-{{ $attempt->id }}" class="d-flex flex-wrap justify-content-center">
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
            <button class="btn btn-outline-secondary ml-2 mb-2"
                    type="submit"
                    name="after_save"
                    value="interrupt"
                    onclick="return confirm('受験を中断しても制限時間は止まりません。予定の制限時間を過ぎると回答できなくなります。現在の回答を保存して受験を中断しますか？');">
                <i class="fas fa-pause"></i>
                受験を中断する
            </button>
        </div>
    </form>
@endif

@if ($attempt->expires_at && !$is_expired)
    <script>
        (function () {
            var timer = document.getElementById('quiz-time-remaining-{{ $attempt->id }}');
            var form = document.getElementById('quiz-answer-form-{{ $attempt->id }}');
            var fields = document.getElementById('quiz-answer-fields-{{ $attempt->id }}');
            var actions = document.getElementById('quiz-answer-actions-{{ $attempt->id }}');
            var expiredMessage = document.getElementById('quiz-expired-message-{{ $attempt->id }}');

            if (!timer || !form) {
                return;
            }

            var expiresAt = Number(timer.getAttribute('data-expires-at'));
            var serverNow = Number(timer.getAttribute('data-server-now'));
            var startedAt = Date.now();
            var intervalId = null;

            function expireAnswerScreen() {
                if (fields) {
                    fields.querySelectorAll('input, textarea, select, button').forEach(function (element) {
                        element.disabled = true;
                    });
                }
                if (actions) {
                    actions.classList.add('d-none');
                }
                if (expiredMessage) {
                    expiredMessage.classList.remove('d-none');
                }
                timer.textContent = '制限時間終了';
                timer.classList.remove('badge-primary', 'badge-warning');
                timer.classList.add('badge-danger');
                if (intervalId) {
                    window.clearInterval(intervalId);
                }
            }

            function updateTimer() {
                var currentServerTime = serverNow + (Date.now() - startedAt);
                var remainingSeconds = Math.max(0, Math.ceil((expiresAt - currentServerTime) / 1000));

                if (remainingSeconds <= 0) {
                    expireAnswerScreen();
                    return;
                }

                var hours = Math.floor(remainingSeconds / 3600);
                var minutes = Math.floor((remainingSeconds % 3600) / 60);
                var seconds = remainingSeconds % 60;
                var value = hours > 0
                    ? String(hours) + ':' + String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0')
                    : String(minutes) + ':' + String(seconds).padStart(2, '0');

                timer.textContent = '残り ' + value;

                if (remainingSeconds <= 300) {
                    timer.classList.remove('badge-primary');
                    timer.classList.add('badge-warning');
                }
            }

            updateTimer();
            intervalId = window.setInterval(updateTimer, 1000);
        }());
    </script>
@endif
