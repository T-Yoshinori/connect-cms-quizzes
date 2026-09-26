@include('plugins.user.yuyuquizzes.default.quizzes_mathjax')

@php
    $is_expired = $attempt->expires_at && now()->greaterThanOrEqualTo($attempt->expires_at);
    $review_url = url('/') . '/plugin/yuyuquizzes/review/' . $page->id . '/' . $frame->id . '/' . $attempt->id . '#frame-' . $frame->id;
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
          action="{{ url('/') }}/redirect/plugin/yuyuquizzes/saveAnswer/{{ $page->id }}/{{ $frame->id }}/{{ $attempt->id }}#frame-{{ $frame->id }}"
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
                                @elseif ($revision->question_type === 'essay' && $revision->essay_input_mode === 'handwriting')
                                    <div class="quiz-handwriting" data-question-id="{{ $attempt_question->id }}"
                                         data-image-url="@if(!empty($saved['handwriting_image_id'])){{ url('/') }}/download/plugin/yuyuquizzes/handwritingImage/{{ $page->id }}/{{ $frame->id }}/{{ $saved['handwriting_image_id'] }}@endif">
                                        <div class="mb-2">
                                            <button type="button" class="btn btn-sm btn-outline-secondary handwriting-undo">一つ戻す</button>
                                            <button type="button" class="btn btn-sm btn-outline-secondary handwriting-clear">全消去</button>
                                            <span class="handwriting-status small ml-2" role="status">{{ !empty($saved['handwriting_image_id']) ? '保存済み' : '未回答' }}</span>
                                        </div>
                                        <canvas width="1200" height="600" class="w-100 border rounded"
                                                style="height:auto; touch-action:none; background:white; cursor:crosshair"
                                                aria-label="問{{ $question_number }}の手書き回答欄"></canvas>
                                    </div>
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

@if (!$is_expired)
<script>
document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('quiz-answer-form-{{ $attempt->id }}');
    if (!form) return;
    var writers = [];
    document.querySelectorAll('#quiz-answer-fields-{{ $attempt->id }} .quiz-handwriting').forEach(function (box) {
        var canvas = box.querySelector('canvas'), ctx = canvas.getContext('2d');
        var status = box.querySelector('.handwriting-status'), undo = box.querySelector('.handwriting-undo');
        var history = [], drawing = false, dirty = false, timer, pending = Promise.resolve();
        ctx.lineWidth = 3; ctx.lineCap = 'round'; ctx.lineJoin = 'round'; ctx.strokeStyle = '#111';
        var savedUrl = box.dataset.imageUrl;
        if (savedUrl) {
            var image = new Image();
            image.onload = function () { if (!dirty) ctx.drawImage(image, 0, 0, canvas.width, canvas.height); };
            image.src = savedUrl;
        }
        function point(e) { var r = canvas.getBoundingClientRect(); return {
            x: (e.clientX - r.left) * canvas.width / r.width,
            y: (e.clientY - r.top) * canvas.height / r.height
        }; }
        function changed() { dirty = true; status.textContent = '未保存'; clearTimeout(timer);
            timer = setTimeout(function () { save().catch(function () {}); }, 1800); }
        canvas.addEventListener('pointerdown', function (e) {
            if (drawing) return;
            canvas.setPointerCapture(e.pointerId); drawing = true;
            history.push(ctx.getImageData(0, 0, canvas.width, canvas.height));
            if (history.length > 10) history.shift();
            var p = point(e); ctx.beginPath(); ctx.moveTo(p.x, p.y); ctx.lineTo(p.x + .01, p.y + .01); ctx.stroke();
            changed();
        });
        canvas.addEventListener('pointermove', function (e) {
            if (!drawing) return; var p = point(e); ctx.lineTo(p.x, p.y); ctx.stroke(); changed();
        });
        ['pointerup', 'pointercancel'].forEach(function (event) {
            canvas.addEventListener(event, function () { drawing = false; });
        });
        undo.addEventListener('click', function () { if (history.length) { ctx.putImageData(history.pop(), 0, 0); changed(); } });
        box.querySelector('.handwriting-clear').addEventListener('click', function () {
            if (!confirm('手書き回答を全消去しますか？')) return;
            history.push(ctx.getImageData(0, 0, canvas.width, canvas.height));
            if (history.length > 10) history.shift();
            ctx.clearRect(0, 0, canvas.width, canvas.height); changed();
        });
        function save() {
            clearTimeout(timer);
            if (!dirty) return pending;
            dirty = false;
            // Serialized uploads prevent an older image from replacing a newer one.
            var snapshot = document.createElement('canvas');
            snapshot.width = canvas.width; snapshot.height = canvas.height;
            var snapshotCtx = snapshot.getContext('2d');
            snapshotCtx.fillStyle = '#fff'; snapshotCtx.fillRect(0, 0, snapshot.width, snapshot.height);
            snapshotCtx.drawImage(canvas, 0, 0);
            var pixels = snapshotCtx.getImageData(0, 0, snapshot.width, snapshot.height).data;
            var blank = true;
            for (var i = 0; i < pixels.length; i += 4) {
                if (pixels[i] < 245 || pixels[i + 1] < 245 || pixels[i + 2] < 245) { blank = false; break; }
            }
            pending = pending.catch(function () {}).then(function () {
                status.textContent = '保存中';
                return blank ? null : new Promise(function (resolve) { snapshot.toBlob(resolve, 'image/png'); });
            }).then(function (blob) {
                if (!blank && !blob) throw new Error('画像を作成できませんでした');
                var data = new FormData();
                data.append('_token', form.querySelector('input[name="_token"]').value);
                data.append('return_mode', 'asis');
                data.append('attempt_question_id', box.dataset.questionId);
                if (blank) data.append('clear', '1');
                else data.append('image', blob, 'answer.png');
                return fetch('{{ url('/') }}/redirect/plugin/yuyuquizzes/saveHandwriting/{{ $page->id }}/{{ $frame->id }}/{{ $attempt->id }}', {
                    method: 'POST', body: data, credentials: 'same-origin', headers: { 'Accept': 'application/json' }
                });
            }).then(function (response) {
                if (!response.ok || !response.headers.get('content-type')?.includes('application/json'))
                    throw new Error('保存に失敗しました');
                return response.json();
            }).then(function (result) {
                if (result.saved !== true) throw new Error('保存に失敗しました');
                status.textContent = dirty ? '未保存' : '保存済み';
            }).catch(function (error) { dirty = true; status.textContent = '保存できませんでした。再試行してください'; throw error; });
            return pending;
        }
        writers.push({ save: save, isDirty: function () { return dirty; } });
    });
    var allowingSubmit = false;
    form.addEventListener('submit', function (e) {
        if (allowingSubmit) return;
        if (!writers.length) return;
        e.preventDefault(); var button = e.submitter;
        function flush() { return Promise.all(writers.map(function (writer) { return writer.save(); })).then(function () {
            return writers.some(function (writer) { return writer.isDirty(); }) ? flush() : null;
        }); }
        flush().then(function () {
            allowingSubmit = true;
            if (button) button.disabled = false;
            form.requestSubmit(button || undefined);
        }).catch(function () { alert('手書き回答を保存できませんでした。通信状態を確認して再試行してください。'); });
    });
});
</script>
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
