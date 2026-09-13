<?php

namespace App\Models\User\YuyuQuizzes;

use Illuminate\Database\Eloquent\Model;

class YuyuQuizAttemptQuestion extends Model
{

    protected $guarded = ['id'];

    protected $casts = [
        'display_sequence' => 'integer',
        'points' => 'decimal:2',
    ];

    public function attempt_page()
    {
        return $this->belongsTo(YuyuQuizAttemptPage::class, 'quiz_attempt_page_id', 'id');
    }

    public function quiz_question()
    {
        return $this->belongsTo(YuyuQuizQuestion::class, 'quiz_question_id', 'id');
    }

    public function question_revision()
    {
        return $this->belongsTo(YuyuQuizQuestionRevision::class, 'question_revision_id', 'id');
    }

    public function choices()
    {
        return $this->hasMany(YuyuQuizAttemptQuestionChoice::class, 'quiz_attempt_question_id', 'id')
            ->orderBy('display_sequence')
            ->orderBy('id');
    }

    public function answer()
    {
        return $this->hasOne(YuyuQuizAnswer::class, 'quiz_attempt_question_id', 'id');
    }
}
