<?php

namespace App\Models\User\YuyuQuizzes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class YuyuQuizQuestion extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'sequence' => 'integer',
    ];

    public function quiz_page()
    {
        return $this->belongsTo(YuyuQuizPage::class, 'quiz_page_id', 'id');
    }

    public function current_revision()
    {
        return $this->belongsTo(YuyuQuizQuestionRevision::class, 'current_revision_id', 'id');
    }

    public function revisions()
    {
        return $this->hasMany(YuyuQuizQuestionRevision::class, 'quiz_question_id', 'id')
            ->orderBy('revision_no');
    }

    public function attempt_questions()
    {
        return $this->hasMany(YuyuQuizAttemptQuestion::class, 'quiz_question_id', 'id');
    }

    /**
     * 本番受験で手動採点を待っている回答です。
     */
    public function pending_manual_answers()
    {
        return $this->hasManyThrough(
            YuyuQuizAnswer::class,
            YuyuQuizAttemptQuestion::class,
            'quiz_question_id',
            'quiz_attempt_question_id',
            'id',
            'id'
        )
            ->where('yuyu_quiz_answers.grading_status', 'manual_pending')
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
