<?php

namespace App\Plugins\User\Yuyuquizzes\Services;

use App\Models\User\YuyuQuizzes\YuyuQuizAnswer;
use App\Models\User\YuyuQuizzes\YuyuQuizAttempt;
use App\Models\User\YuyuQuizzes\YuyuQuizAttemptQuestion;
use App\Models\User\YuyuQuizzes\YuyuQuizHandwritingImage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class QuizHandwritingService
{
    public function save($attemptId, $questionId, $userId, $file, $clear = false)
    {
        if (!$clear && (!$file || !$file->isValid() || $file->getSize() > 1048576)) {
            throw ValidationException::withMessages(['image' => '画像は1MB以下のPNGで保存してください。']);
        }
        $bytes = $clear ? null : file_get_contents($file->getRealPath());
        $dimensions = $clear ? null : @getimagesizefromstring($bytes);
        if (!$clear && (!$dimensions || $dimensions[2] !== IMAGETYPE_PNG || $dimensions[0] > 2400
            || $dimensions[1] > 2400 || $dimensions[0] < 1 || $dimensions[1] < 1)) {
            throw ValidationException::withMessages(['image' => '画像の形式または大きさが正しくありません。']);
        }

        $path = null;
        $previousImageId = null;
        try {
            $result = DB::transaction(function () use ($attemptId, $questionId, $userId, $bytes, $dimensions, $clear, &$path, &$previousImageId) {
                $attempt = YuyuQuizAttempt::whereKey($attemptId)->where('user_id', $userId)
                    ->lockForUpdate()->firstOrFail();
                if ($attempt->status !== 'in_progress' ||
                    ($attempt->expires_at && now()->greaterThan($attempt->expires_at))) {
                    throw ValidationException::withMessages(['image' => '提出後または制限時間後は変更できません。']);
                }
                $question = YuyuQuizAttemptQuestion::with('question_revision')
                    ->whereKey($questionId)
                    ->whereHas('attempt_page', function ($query) use ($attemptId) {
                        $query->where('quiz_attempt_id', $attemptId);
                    })->firstOrFail();
                if ($question->question_revision->question_type !== 'essay' ||
                    $question->question_revision->essay_input_mode !== 'handwriting') {
                    abort(404);
                }

                $answer = YuyuQuizAnswer::firstOrCreate(
                    ['quiz_attempt_id' => $attemptId, 'quiz_attempt_question_id' => $questionId],
                    ['correctness' => 'unanswered', 'grading_status' => 'ungraded']
                );
                $previousImageId = data_get($answer->answer_data, 'handwriting_image_id');
                if ($clear) {
                    $answer->answer_data = null;
                    $answer->correctness = 'unanswered';
                    $answer->answered_at = null;
                    $answer->save();
                    return null;
                }
                $path = 'yuyu-quizzes/handwriting/' . $attemptId . '/' . bin2hex(random_bytes(20)) . '.png';
                if (!Storage::disk('local')->put($path, $bytes)) {
                    throw new \RuntimeException('手書き画像を保存できませんでした。');
                }
                $image = YuyuQuizHandwritingImage::create([
                    'quiz_answer_id' => $answer->id, 'path' => $path,
                    'bytes' => strlen($bytes), 'width' => $dimensions[0], 'height' => $dimensions[1],
                ]);
                $answer->answer_data = ['handwriting_image_id' => $image->id];
                $answer->correctness = 'answered';
                $answer->answered_at = now();
                $answer->save();
                return $image;
            });
        } catch (\Throwable $e) {
            if ($path) Storage::disk('local')->delete($path);
            throw $e;
        }
        // Cleanup is best effort after commit. A stale image remains inaccessible.
        if ($previousImageId) {
            try {
                $previous = YuyuQuizHandwritingImage::find($previousImageId);
                if ($previous) {
                    Storage::disk('local')->delete($previous->path);
                    $previous->delete();
                }
            } catch (\Throwable $ignored) {
                // A later maintenance cleanup can remove this orphan.
            }
        }
        return $result;
    }

    public function findForViewer($imageId, $viewerId, $canGrade = false)
    {
        $image = YuyuQuizHandwritingImage::with('answer.attempt')->findOrFail($imageId);
        if (!$canGrade && (int)$image->answer->attempt->user_id !== (int)$viewerId) abort(403);
        // Old superseded images must never remain accessible via a guessed ID.
        if ((int)data_get($image->answer->answer_data, 'handwriting_image_id') !== (int)$image->id) abort(404);
        return $image;
    }
}
