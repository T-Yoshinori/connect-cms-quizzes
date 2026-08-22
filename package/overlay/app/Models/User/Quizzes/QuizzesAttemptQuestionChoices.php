<?php

namespace App\Models\User\Quizzes;

use Illuminate\Database\Eloquent\Model;

class QuizzesAttemptQuestionChoices extends Model
{
    protected $table = 'quiz_attempt_question_choices';

    protected $guarded = ['id'];

    protected $casts = [
        'display_sequence' => 'integer',
    ];

    public function attempt_question()
    {
        return $this->belongsTo(QuizzesAttemptQuestions::class, 'quiz_attempt_question_id', 'id');
    }

    public function choice_revision()
    {
        return $this->belongsTo(QuizzesChoiceRevisions::class, 'choice_revision_id', 'id');
    }
}
