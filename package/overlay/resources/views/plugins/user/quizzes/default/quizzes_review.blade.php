<div class="card"><div class="card-header">提出確認</div><div class="card-body">
<p>回答内容を確認し、小テストを提出します。提出後は回答を変更できません。</p>
<div class="text-center"><a class="btn btn-secondary" href="{{ url('/') }}/plugin/quizzes/answer/{{ $page->id }}/{{ $frame->id }}/{{ $attempt->id }}#frame-{{ $frame->id }}">回答に戻る</a>
<form class="d-inline" action="{{ url('/') }}/redirect/plugin/quizzes/submitAttempt/{{ $page->id }}/{{ $frame->id }}/{{ $attempt->id }}#frame-{{ $frame->id }}" method="POST" onsubmit="return confirm('提出後は回答を変更できません。提出しますか？');">{{ csrf_field() }}<button class="btn btn-primary" type="submit">提出する</button></form></div>
</div></div>
