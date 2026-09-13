<?php

namespace App\Models\User\YuyuQuizzes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class YuyuQuizQuestionRevisionCategory extends Model
{

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
            YuyuQuizQuestionRevision::class,
            'question_revision_id',
            'id'
        );
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(
            YuyuQuizCategory::class,
            'quiz_category_id',
            'id'
        );
    }
}
