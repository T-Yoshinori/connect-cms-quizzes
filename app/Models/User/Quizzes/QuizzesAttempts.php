<?php

namespace App\Models\User\Quizzes;

use App\User;
use App\Models\Common\Frame;
use App\Models\Common\Page;
use Illuminate\Database\Eloquent\Model;

class QuizzesAttempts extends Model
{
    protected $table = 'quiz_attempts';

    protected $guarded = ['id'];

    protected $casts = [
        'attempt_no' => 'integer',
        'started_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'submitted_at' => 'datetime',
        'graded_at' => 'datetime',
        'expires_at' => 'datetime',
        'elapsed_seconds' => 'integer',
        'total_score' => 'decimal:2',
        'effective_max_score' => 'decimal:2',
        'score_rate' => 'decimal:2',
        'passing_score_snapshot' => 'decimal:2',
        'passing_rate_snapshot' => 'decimal:2',
        'time_limit_minutes_snapshot' => 'integer',
        'is_preview' => 'boolean',
    ];

    public function quiz()
    {
        return $this->belongsTo(Quizzes::class, 'quiz_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function page()
    {
        return $this->belongsTo(Page::class, 'page_id', 'id');
    }

    public function frame()
    {
        return $this->belongsTo(Frame::class, 'frame_id', 'id');
    }

    public function attempt_pages()
    {
        return $this->hasMany(QuizzesAttemptPages::class, 'quiz_attempt_id', 'id')
            ->orderBy('display_sequence')
            ->orderBy('id');
    }

    public function answers()
    {
        return $this->hasMany(QuizzesAnswers::class, 'quiz_attempt_id', 'id');
    }

    public function scopeForUser($query, $user_id)
    {
        return $query->where('user_id', $user_id);
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }
}
