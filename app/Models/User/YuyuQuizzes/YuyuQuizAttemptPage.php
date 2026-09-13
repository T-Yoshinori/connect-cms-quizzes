<?php

namespace App\Models\User\YuyuQuizzes;

use Illuminate\Database\Eloquent\Model;

class YuyuQuizAttemptPage extends Model
{

    protected $guarded = ['id'];

    protected $casts = [
        'display_sequence' => 'integer',
    ];

    public function attempt()
    {
        return $this->belongsTo(YuyuQuizAttempt::class, 'quiz_attempt_id', 'id');
    }

    public function quiz_page()
    {
        return $this->belongsTo(YuyuQuizPage::class, 'quiz_page_id', 'id');
    }

    public function attempt_questions()
    {
        return $this->hasMany(YuyuQuizAttemptQuestion::class, 'quiz_attempt_page_id', 'id')
            ->orderBy('display_sequence')
            ->orderBy('id');
    }
}
