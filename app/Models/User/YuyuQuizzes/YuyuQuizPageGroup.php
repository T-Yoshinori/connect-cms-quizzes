<?php

namespace App\Models\User\YuyuQuizzes;

use App\Models\Common\Frame;
use App\Models\Common\Page;
use App\Models\Common\Group;
use Illuminate\Database\Eloquent\Model;

class YuyuQuizPageGroup extends Model
{

    protected $guarded = ['id'];

    public function quiz()
    {
        return $this->belongsTo(YuyuQuiz::class, 'quiz_id', 'id');
    }

    public function page()
    {
        return $this->belongsTo(Page::class, 'page_id', 'id');
    }

    public function frame()
    {
        return $this->belongsTo(Frame::class, 'frame_id', 'id');
    }

    public function group()
    {
        return $this->belongsTo(Group::class, 'group_id', 'id');
    }
}
