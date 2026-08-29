<?php

namespace App\Plugins\User\Quizzes;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

use App\Models\User\Quizzes\Quizzes;
use App\Models\User\Quizzes\QuizzesFrames;
use App\Models\User\Quizzes\QuizzesPages;
use App\Models\User\Quizzes\QuizzesQuestions;
use App\Models\User\Quizzes\QuizzesAnswers;
use App\Plugins\User\UserPluginBase;
use App\Plugins\User\Quizzes\Services\QuizService;
use App\Plugins\User\Quizzes\Services\QuizCopyService;
use App\Plugins\User\Quizzes\Services\QuizPageService;
use App\Plugins\User\Quizzes\Services\QuizQuestionService;
use App\Plugins\User\Quizzes\Services\QuizAttemptService;
use App\Plugins\User\Quizzes\Services\QuizAnswerService;
use App\Plugins\User\Quizzes\Services\QuizSubmissionService;
use App\Plugins\User\Quizzes\Services\QuizManualGradeService;
use App\Plugins\User\Quizzes\Services\QuizResultStatisticsService;
use App\Plugins\User\Quizzes\Services\QuizAdminResultService;
use App\Plugins\User\Quizzes\Services\QuizAnalysisCsvService;
use App\Plugins\User\Quizzes\Services\QuizCategoryService;
use App\Plugins\User\Quizzes\Services\QuizCategoryResultService;

/**
 * 小テストプラグイン
 *
 * Connect-CMSでは、UserPluginBaseを継承するこのクラスが
 * 一般的なLaravelアプリケーションにおけるControllerの役割を担います。
 *
 * @category 小テストプラグイン
 * @package Controller
 * @plugin_title 小テスト
 * @plugin_desc 問題作成、受験、自動採点、手動採点を行う小テストプラグインです。
 */
class QuizzesPlugin extends UserPluginBase
{
    /**
     * getPost()で取得したデータのキャッシュ
     *
     * @var \App\Models\User\Quizzes\Quizzes|null
     */
    public $post = null;

    /**
     * コアから直接呼び出せる独自アクション
     */
    public function getPublicFunctions()
    {
        return [
            'get' => [
                // 設定画面
                'listQuizzes',
                'createQuiz',
                'editQuizSettings',
                'manageCategories',
                'editView',
                'editBucketsRoles',
                'editBucketsMails',

                // 問題編集・受験
                'createFirstQuestion',
                'createPage',
                'editPage',
                'editQuestions',
                'createQuestion',
                'editQuestion',
                'start',
                'answer',
                'review',
                'result',
                'adminResults',
                'exportItemResponseCsv',
                'exportSpTableCsv',
                'exportCategoryResultsCsv',
                'grading',
            ],
            'post' => [
                // 設定保存
                'selectQuiz',
                'copyQuizForFrame',
                'saveQuizSettings',
                'saveCategories',
                'saveView',
                'saveBucketsRoles',
                'saveBucketsMails',

                // 問題編集・受験
                'savePage',
                'saveQuestion',
                'deleteQuestion',
                'startAttempt',
                'saveAnswer',
                'submitAttempt',
                'gradeAnswer',
            ],
        ];
    }

    /**
     * アクションごとの権限定義
     */
    public function declareRole()
    {
        return [
            // 設定画面
            'listQuizzes' => ['role_article_admin'],
            'createQuiz' => ['role_article_admin'],
            'selectQuiz' => ['role_article_admin'],
            'copyQuizForFrame' => ['role_article_admin'],
            'editQuizSettings' => ['role_article_admin'],
            'manageCategories' => ['role_article_admin'],
            'saveQuizSettings' => ['role_article_admin'],
            'saveCategories' => ['role_article_admin'],
            'editView' => ['role_article_admin'],
            'saveView' => ['role_article_admin'],
            'editBucketsRoles' => ['role_article_admin'],
            'saveBucketsRoles' => ['role_article_admin'],
            'editBucketsMails' => ['role_article_admin'],
            'saveBucketsMails' => ['role_article_admin'],

            // 問題編集・採点
            'createFirstQuestion' => ['role_article_admin'],
            'createPage' => ['role_article_admin'],
            'editPage' => ['role_article_admin'],
            'savePage' => ['role_article_admin'],
            'editQuestions' => ['role_article_admin'],
            'createQuestion' => ['role_article_admin'],
            'editQuestion' => ['role_article_admin'],
            'saveQuestion' => ['role_article_admin'],
            'deleteQuestion' => ['role_article_admin'],
            'grading' => ['role_article_admin'],
            // 結果閲覧・CSV出力はモデレータ以上に許可
            'adminResults' => ['role_article'],
            'exportItemResponseCsv' => ['role_article'],
            'exportSpTableCsv' => ['role_article'],
            'exportCategoryResultsCsv' => ['role_article'],
            'gradeAnswer' => ['role_article_admin'],

            // 受験
            'start' => ['role_guest'],
            'answer' => ['role_guest'],
            'review' => ['role_guest'],
            'result' => ['role_guest'],
            'startAttempt' => ['role_guest'],
            'saveAnswer' => ['role_guest'],
            'submitAttempt' => ['role_guest'],
        ];
    }

    /**
     * 小テストIDから小テストを取得します。
     */
    public function getPost($id, $action = null)
    {
        if (empty($id)) {
            return null;
        }
    
        if (!empty($this->post) && (int) $this->post->id === (int) $id) {
            return $this->post;
        }
    
        $this->post = Quizzes::find($id);
    
        return $this->post;
    }

