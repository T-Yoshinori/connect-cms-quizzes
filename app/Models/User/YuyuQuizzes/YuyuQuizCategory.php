<?php

namespace App\Models\User\YuyuQuizzes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class YuyuQuizCategory extends Model
{

    protected $fillable = [
        'quiz_category_group_id',
        'name',
        'sequence',
        'is_active',
    ];

    protected $casts = [
        'quiz_category_group_id' => 'integer',
        'sequence' => 'integer',
        'is_active' => 'boolean',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(
            YuyuQuizCategoryGroup::class,
            'quiz_category_group_id',
            'id'
        );
    }

    public function question_revision_categories(): HasMany
    {
        return $this->hasMany(
            YuyuQuizQuestionRevisionCategory::class,
            'quiz_category_id',
            'id'
        );
    }

    public function question_revisions(): BelongsToMany
    {
        return $this->belongsToMany(
            YuyuQuizQuestionRevision::class,
            'yuyu_quiz_question_revision_categories',
            'quiz_category_id',
            'question_revision_id'
        )->withTimestamps();
    }
}
