<?php

namespace App\Models\User\Quizzes;

use App\User;
use Illuminate\Database\Eloquent\Model;

class QuizzesAnswerGrades extends Model
{
    protected $table = 'quiz_answer_grades';

    protected $guarded = ['id'];

    protected $casts = [
        'score' => 'decimal:2',
        'graded_at' => 'datetime',
        'is_current' => 'boolean',
    ];

    public function answer()
    {
        return $this->belongsTo(QuizzesAnswers::class, 'quiz_answer_id', 'id');
    }

    public function grader()
    {
        return $this->belongsTo(User::class, 'graded_by', 'id');
    }

    public function scopeCurrent($query)
    {
        return $query->where('is_current', true);
    }
}
