<?php

namespace App\Models\User\YuyuQuizzes;

use Illuminate\Database\Eloquent\Model;

class YuyuQuizCorrectAnswerRevision extends Model
{

    protected $guarded = ['id'];

    protected $casts = [
        'answer_group' => 'integer',
        'sequence' => 'integer',
    ];

    public function question_revision()
    {
        return $this->belongsTo(YuyuQuizQuestionRevision::class, 'question_revision_id', 'id');
    }
}
