<?php

namespace App\Models\User\Quizzes;

use App\Models\Common\Frame;
use App\Models\Common\Page;
use App\Models\Common\Group;
use Illuminate\Database\Eloquent\Model;

class QuizzesPageGroups extends Model
{
    protected $table = 'quiz_page_groups';

    protected $guarded = ['id'];

    public function quiz()
    {
        return $this->belongsTo(Quizzes::class, 'quiz_id', 'id');
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