    /**
     * 指定した小テストが現在のフレームに割り当てられていることを確認します。
     */
    private function ensureFrameQuiz($frame_id, $quiz_id)
    {
        $exists = QuizzesFrames::query()
            ->where('frame_id', $frame_id)
            ->where('quiz_id', $quiz_id)
            ->exists();

        if (!$exists) {
            abort(404);
        }
    }

    /**
     * 小テスト通常画面（管理者用）
     *
     * 現在のフレームに割り当てられた小テストと、
     * ページ、ページ共通本文、問題の現行リビジョンを表示します。
     */
    public function index($request, $page_id, $frame_id)
    {
        $can_manage_quiz = $this->checkRoleFromFrame(
            Auth::user(),
            'role_article_admin',
            $this->frame
        );
        $can_view_admin_results = $this->checkRoleFromFrame(
            Auth::user(),
            'role_article',
            $this->frame
        );

        $quiz_frame = QuizzesFrames::query()
            ->where('frame_id', $frame_id)
            ->first();

        if (empty($quiz_frame)) {
            return $this->view('default', [
                'page_id' => $page_id,
                'frame_id' => $frame_id,
                'quiz' => null,
                'can_manage_quiz' => $can_manage_quiz,
                'can_view_admin_results' => $can_view_admin_results,
            ]);
        }

        $quiz = Quizzes::query()
            ->withCount('frames')
            ->with([
                'pages' => function ($query) {
                    $query->orderBy('sequence')->orderBy('id');
                },
                'pages.questions' => function ($query) {
                    $query->where('status', 'active')
                        ->withCount('pending_manual_answers')
                        ->orderBy('sequence')
                        ->orderBy('id');
                },
                'pages.questions.current_revision.choices',
                'pages.questions.current_revision.correct_answers',
            ])
            ->find($quiz_frame->quiz_id);

        $tool = new QuizzesTool($request, $page_id, $frame_id, $quiz);

        return $this->view('default', [
            'page_id' => $page_id,
            'frame_id' => $frame_id,
            'quiz' => $quiz,
            'latest_result_attempt' => $tool->latestResultAttempt(),
            'can_manage_quiz' => $can_manage_quiz,
            'can_view_admin_results' => $can_view_admin_results,
        ]);
    }

    /**
     * 最初の問題作成画面
     *
     * 出題ページが未作成の場合は最初の出題ページを自動生成し、
     * 問題作成画面へ遷移します。既に出題ページがある場合は、
     * 先頭の出題ページを再利用します。
     */
    public function createFirstQuestion($request, $page_id, $frame_id, $quiz_id)
    {
        $this->ensureFrameQuiz($frame_id, $quiz_id);

        $quiz = Quizzes::findOrFail($quiz_id);
        $quiz_page = QuizzesPages::query()
            ->where('quiz_id', $quiz->id)
            ->orderBy('sequence')
            ->orderBy('id')
            ->first();

        if (empty($quiz_page)) {
            /** @var QuizPageService $service */
            $service = app(QuizPageService::class);
            $quiz_page = $service->savePage([
                'quiz_id' => $quiz->id,
                'title' => null,
                'description' => null,
            ]);
        }

        return redirect(
            url('/')
            . '/plugin/quizzes/createQuestion/'
            . $page_id . '/'
            . $frame_id . '/'
            . $quiz_page->id
            . '#frame-' . $frame_id
        );
    }

    /**
     * 出題ページ新規作成画面
     */
    public function createPage($request, $page_id, $frame_id, $quiz_id)
    {
        $this->ensureFrameQuiz($frame_id, $quiz_id);

        $quiz = Quizzes::findOrFail($quiz_id);

        $quiz_page = new QuizzesPages([
            'quiz_id' => $quiz->id,
            'title' => null,
            'description' => null,
        ]);

        return $this->view('quizzes_page_edit', [
            'quiz' => $quiz,
            'quiz_page' => $quiz_page,
            'is_create' => true,
        ])->withInput($request->all);
    }

    /**
     * ページ編集画面
     */
    public function editPage($request, $page_id, $frame_id, $quiz_page_id)
    {
        $quiz_page = QuizzesPages::with('quiz')->findOrFail($quiz_page_id);
        $this->ensureFrameQuiz($frame_id, $quiz_page->quiz_id);

        return $this->view('quizzes_page_edit', [
            'quiz' => $quiz_page->quiz,
            'quiz_page' => $quiz_page,
            'is_create' => false,
        ])->withInput($request->all);
    }

