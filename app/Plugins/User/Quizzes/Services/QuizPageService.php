<?php

namespace App\Plugins\User\Quizzes\Services;

use Illuminate\Support\Facades\DB;
use App\Models\User\Quizzes\QuizzesPages;

class QuizPageService
{
    /**
     * ページを新規作成または更新する。
     */
    public function savePage(array $data, $quiz_page_id = null): QuizzesPages
    {
        return DB::transaction(function () use ($data, $quiz_page_id) {
            if (empty($quiz_page_id)) {
                $quiz_page = new QuizzesPages();
                $quiz_page->quiz_id = (int) $data['quiz_id'];
                $quiz_page->sequence = $this->getNextSequence((int) $data['quiz_id']);
            } else {
                $quiz_page = QuizzesPages::findOrFail($quiz_page_id);

                if ((int) $quiz_page->quiz_id !== (int) $data['quiz_id']) {
                    abort(404);
                }
            }

            $quiz_page->title = $data['title'] ?? null;
            $quiz_page->description = $data['description'] ?? null;
            $quiz_page->save();

            return $quiz_page;
        });
    }

    /**
     * 新規ページを末尾へ追加するための表示順を返す。
     */
    private function getNextSequence(int $quiz_id): int
    {
        $max_sequence = QuizzesPages::query()
            ->where('quiz_id', $quiz_id)
            ->max('sequence');

        return is_null($max_sequence)
            ? 0
            : (int) $max_sequence + 1;
    }
}
