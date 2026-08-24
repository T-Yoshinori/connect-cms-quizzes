<?php

namespace App\Models\User\Quizzes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuizzesQuestionRevisionCategories extends Model
{
    protected $table = 'quiz_question_revision_categories';

    protected $fillable = [
        'question_revision_id',
        'quiz_category_id',
    ];

    protected $casts = [
        'question_revision_id' => 'integer',
        'quiz_category_id' => 'integer',
    ];

    public function question_revision(): BelongsTo
    {
        return $this->belongsTo(
            QuizzesQuestionRevisions::class,
            'question_revision_id',
            'id'
        );
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(
            QuizzesCategories::class,
            'quiz_category_id',
            'id'
        );
    }
}
