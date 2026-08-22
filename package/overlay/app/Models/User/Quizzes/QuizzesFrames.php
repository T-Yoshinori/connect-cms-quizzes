<?php

namespace App\Models\User\Quizzes;

use App\Models\Common\Frame;
use Illuminate\Database\Eloquent\Model;

class QuizzesFrames extends Model
{
    /**
     * 対応するテーブル名。
     */
    protected $table = 'quiz_frames';

    /**
     * id以外は、Service・Plugin側で明示的に設定します。
     */
    protected $guarded = ['id'];

    /**
     * 属性の型変換。
     */
    protected $casts = [
        'frame_id' => 'integer',
        'quiz_id' => 'integer',
    ];

    /**
     * このフレームに割り当てられた小テスト。
     */
    public function quiz()
    {
        return $this->belongsTo(Quizzes::class, 'quiz_id', 'id');
    }

    /**
     * Connect-CMSのフレーム。
     */
    public function frame()
    {
        return $this->belongsTo(Frame::class, 'frame_id', 'id');
    }
}