<?php

namespace App\Models\User\YuyuQuizzes;

use App\User;
use Illuminate\Database\Eloquent\Model;

class YuyuQuizQuestionRevision extends Model
{

    protected $guarded = ['id'];

    protected $casts = [
        'revision_no' => 'integer',
        'points' => 'decimal:2',
        'choice_random' => 'boolean',
        'answer_order_fixed' => 'boolean',
        'normalization_options' => 'array',
        'answer_rows' => 'integer',
        'character_limit' => 'integer',
        'essay_input_mode' => 'string',
    ];

    public function question()
    {
        return $this->belongsTo(YuyuQuizQuestion::class, 'quiz_question_id', 'id');
    }

    public function choices()
    {
        return $this->hasMany(YuyuQuizChoiceRevision::class, 'question_revision_id', 'id')
            ->orderBy('sequence')
            ->orderBy('id');
    }

    public function correct_answers()
    {
        return $this->hasMany(YuyuQuizCorrectAnswerRevision::class, 'question_revision_id', 'id')
            ->orderBy('answer_group')
            ->orderBy('sequence')
            ->orderBy('id');
    }

    public function attempt_questions()
    {
        return $this->hasMany(YuyuQuizAttemptQuestion::class, 'question_revision_id', 'id');
    }

    public function categories()
    {
        return $this->belongsToMany(
            YuyuQuizCategory::class,
            'yuyu_quiz_question_revision_categories',
            'question_revision_id',
            'quiz_category_id'
        )->withTimestamps();
    }

    public function category_assignments()
    {
        return $this->hasMany(
            YuyuQuizQuestionRevisionCategory::class,
            'question_revision_id',
            'id'
        );
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_id', 'id');
    }
}
