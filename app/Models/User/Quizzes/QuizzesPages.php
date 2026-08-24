<?php

namespace App\Models\User\Quizzes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class QuizzesPages extends Model
{
    use SoftDeletes;

    protected $table = 'quiz_pages';

    protected $guarded = ['id'];

    protected $casts = [
        'sequence' => 'integer',
    ];

    public function quiz()
    {
        return $this->belongsTo(Quizzes::class, 'quiz_id', 'id');
    }

    public function questions()
    {
        return $this->hasMany(QuizzesQuestions::class, 'quiz_page_id', 'id')
            ->orderBy('sequence')
            ->orderBy('id');
    }

    public function attempt_pages()
    {
        return $this->hasMany(QuizzesAttemptPages::class, 'quiz_page_id', 'id');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sequence')->orderBy('id');
    }
}
