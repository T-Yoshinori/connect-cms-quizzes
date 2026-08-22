@extends('core.cms_frame_base_setting')
@section("plugin_setting_$frame->id")
@include('plugins.common.errors_form_line')
<form action="{{ url('/') }}/redirect/plugin/quizzes/save/{{ $page->id }}/{{ $frame->id }}{{ $quiz->exists ? '/' . $quiz->id : '' }}#frame-{{ $frame->id }}" method="POST">
    {{ csrf_field() }}
    <div class="card"><div class="card-header">小テスト設定</div><div class="card-body">
        <div class="form-group"><label>小テスト名</label><input name="title" class="form-control" value="{{ old('title', $quiz->title) }}" required></div>
        <div class="form-group"><label>説明</label><textarea name="description" class="form-control" rows="5">{{ old('description', $quiz->description) }}</textarea></div>
        <input type="hidden" name="status" value="{{ old('status', $quiz->status ?: 'draft') }}">
        <input type="hidden" name="retry_type" value="{{ old('retry_type', $quiz->retry_type ?: 'unlimited') }}">
        <input type="hidden" name="passing_type" value="{{ old('passing_type', $quiz->passing_type ?: 'none') }}">
        <input type="hidden" name="result_display_timing" value="{{ old('result_display_timing', $quiz->result_display_timing ?: 'after_grading') }}">
        <div class="text-center"><a class="btn btn-secondary" href="{{ URL::to($page->permanent_link) }}#frame-{{ $frame->id }}">キャンセル</a> <button class="btn btn-primary" type="submit">保存</button></div>
    </div></div>
</form>
@endsection