    /**
     * ページ保存
     */
    public function savePage($request, $page_id, $frame_id, $quiz_page_id = null)
    {
        $validator = Validator::make($request->all(), [
            'quiz_id' => ['required', 'integer', 'exists:quizzes,id'],
            'title' => ['nullable', 'string', 'max:191'],
            'description' => ['nullable', 'string'],
        ]);

        $validator->setAttributeNames([
            'title' => 'ページタイトル',
            'description' => '共通問題文・資料',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        /** @var QuizPageService $service */
        $service = app(QuizPageService::class);
        $quiz_page = $service->savePage(
            $validator->validated(),
            $quiz_page_id
        );

        $request->flash_message = '設定を保存しました。';

        if ($request->input('after_save') === 'continue') {
            $redirect_path = url('/')
                . '/plugin/quizzes/editPage/'
                . $page_id . '/'
                . $frame_id . '/'
                . $quiz_page->id
                . '#frame-' . $frame_id;
        } else {
            $redirect_path = $request->input('normal_page_path');
        }

        $request->merge([
            'redirect_path' => $redirect_path,
        ]);
    }

    /**
     * 旧問題一覧URLとの互換処理
     *
     * 問題一覧は通常画面へ統合しています。
     */
    public function editQuestions($request, $page_id, $frame_id, $quiz_id)
    {
        return $this->index($request, $page_id, $frame_id);
    }

    /**
     * 問題新規作成画面
     */
    public function createQuestion($request, $page_id, $frame_id, $quiz_page_id)
    {
        $quiz_page = QuizzesPages::with('quiz')->findOrFail($quiz_page_id);
        $this->ensureFrameQuiz($frame_id, $quiz_page->quiz_id);

        $category_groups = $quiz_page->quiz->use_category_scoring
            ? $quiz_page->quiz->active_category_groups()
                ->with('active_categories')
                ->get()
            : collect();

        return $this->view('quizzes_question_edit', [
            'quiz' => $quiz_page->quiz,
            'quiz_page' => $quiz_page,
            'question' => new QuizzesQuestions(),
            'revision' => null,
            'category_groups' => $category_groups,
            'is_create' => true,
        ])->withInput($request->all);
    }

    /**
     * 問題編集画面
     */
    public function editQuestion($request, $page_id, $frame_id, $question_id)
    {
        $question = QuizzesQuestions::with([
            'quiz_page.quiz',
            'current_revision.choices',
            'current_revision.correct_answers',
            'current_revision.categories',
        ])->findOrFail($question_id);
        $this->ensureFrameQuiz($frame_id, $question->quiz_page->quiz_id);

        $quiz = $question->quiz_page->quiz;
        $category_groups = $quiz->use_category_scoring
            ? $quiz->active_category_groups()
                ->with('active_categories')
                ->get()
            : collect();

        return $this->view('quizzes_question_edit', [
            'quiz' => $quiz,
            'quiz_page' => $question->quiz_page,
            'question' => $question,
            'revision' => $question->current_revision,
            'category_groups' => $category_groups,
            'is_create' => false,
        ])->withInput($request->all);
    }

    /**
     * 問題保存
     *
     * 回答が存在しない場合は現行Revisionを更新し、
     * 回答が存在する場合は新Revisionを作成します。
     */
    public function saveQuestion($request, $page_id, $frame_id, $question_id = null)
    {
        $validator = Validator::make($request->all(), [
            'quiz_id' => ['required', 'integer', 'exists:quizzes,id'],
            'quiz_page_id' => ['required', 'integer', 'exists:quiz_pages,id'],
            'question_type' => [
                'required',
                'in:single_choice,multiple_choice,word,multiple_word,essay',
            ],
            'question_text' => ['required', 'string'],
            'points' => ['required', 'numeric', 'min:0'],
            'commentary' => ['nullable', 'string'],
            'model_answer' => ['nullable', 'string'],
            'grading_guide' => ['nullable', 'string'],
            'choice_random' => ['nullable', 'boolean'],
            'answer_order_fixed' => ['nullable', 'boolean'],
            'normalization_options' => ['nullable', 'array'],
            'answer_rows' => ['nullable', 'integer', 'min:1'],
            'character_limit' => ['nullable', 'integer', 'min:1'],
            'sequence' => ['nullable', 'integer', 'min:0'],
            'choices' => ['nullable', 'array'],
            'choices.*.text' => ['nullable', 'string'],
            'choices.*.is_correct' => ['nullable', 'boolean'],
            'correct_answers' => ['nullable', 'array'],
            'correct_answers.*.answer_group' => ['nullable', 'integer', 'min:1'],
            'correct_answers.*.answer_text' => ['nullable', 'string'],
            'correct_answers.*.sequence' => ['nullable', 'integer', 'min:1'],
            'category_assignment_present' => ['nullable', 'boolean'],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['integer', 'distinct'],
        ]);

        $validator->setAttributeNames([
            'quiz_page_id' => '出題ページ',
            'question_type' => '問題形式',
            'question_text' => '問題文',
            'points' => '配点',
            'commentary' => '解説',
            'model_answer' => '模範解答',
            'grading_guide' => '採点基準',
            'answer_rows' => '回答欄の行数',
            'character_limit' => '文字数上限',
            'sequence' => '表示順',
            'category_ids' => 'カテゴリー',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        /** @var QuizQuestionService $service */
        $service = app(QuizQuestionService::class);
        $question = $service->saveQuestion(
            $validator->validated(),
            $question_id,
            Auth::id()
        );

        $request->flash_message = '設定を保存しました。';

        if ($request->input('after_save') === 'continue') {
            $redirect_path = url('/')
                . '/plugin/quizzes/editQuestion/'
                . $page_id . '/'
                . $frame_id . '/'
                . $question->id
                . '#frame-' . $frame_id;
        } else {
            $redirect_path = $request->input('normal_page_path');
        }

        $request->merge([
            'redirect_path' => $redirect_path,
        ]);
    }

    /**
     * 問題削除
     */
    public function deleteQuestion($request, $page_id, $frame_id, $question_id)
    {
        $question = QuizzesQuestions::with('quiz_page')->findOrFail($question_id);
        $this->ensureFrameQuiz($frame_id, $question->quiz_page->quiz_id);

        /** @var QuizQuestionService $service */
        $service = app(QuizQuestionService::class);
        $service->deleteQuestion($question_id);

        $request->flash_message = '問題を削除しました。';
        $request->merge([
            'redirect_path' => $request->input('normal_page_path'),
        ]);
    }

    /**
     * 受験開始確認画面
     */
    public function start($request, $page_id, $frame_id, $quiz_id)
    {
        $quiz = Quizzes::with('pages.questions.current_revision')
            ->published()
            ->findOrFail($quiz_id);

        $tool = new QuizzesTool($request, $page_id, $frame_id, $quiz);

        $question_count = $quiz->pages->sum(function ($quiz_page) {
            return $quiz_page->questions
                ->where('status', 'active')
                ->filter(function ($question) {
                    return !empty($question->current_revision);
                })
                ->count();
        });

        $total_points = $quiz->pages->sum(function ($quiz_page) {
            return $quiz_page->questions
                ->where('status', 'active')
                ->sum(function ($question) {
                    return !empty($question->current_revision)
                        ? (float)$question->current_revision->points
                        : 0;
                });
        });

        return $this->view('quizzes_start', [
            'quiz' => $quiz,
            'tool' => $tool,
            'question_count' => $question_count,
            'total_points' => $total_points,
            'in_progress_attempt' => $tool->inProgressAttempt(),
            'latest_attempt' => $tool->latestAttempt(),
        ]);
    }

    /**
     * Attemptを作成して受験を開始
     */
    public function startAttempt($request, $page_id, $frame_id, $quiz_id)
    {
        $this->ensureFrameQuiz($frame_id, $quiz_id);

        /** @var QuizAttemptService $service */
        $service = app(QuizAttemptService::class);
        $attempt = $service->startAttempt(
            $quiz_id,
            Auth::id(),
            $page_id,
            $frame_id,
            (bool) $request->boolean('is_preview')
        );

        $request->merge([
            'redirect_path' => url('/')
                . '/plugin/quizzes/answer/'
                . $page_id . '/'
                . $frame_id . '/'
                . $attempt->id
                . '#frame-' . $frame_id,
        ]);
    }

    /**
     * 回答画面
     */
    public function answer($request, $page_id, $frame_id, $attempt_id)
    {
        /** @var QuizAttemptService $service */
        $service = app(QuizAttemptService::class);
        $attempt = $service->getAnswerDisplayAttempt($attempt_id, Auth::id());
        $this->ensureFrameQuiz($frame_id, $attempt->quiz_id);

        return $this->view('quizzes_answer', [
            'attempt' => $attempt,
        ]);
    }

    /**
     * 回答の一時保存
     */
    public function saveAnswer($request, $page_id, $frame_id, $target_id)
    {
        $validator = Validator::make($request->all(), [
            'attempt_id' => ['required', 'integer', 'exists:quiz_attempts,id'],
            'answer_data' => ['nullable'],
            'answers' => ['nullable', 'array'],
            'answers.*' => ['nullable', 'array'],
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        /** @var QuizAttemptService $attempt_service */
        $attempt_service = app(QuizAttemptService::class);
        $attempt = $attempt_service->getAnsweringAttempt(
            (int) $request->attempt_id,
            Auth::id()
        );
        $this->ensureFrameQuiz($frame_id, $attempt->quiz_id);

        /** @var QuizAnswerService $service */
        $service = app(QuizAnswerService::class);

        if ($request->has('answers')) {
            foreach ($request->input('answers', []) as $attempt_question_id => $answer_data) {
                unset($answer_data['_present']);

                $service->saveAnswer(
                    (int) $request->attempt_id,
                    (int) $attempt_question_id,
                    $answer_data,
                    Auth::id()
                );
            }
        } else {
            // 従来の小問単位保存URLとの互換処理
            $service->saveAnswer(
                (int) $request->attempt_id,
                $target_id,
                $request->input('answer_data'),
                Auth::id()
            );
        }

        $request->flash_message = 'この画面の回答を保存しました。';
        $request->merge([
            'redirect_path' => $request->input('after_save') === 'review'
                ? url('/')
                    . '/plugin/quizzes/review/'
                    . $page_id . '/'
                    . $frame_id . '/'
                    . (int) $request->attempt_id
                    . '#frame-' . $frame_id
                : url('/')
                    . '/plugin/quizzes/answer/'
                    . $page_id . '/'
                    . $frame_id . '/'
                    . (int) $request->attempt_id
                    . '#frame-' . $frame_id,
        ]);
    }

    /**
     * 提出前確認画面
     */
    public function review($request, $page_id, $frame_id, $attempt_id)
    {
        /** @var QuizAttemptService $service */
        $service = app(QuizAttemptService::class);
        $attempt = $service->getReviewAttempt($attempt_id, Auth::id());
        $this->ensureFrameQuiz($frame_id, $attempt->quiz_id);

        return $this->view('quizzes_review', [
            'attempt' => $attempt,
        ]);
    }

    /**
     * 小テスト提出・自動採点
     */
    public function submitAttempt($request, $page_id, $frame_id, $attempt_id)
    {
        /** @var QuizAttemptService $attempt_service */
        $attempt_service = app(QuizAttemptService::class);
        $answering_attempt = $attempt_service->getSubmittableAttempt(
            $attempt_id,
            Auth::id()
        );
        $this->ensureFrameQuiz($frame_id, $answering_attempt->quiz_id);

        /** @var QuizSubmissionService $service */
        $service = app(QuizSubmissionService::class);
        $attempt = $service->submitAttempt($attempt_id, Auth::id());

        $request->flash_message = '小テストを提出しました。';
        $request->merge([
            'redirect_path' => url('/')
                . '/plugin/quizzes/result/'
                . $page_id . '/'
                . $frame_id . '/'
                . $attempt->id
                . '#frame-' . $frame_id,
        ]);
    }

    /**
     * 結果画面
     */
    public function result($request, $page_id, $frame_id, $attempt_id)
    {
        /** @var QuizAttemptService $service */
        $service = app(QuizAttemptService::class);
        $attempt = $service->getResultAttempt($attempt_id, Auth::id());
        $this->ensureFrameQuiz($frame_id, $attempt->quiz_id);

        $show_statistics = $attempt->status === 'graded'
            && (
                $attempt->quiz->show_average_score
                || $attempt->quiz->show_highest_score
                || $attempt->quiz->show_lowest_score
                || $attempt->quiz->show_participant_count
                || $attempt->quiz->show_score_distribution
            );

        $statistics = null;
        if ($show_statistics) {
            /** @var QuizResultStatisticsService $statistics_service */
            $statistics_service = app(QuizResultStatisticsService::class);
            $statistics = $statistics_service->forQuiz(
                $attempt->quiz_id,
                (float) $attempt->effective_max_score
            );
        }

        /** @var QuizCategoryResultService $category_result_service */
        $category_result_service = app(QuizCategoryResultService::class);
        $category_results = $category_result_service->forAttempt(
            (int) $attempt->id,
            (int) $attempt->quiz_id
        );

        return $this->view('quizzes_result', [
            'attempt' => $attempt,
            'statistics' => $statistics,
            'category_results' => $category_results,
        ]);
    }

    /**
     * 管理者向け結果画面
     */
    public function adminResults($request, $page_id, $frame_id, $quiz_id)
    {
        $this->ensureFrameQuiz($frame_id, $quiz_id);
        $quiz = Quizzes::findOrFail($quiz_id);

        /** @var QuizAdminResultService $service */
        $service = app(QuizAdminResultService::class);
        $results = $service->forQuiz((int) $quiz->id);

        $selected_attempt = null;
        $selected_attempt_id = (int) $request->input('attempt_id');
        if ($selected_attempt_id > 0) {
            $selected_attempt = $results->attempts->firstWhere(
                'id',
                $selected_attempt_id
            );
        }

        /** @var QuizCategoryResultService $category_result_service */
        $category_result_service = app(QuizCategoryResultService::class);
        $category_averages = $category_result_service->averagesForQuiz((int) $quiz->id);

        $selected_category_results = [];
        if (!empty($selected_attempt)) {
            $selected_category_results = $category_result_service->forAttempt(
                (int) $selected_attempt->id,
                (int) $quiz->id
            );
        }

        return $this->view('quizzes_admin_results', [
            'quiz' => $quiz,
            'results' => $results,
            'selected_attempt' => $selected_attempt,
            'selected_category_results' => $selected_category_results,
            'category_averages' => $category_averages,
        ]);
    }

    /**
     * 小問別反応表CSV
     */
    public function exportItemResponseCsv($request, $page_id, $frame_id, $quiz_id)
    {
        $this->ensureFrameQuiz($frame_id, $quiz_id);

        /** @var QuizAnalysisCsvService $service */
        $service = app(QuizAnalysisCsvService::class);

        return $service->itemResponse((int) $quiz_id);
    }

    /**
     * SP表CSV
     */
    public function exportSpTableCsv($request, $page_id, $frame_id, $quiz_id)
    {
        $this->ensureFrameQuiz($frame_id, $quiz_id);

        /** @var QuizAnalysisCsvService $service */
        $service = app(QuizAnalysisCsvService::class);

        return $service->spTable((int) $quiz_id);
    }

    /**
     * カテゴリー別結果CSV
     */
    public function exportCategoryResultsCsv($request, $page_id, $frame_id, $quiz_id)
    {
        $this->ensureFrameQuiz($frame_id, $quiz_id);

        /** @var QuizAnalysisCsvService $service */
        $service = app(QuizAnalysisCsvService::class);

        return $service->categoryResults((int) $quiz_id);
    }

    /**
     * 記述式問題の採点画面
     */
    public function grading($request, $page_id, $frame_id, $question_id)
    {
        $question = QuizzesQuestions::with([
            'quiz_page.quiz',
            'current_revision',
        ])->findOrFail($question_id);
        $this->ensureFrameQuiz($frame_id, $question->quiz_page->quiz_id);

        /** @var QuizManualGradeService $service */
        $service = app(QuizManualGradeService::class);

        return $this->view('quizzes_grading', [
            'question' => $question,
            'answers' => $service->getPendingAnswersByQuestion($question_id),
        ]);
    }

    /**
     * 手動採点
     */
    public function gradeAnswer($request, $page_id, $frame_id, $answer_id)
    {
        $answer = QuizzesAnswers::with([
            'attempt_question.quiz_question.quiz_page',
        ])->findOrFail($answer_id);
        $question = $answer->attempt_question->quiz_question;
        $this->ensureFrameQuiz($frame_id, $question->quiz_page->quiz_id);

        $max_score = (float)$answer->attempt_question->points;
        $validator = Validator::make($request->all(), [
            'score' => ['required', 'numeric', 'min:0', 'max:' . $max_score],
            'correctness' => ['required', 'in:correct,partial,incorrect,not_applicable'],
            'reason' => ['nullable', 'string', 'max:65535'],
            'comment' => ['nullable', 'string'],
            'internal_comment' => ['nullable', 'string'],
        ]);

        $validator->setAttributeNames([
            'score' => '得点',
            'correctness' => '判定',
            'reason' => '採点理由',
            'comment' => '受験者へのコメント',
            'internal_comment' => '管理者用メモ',
        ]);

        if ($validator->fails()) {
            return redirect()
                ->to(url('/')
                    . '/plugin/quizzes/grading/'
                    . $page_id . '/'
                    . $frame_id . '/'
                    . $question->id
                    . '#grading-answer-' . $answer->id)
                ->withErrors($validator)
                ->withInput($request->all() + [
                    'grading_answer_id' => $answer->id,
                ]);
        }

        /** @var QuizManualGradeService $service */
        $service = app(QuizManualGradeService::class);
        $service->gradeAnswer(
            $answer->id,
            $validator->validated(),
            Auth::id()
        );

        $request->flash_message = '採点結果を保存しました。';
        $request->merge([
            'redirect_path' => url('/')
                . '/plugin/quizzes/grading/'
                . $page_id . '/'
                . $frame_id . '/'
                . $question->id
                . '#frame-' . $frame_id,
        ]);
    }

    /**
     * 小テスト一覧
     *
     * 登録済み小テストの一覧と、
     * 現在のフレームに割り当てられている小テストを取得します。
     */
    public function listQuizzes($request, $page_id, $frame_id)
    {
        // 登録済み小テスト一覧
        $quizzes = Quizzes::query()
            ->withCount('frames')
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->get();
    
        // 現在のフレームに割り当てられている情報
        $quiz_frame = QuizzesFrames::query()
            ->where('frame_id', $frame_id)
            ->first();
    
        // 現在選択されている小テストID
        $selected_quiz_id = $quiz_frame
            ? $quiz_frame->quiz_id
            : null;
    
        return $this->view('quizzes_list', [
            'page_id' => $page_id,
            'frame_id' => $frame_id,
            'quizzes' => $quizzes,
            'selected_quiz_id' => $selected_quiz_id,
        ]);
    }
    
    /**
     * フレームで使用する小テストを選択
     */
    public function selectQuiz($request, $page_id, $frame_id)
    {
        $validator = Validator::make($request->all(), [
            'quiz_id' => [
                'required',
                'integer',
                'exists:quizzes,id',
            ],
            'selection_mode' => [
                'required',
                'in:share,copy',
            ],
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        if ($request->input('selection_mode') === 'copy') {
            /** @var QuizCopyService $service */
            $service = app(QuizCopyService::class);
            $quiz = $service->copyForFrame(
                (int) $request->input('quiz_id'),
                (int) $frame_id,
                Auth::id()
            );

            $request->flash_message = '小テストを複製しました。名前と設定を確認してください。';
            $request->merge([
                'redirect_path' => url('/')
                    . '/plugin/quizzes/editQuizSettings/'
                    . $page_id . '/'
                    . $frame_id . '/'
                    . $quiz->id
                    . '#frame-' . $frame_id,
            ]);

            return;
        }

        // 既存の重複割当を解消し、このフレームの割当を1件に固定する。
        QuizzesFrames::where('frame_id', $frame_id)->delete();
        QuizzesFrames::create([
            'frame_id' => $frame_id,
            'quiz_id' => $request->input('quiz_id'),
        ]);

        $request->flash_message = '小テストを共有して使用する設定にしました。';
        $request->merge([
            'redirect_path' => $request->input('normal_page_path'),
        ]);
    }

    /**
     * 共有中の小テストを複製し、現在のフレームだけ複製先へ切り替えます。
     */
    public function copyQuizForFrame($request, $page_id, $frame_id, $quiz_id)
    {
        $this->ensureFrameQuiz($frame_id, $quiz_id);

        /** @var QuizCopyService $service */
        $service = app(QuizCopyService::class);
        $quiz = $service->copyForFrame(
            (int) $quiz_id,
            (int) $frame_id,
            Auth::id()
        );

        $request->flash_message = '小テストを複製しました。元の小テストとは別に編集できます。';
        $request->merge([
            'redirect_path' => url('/')
                . '/plugin/quizzes/editQuizSettings/'
                . $page_id . '/'
                . $frame_id . '/'
                . $quiz->id
                . '#frame-' . $frame_id,
        ]);
    }

    /**
     * 小テスト新規作成画面
     */
    public function createQuiz($request, $page_id, $frame_id)
    {
        $quiz = new Quizzes([
            'status' => 'draft',
            'retry_type' => 'unlimited',
            'passing_type' => 'none',
            'perfect_score' => 0,
            'use_category_scoring' => false,
            'show_score' => true,
            'show_pass_status' => true,
            'show_question_result' => false,
            'show_correct_answer' => false,
            'show_commentary' => false,
            'show_grading_comment' => true,
            'result_display_timing' => 'after_grading',
        ]);
    
        return $this->view('quizzes_create', [
            'quiz' => $quiz,
            'page_id' => $page_id,
            'frame_id' => $frame_id,
        ])->withInput($request->all);
    }

    /**
     * 小テスト設定
     */
    public function editQuizSettings(
        $request,
        $page_id,
        $frame_id,
        $quiz_id = null
    ) {
        if (empty($quiz_id)) {
            $quiz_frame = QuizzesFrames::query()
                ->where('frame_id', $frame_id)
                ->first();
    
            $quiz_id = $quiz_frame
                ? $quiz_frame->quiz_id
                : null;
        }
    
        $quiz = $quiz_id
            ? Quizzes::withCount('frames')->find($quiz_id)
            : null;
    
        return $this->view('quizzes_settings', [
            'page_id' => $page_id,
            'frame_id' => $frame_id,
            'quiz_id' => $quiz_id,
            'quiz' => $quiz,
        ]);
    }


    /**
     * カテゴリー管理画面
     */
    public function manageCategories($request, $page_id, $frame_id, $quiz_id)
    {
        $this->ensureFrameQuiz($frame_id, $quiz_id);

        $quiz = Quizzes::with([
            'category_groups.categories',
        ])->findOrFail($quiz_id);

        if (!$quiz->use_category_scoring) {
            abort(404);
        }

        /** @var QuizCategoryService $service */
        $service = app(QuizCategoryService::class);

        return $this->view('quizzes_categories', [
            'quiz' => $quiz,
            'category_groups' => $quiz->category_groups,
            'unassigned_question_count' =>
                $service->unassignedQuestionCount($quiz->id),
        ])->withInput($request->all);
    }

    /**
     * カテゴリー管理を保存
     */
    public function saveCategories($request, $page_id, $frame_id, $quiz_id)
    {
        $this->ensureFrameQuiz($frame_id, $quiz_id);

        $validator = Validator::make($request->all(), [
            'groups' => ['nullable', 'array', 'max:100'],
            'groups.*.id' => ['nullable', 'integer'],
            'groups.*.name' => ['required', 'string', 'max:255'],
            'groups.*.sequence' => ['required', 'integer', 'min:1'],
            'groups.*.is_active' => ['nullable', 'boolean'],
            'groups.*.categories' => ['nullable', 'array', 'max:200'],
            'groups.*.categories.*.id' => ['nullable', 'integer'],
            'groups.*.categories.*.name' => ['required', 'string', 'max:255'],
            'groups.*.categories.*.sequence' => ['required', 'integer', 'min:1'],
            'groups.*.categories.*.is_active' => ['nullable', 'boolean'],
        ]);

        $validator->setAttributeNames([
            'groups.*.name' => 'グループ名',
            'groups.*.sequence' => 'グループの表示順',
            'groups.*.categories.*.name' => 'カテゴリー項目名',
            'groups.*.categories.*.sequence' => 'カテゴリー項目の表示順',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        /** @var QuizCategoryService $service */
        $service = app(QuizCategoryService::class);
        $service->save((int) $quiz_id, $validator->validated()['groups'] ?? []);

        session()->flash(
            'quizzes_category_success',
            'カテゴリー設定を保存しました。'
        );
        $request->flash_message = 'カテゴリー設定を保存しました。';
        $request->merge([
            'redirect_path' => $request->input('back_path'),
        ]);
    }

    /**
     * 小テスト設定保存
     */
    public function saveQuizSettings(
        $request,
        $page_id,
        $frame_id,
        $quiz_id = null
    ) {
        $form_mode = $request->input('form_mode');
    
        /*
         * 新規作成
         */
        if ($form_mode === 'create') {
    
            $validator = Validator::make($request->all(), [
                'title' => [
                    'required',
                    'string',
                    'max:191',
                ],
                'description' => [
                    'nullable',
                    'string',
                ],
            ]);
    
            $validator->setAttributeNames([
                'title' => '小テスト名',
                'description' => '説明',
            ]);
    
            if ($validator->fails()) {
                return redirect()
                    ->back()
                    ->withErrors($validator)
                    ->withInput();
            }
    
            $quiz = new Quizzes();
    
            /*
             * Quizzesモデルに作成した初期値設定メソッド
             */
            $quiz->fillDefaultValues();
    
            $quiz->title = $request->input('title');
            $quiz->description = $request->input('description');
            $quiz->created_id = Auth::id();
            $quiz->updated_id = Auth::id();
    
            $quiz->save();
    
            /*
             * 作成した小テストを現在のフレームへ割り当て
             */
            QuizzesFrames::where('frame_id', $frame_id)->delete();
            QuizzesFrames::create([
                'frame_id' => $frame_id,
                'quiz_id' => $quiz->id,
            ]);
    
            /*
             * 新規作成後は必ず設定変更画面へ進む
             */
            $request->flash_message = '小テストを作成しました。';
    
            $request->merge([
                'redirect_path' =>
                    url('/')
                    . '/plugin/quizzes/editQuizSettings/'
                    . $page_id
                    . '/'
                    . $frame_id
                    . '/'
                    . $quiz->id
                    . '#frame-'
                    . $frame_id,
            ]);
    
            return;
        }
    
        /*
         * 設定変更
         */
        if ($form_mode === 'settings') {
    
            $quiz = Quizzes::findOrFail($quiz_id);
    
            $validator = Validator::make($request->all(), [
                'status' => [
                    'required',
                    'in:draft,published,closed',
                ],
                'publish_start_at' => [
                    'nullable',
                    'date',
                ],
                'publish_end_at' => [
                    'nullable',
                    'date',
                    'after_or_equal:publish_start_at',
                ],
                'estimated_minutes' => [
                    'nullable',
                    'integer',
                    'min:1',
                    'max:65535',
                ],
                'time_limit_minutes' => [
                    'nullable',
                    'integer',
                    'min:1',
                    'max:65535',
                ],
                'retry_type' => [
                    'required',
                    'in:unlimited,limited,once',
                ],
                'retry_limit' => [
                    'nullable',
                    'required_if:retry_type,limited',
                    'integer',
                    'min:1',
                    'max:65535',
                ],
                'passing_type' => [
                    'required',
                    'in:none,score,rate',
                ],
                'passing_score' => [
                    'nullable',
                    'required_if:passing_type,score',
                    'numeric',
                    'min:0',
                ],
                'passing_rate' => [
                    'nullable',
                    'required_if:passing_type,rate',
                    'numeric',
                    'min:0',
                    'max:100',
                ],
                                'result_display_timing' => [
                    'required',
                    'in:immediately,after_grading,manual',
                ],
                'use_category_scoring' => [
                    'required',
                    'boolean',
                ],
            ]);
    
            $validator->setAttributeNames([
                'status' => '公開状態',
                'publish_start_at' => '公開開始日時',
                'publish_end_at' => '公開終了日時',
                'estimated_minutes' => '所要時間の目安',
                'time_limit_minutes' => '制限時間',
                'retry_type' => '再受験設定',
                'retry_limit' => '受験可能回数',
                'passing_type' => '合格判定方式',
                'passing_score' => '合格点',
                'passing_rate' => '合格率',
                'result_display_timing' => '結果表示時期',
                'use_category_scoring' => 'カテゴリー別採点',
            ]);
    
            if ($validator->fails()) {
                return redirect()
                    ->back()
                    ->withErrors($validator)
                    ->withInput();
            }
    
            /*
             * チェックボックスは未送信時にfalseとする
             */
            $quiz->status = $request->input('status');
            $quiz->publish_start_at = $request->input('publish_start_at');
            $quiz->publish_end_at = $request->input('publish_end_at');
            $quiz->estimated_minutes = $request->input('estimated_minutes');
            $quiz->time_limit_minutes = $request->input('time_limit_minutes');
            
            $quiz->retry_type = $request->input('retry_type');

            $quiz->retry_limit =
                $request->input('retry_type') === 'limited'
                    ? $request->input('retry_limit')
                    : null;
            
            $quiz->passing_type = $request->input('passing_type');
            
            $quiz->passing_score =
                $request->input('passing_type') === 'score'
                    ? $request->input('passing_score')
                    : null;
            
            $quiz->passing_rate =
                $request->input('passing_type') === 'rate'
                    ? $request->input('passing_rate')
                    : null;
                    
            $quiz->result_display_timing =
                $request->input('result_display_timing');

            $quiz->use_category_scoring =
                $request->boolean('use_category_scoring');
    
            $quiz->show_score =
                $request->boolean('show_score');
    
            $quiz->show_pass_status =
                $request->boolean('show_pass_status');
    
            $quiz->show_question_result =
                $request->boolean('show_question_result');

            $quiz->show_user_answer =
                $request->boolean('show_user_answer');

            $quiz->show_average_score =
                $request->boolean('show_average_score');

            $quiz->show_highest_score =
                $request->boolean('show_highest_score');

            $quiz->show_lowest_score =
                $request->boolean('show_lowest_score');

            $quiz->show_participant_count =
                $request->boolean('show_participant_count');

            $quiz->show_score_distribution =
                $request->boolean('show_score_distribution');
    
            $quiz->show_correct_answer =
                $request->boolean('show_correct_answer');
    
            $quiz->show_commentary =
                $request->boolean('show_commentary');
    
            $quiz->show_grading_comment =
                $request->boolean('show_grading_comment');
    
            $quiz->updated_id = Auth::id();
    
            $quiz->save();
    
            /*
             * 「保存して続ける」
             */
            if ($request->input('after_save') === 'continue') {
    
                $request->flash_message = '設定を保存しました。';
    
                $request->merge([
                    'redirect_path' =>
                        url('/')
                        . '/plugin/quizzes/editQuizSettings/'
                        . $page_id
                        . '/'
                        . $frame_id
                        . '/'
                        . $quiz->id
                        . '#frame-'
                        . $frame_id,
                ]);
    
                return;
            }
    
            /*
             * 「保存して戻る」
             */
            $request->flash_message = '設定を保存しました。';
    
            $request->merge([
                'redirect_path' =>
                    $request->input('normal_page_path'),
            ]);
    
            return;
        }
    
        /*
         * form_modeが不正な場合
         */
        abort(400, '保存形式が指定されていません。');
    }

    /**
     * 出題・表示設定
     */
    public function editView($request, $page_id, $frame_id)
    {
        $quiz_frame = QuizzesFrames::query()
            ->where('frame_id', $frame_id)
            ->first();

        $quiz = $quiz_frame
            ? Quizzes::withCount('frames')->find($quiz_frame->quiz_id)
            : null;

        return $this->view('quizzes_frame_edit', [
            'page_id' => $page_id,
            'frame_id' => $frame_id,
            'quiz' => $quiz,
        ])->withInput($request->all);
    }

    /**
     * 出題・表示設定保存
     */
    public function saveView($request, $page_id, $frame_id)
    {
        $quiz_frame = QuizzesFrames::query()
            ->where('frame_id', $frame_id)
            ->firstOrFail();

        $quiz = Quizzes::findOrFail($quiz_frame->quiz_id);

        $validator = Validator::make($request->all(), [
            'question_order' => [
                'required',
                'in:registered,random',
            ],
            'question_display' => [
                'required',
                'in:page,one_by_one',
            ],
            'question_number_format' => [
                'required',
                'in:numeric,q,none',
            ],
        ]);

        $validator->setAttributeNames([
            'question_order' => '出題順',
            'question_display' => '問題の表示単位',
            'question_number_format' => '受験画面の問題番号',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $quiz->question_order = $request->input('question_order');
        $quiz->question_display = $request->input('question_display');
        $quiz->question_number_format =
            $request->input('question_number_format');
        $quiz->updated_id = Auth::id();
        $quiz->save();

        $request->flash_message = '設定を保存しました。';

        if ($request->input('after_save') === 'continue') {
            $redirect_path = url('/')
                . '/plugin/quizzes/editView/'
                . $page_id . '/'
                . $frame_id
                . '#frame-' . $frame_id;
        } else {
            $redirect_path = $request->input('normal_page_path');
        }

        $request->merge([
            'redirect_path' => $redirect_path,
        ]);
    }
}
