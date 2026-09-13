<?php

namespace App\Models\User\YuyuQuizzes;

use Illuminate\Database\Eloquent\Model;

class YuyuQuizChoiceRevision extends Model
{

    protected $guarded = ['id'];

    protected $casts = [
        'sequence' => 'integer',
        'is_correct' => 'boolean',
    ];

    public function question_revision()
    {
        return $this->belongsTo(YuyuQuizQuestionRevision::class, 'question_revision_id', 'id');
    }

    public function attempt_choices()
    {
        return $this->hasMany(YuyuQuizAttemptQuestionChoice::class, 'choice_revision_id', 'id');
    }
}
