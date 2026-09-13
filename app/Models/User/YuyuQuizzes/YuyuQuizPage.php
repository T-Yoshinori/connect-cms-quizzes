<?php

namespace App\Models\User\YuyuQuizzes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class YuyuQuizPage extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'sequence' => 'integer',
    ];

    public function quiz()
    {
        return $this->belongsTo(YuyuQuiz::class, 'quiz_id', 'id');
    }

    public function questions()
    {
        return $this->hasMany(YuyuQuizQuestion::class, 'quiz_page_id', 'id')
            ->orderBy('sequence')
            ->orderBy('id');
    }

    public function attempt_pages()
    {
        return $this->hasMany(YuyuQuizAttemptPage::class, 'quiz_page_id', 'id');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sequence')->orderBy('id');
    }
}
