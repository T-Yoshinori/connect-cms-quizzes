<?php

namespace App\Models\User\YuyuQuizzes;

use Illuminate\Database\Eloquent\Model;

class YuyuQuizHandwritingImage extends Model
{
    protected $guarded = ['id'];

    public function answer()
    {
        return $this->belongsTo(YuyuQuizAnswer::class, 'quiz_answer_id');
    }
}
