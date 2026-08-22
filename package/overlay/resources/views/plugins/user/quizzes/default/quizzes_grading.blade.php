@extends('core.cms_frame_base')

@section("plugin_contents_$frame->id")

@include('plugins.user.quizzes.default.quizzes_mathjax')

<div class="card mb-3">
    <div class="card-header">
        <div class="small text-muted">記述式問題の採点</div>
        <h2 class="h5 mb-0">{{ $question->quiz_page->quiz->title }}</h2>
    </div>
    <div class="card-body">
        <div class="font-weight-bold mb-2">問題文</div>
        <div>{!! $question->current_revision->question_text !!}</div>

        @if (!empty($question->current_revision->model_answer))
            <div class="alert alert-light border mt-3 mb-0">
                <strong>模範解答</strong>
                <div>{!! nl2br(e($question->current_revision->model_answer)) !!}</div>
            </div>
        @endif

        @if (!empty($question->current_revision->grading_guide))
            <div class="alert alert-light border mt-3 mb-0">
                <strong>採点基準</strong>
                <div>{!! nl2br(e($question->current_revision->grading_guide)) !!}</div>
            </div>
        @endif
    </div>
</div>

@forelse ($answers as $answer)
    <form id="grading-answer-{{ $answer->id }}"
          class="card mb-3"
          action="{{ url('/') }}/redirect/plugin/quizzes/gradeAnswer/{{ $page->id }}/{{ $frame->id }}/{{ $answer->id }}#frame-{{ $frame->id }}"
          method="POST">
        {{ csrf_field() }}

        <div class="card-header bg-white d-flex flex-wrap justify-content-between">
            <span>
                受験者：
                {{ optional($answer->attempt->user)->name ?? 'ユーザーID ' . $answer->attempt->user_id }}
            </span>
            <span class="text-muted">
                受験{{ $answer->attempt->attempt_no }}回目
                @if ($answer->attempt->submitted_at)
                    ／提出 {{ $answer->attempt->submitted_at->format('Y年n月j日 H:i') }}
                @endif
            </span>
        </div>

        <div class="card-body">
            @if ((int) old('grading_answer_id') === (int) $answer->id && $errors->any())
                <div class="alert alert-danger" role="alert">
                    <div class="font-weight-bold mb-1">採点結果を保存できませんでした。</div>
                    <ul class="mb-0 pl-4">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="mb-3">
                <strong>回答</strong>
                <div class="border rounded p-3 mt-1">
                    {!! nl2br(e(data_get($answer->answer_data, 'text', ''))) !!}
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-3">
                    <label for="score-{{ $answer->id }}">
                        得点（配点 {{ number_format((float) $answer->attempt_question->points, 2) }}点）
                    </label>
                    <input required
                           id="score-{{ $answer->id }}"
                           type="number"
                           step="0.01"
                           min="0"
                           max="{{ $answer->attempt_question->points }}"
                           name="score"
                           value="{{ (int) old('grading_answer_id') === (int) $answer->id ? old('score') : '' }}"
                           class="form-control">
                </div>

                <div class="form-group col-md-4">
                    <label for="correctness-{{ $answer->id }}">判定</label>
                    <select required
                            id="correctness-{{ $answer->id }}"
                            name="correctness"
                            class="form-control">
                        <option value="correct" @if ((int) old('grading_answer_id') === (int) $answer->id && old('correctness') === 'correct') selected @endif>正解</option>
                        <option value="partial" @if ((int) old('grading_answer_id') === (int) $answer->id && old('correctness') === 'partial') selected @endif>部分点</option>
                        <option value="incorrect" @if ((int) old('grading_answer_id') === (int) $answer->id && old('correctness') === 'incorrect') selected @endif>不正解</option>
                        <option value="not_applicable" @if ((int) old('grading_answer_id') === (int) $answer->id && old('correctness') === 'not_applicable') selected @endif>判定対象外</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="reason-{{ $answer->id }}">採点理由</label>
                <textarea id="reason-{{ $answer->id }}"
                          name="reason"
                          rows="2"
                          class="form-control">{{ (int) old('grading_answer_id') === (int) $answer->id ? old('reason') : '' }}</textarea>
            </div>

            <div class="form-group">
                <label for="comment-{{ $answer->id }}">受験者へのコメント</label>
                <textarea id="comment-{{ $answer->id }}"
                          name="comment"
                          rows="3"
                          class="form-control">{{ (int) old('grading_answer_id') === (int) $answer->id ? old('comment') : '' }}</textarea>
            </div>

            <div class="form-group">
                <label for="internal-comment-{{ $answer->id }}">管理者用メモ</label>
                <textarea id="internal-comment-{{ $answer->id }}"
                          name="internal_comment"
                          rows="2"
                          class="form-control">{{ (int) old('grading_answer_id') === (int) $answer->id ? old('internal_comment') : '' }}</textarea>
            </div>

            <div class="text-right">
                <button type="submit" class="btn btn-primary">
                    採点結果を保存
                </button>
            </div>
        </div>
    </form>
@empty
    <div class="alert alert-info">採点待ちの回答はありません。</div>
@endforelse

@if (method_exists($answers, 'links'))
    {{ $answers->links() }}
@endif

<div class="text-center mt-4">
    <a href="{{ URL::to($page->permanent_link) }}#frame-{{ $frame->id }}"
       class="btn btn-primary">
        <i class="fas fa-arrow-left"></i>
        小テストの初期画面へ戻る
    </a>
</div>

@endsection
