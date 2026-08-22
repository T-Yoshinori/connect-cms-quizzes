<?php

namespace App\Models\User\Quizzes;

use Illuminate\Database\Eloquent\Model;

class QuizzesCorrectAnswerRevisions extends Model
{
    protected $table = 'quiz_correct_answer_revisions';

    protected $guarded = ['id'];

    protected $casts = [
        'answer_group' => 'integer',
        'sequence' => 'integer',
    ];

    public function question_revision()
    {
        return $this->belongsTo(QuizzesQuestionRevisions::class, 'question_revision_id', 'id');
    }
}
