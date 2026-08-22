<?php

namespace App\Models\User\Quizzes;

use Illuminate\Database\Eloquent\Model;

class QuizzesAttemptPages extends Model
{
    protected $table = 'quiz_attempt_pages';

    protected $guarded = ['id'];

    protected $casts = [
        'display_sequence' => 'integer',
    ];

    public function attempt()
    {
        return $this->belongsTo(QuizzesAttempts::class, 'quiz_attempt_id', 'id');
    }

    public function quiz_page()
    {
        return $this->belongsTo(QuizzesPages::class, 'quiz_page_id', 'id');
    }

    public function attempt_questions()
    {
        return $this->hasMany(QuizzesAttemptQuestions::class, 'quiz_attempt_page_id', 'id')
            ->orderBy('display_sequence')
            ->orderBy('id');
    }
}
