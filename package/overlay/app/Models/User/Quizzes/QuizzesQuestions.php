<?php

namespace App\Models\User\Quizzes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class QuizzesQuestions extends Model
{
    use SoftDeletes;

    protected $table = 'quiz_questions';

    protected $guarded = ['id'];

    protected $casts = [
        'sequence' => 'integer',
    ];

    public function quiz_page()
    {
        return $this->belongsTo(QuizzesPages::class, 'quiz_page_id', 'id');
    }

    public function current_revision()
    {
        return $this->belongsTo(QuizzesQuestionRevisions::class, 'current_revision_id', 'id');
    }

    public function revisions()
    {
        return $this->hasMany(QuizzesQuestionRevisions::class, 'quiz_question_id', 'id')
            ->orderBy('revision_no');
    }

    public function attempt_questions()
    {
        return $this->hasMany(QuizzesAttemptQuestions::class, 'quiz_question_id', 'id');
    }

    /**
     * 本番受験で手動採点を待っている回答です。
     */
    public function pending_manual_answers()
    {
        return $this->hasManyThrough(
            QuizzesAnswers::class,
            QuizzesAttemptQuestions::class,
            'quiz_question_id',
            'quiz_attempt_question_id',
            'id',
            'id'
        )
            ->where('quiz_answers.grading_status', 'manual_pending')
            ->whereHas('attempt', function ($query) {
                $query->where('is_preview', false);
            });
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sequence')->orderBy('id');
    }
}
