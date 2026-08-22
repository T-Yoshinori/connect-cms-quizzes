<?php

namespace App\Models\User\Quizzes;

use Illuminate\Database\Eloquent\Model;

class QuizzesAttemptQuestions extends Model
{
    protected $table = 'quiz_attempt_questions';

    protected $guarded = ['id'];

    protected $casts = [
        'display_sequence' => 'integer',
        'points' => 'decimal:2',
    ];

    public function attempt_page()
    {
        return $this->belongsTo(QuizzesAttemptPages::class, 'quiz_attempt_page_id', 'id');
    }

    public function quiz_question()
    {
        return $this->belongsTo(QuizzesQuestions::class, 'quiz_question_id', 'id');
    }

    public function question_revision()
    {
        return $this->belongsTo(QuizzesQuestionRevisions::class, 'question_revision_id', 'id');
    }

    public function choices()
    {
        return $this->hasMany(QuizzesAttemptQuestionChoices::class, 'quiz_attempt_question_id', 'id')
            ->orderBy('display_sequence')
            ->orderBy('id');
    }

    public function answer()
    {
        return $this->hasOne(QuizzesAnswers::class, 'quiz_attempt_question_id', 'id');
    }
}
