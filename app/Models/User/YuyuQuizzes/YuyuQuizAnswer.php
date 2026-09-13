<?php

namespace App\Models\User\YuyuQuizzes;

use Illuminate\Database\Eloquent\Model;

class YuyuQuizAnswer extends Model
{

    protected $guarded = ['id'];

    protected $casts = [
        'answer_data' => 'array',
        'current_score' => 'decimal:2',
        'answered_at' => 'datetime',
    ];

    public function attempt()
    {
        return $this->belongsTo(YuyuQuizAttempt::class, 'quiz_attempt_id', 'id');
    }

    public function attempt_question()
    {
        return $this->belongsTo(YuyuQuizAttemptQuestion::class, 'quiz_attempt_question_id', 'id');
    }

    public function grades()
    {
        return $this->hasMany(YuyuQuizAnswerGrade::class, 'quiz_answer_id', 'id')
            ->orderByDesc('graded_at')
            ->orderByDesc('id');
    }

    public function current_grade()
    {
        return $this->hasOne(YuyuQuizAnswerGrade::class, 'quiz_answer_id', 'id')
            ->where('is_current', true);
    }

    public function scopePendingManualGrading($query)
    {
        return $query->where('grading_status', 'manual_pending');
    }
}
