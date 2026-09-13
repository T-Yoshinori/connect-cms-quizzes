<?php

namespace App\Models\User\YuyuQuizzes;

use Illuminate\Database\Eloquent\Model;

class YuyuQuizAttemptQuestionChoice extends Model
{

    protected $guarded = ['id'];

    protected $casts = [
        'display_sequence' => 'integer',
    ];

    public function attempt_question()
    {
        return $this->belongsTo(YuyuQuizAttemptQuestion::class, 'quiz_attempt_question_id', 'id');
    }

    public function choice_revision()
    {
        return $this->belongsTo(YuyuQuizChoiceRevision::class, 'choice_revision_id', 'id');
    }
}
