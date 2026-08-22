<?php

namespace App\Models\User\Quizzes;

use Illuminate\Database\Eloquent\Model;

class QuizzesChoiceRevisions extends Model
{
    protected $table = 'quiz_choice_revisions';

    protected $guarded = ['id'];

    protected $casts = [
        'sequence' => 'integer',
        'is_correct' => 'boolean',
    ];

    public function question_revision()
    {
        return $this->belongsTo(QuizzesQuestionRevisions::class, 'question_revision_id', 'id');
    }

    public function attempt_choices()
    {
        return $this->hasMany(QuizzesAttemptQuestionChoices::class, 'choice_revision_id', 'id');
    }
}
