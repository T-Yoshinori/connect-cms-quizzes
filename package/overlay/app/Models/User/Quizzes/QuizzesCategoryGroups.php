<?php

namespace App\Models\User\Quizzes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuizzesCategoryGroups extends Model
{
    protected $table = 'quiz_category_groups';

    protected $fillable = [
        'quiz_id',
        'name',
        'sequence',
        'is_active',
    ];

    protected $casts = [
        'quiz_id' => 'integer',
        'sequence' => 'integer',
        'is_active' => 'boolean',
    ];

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quizzes::class, 'quiz_id', 'id');
    }

    public function categories(): HasMany
    {
        return $this->hasMany(
            QuizzesCategories::class,
            'quiz_category_group_id',
            'id'
        )
            ->orderBy('sequence')
            ->orderBy('id');
    }

    public function active_categories(): HasMany
    {
        return $this->categories()->where('is_active', true);
    }
}
