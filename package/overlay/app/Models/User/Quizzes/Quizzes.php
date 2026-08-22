<?php

namespace App\Models\User\Quizzes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 小テスト本体モデル
 */
class Quizzes extends Model
{
    use SoftDeletes;

    /** 公開状態 */
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLIC = 'public';

    /**
     * 既存コード・既存データとの移行互換用。
     * statusの正式値は STATUS_PUBLIC を使用する。
     */
    public const STATUS_PUBLISHED_LEGACY = 'published';

    /** 再受験設定 */
    public const RETRY_TYPE_UNLIMITED = 'unlimited';
    public const RETRY_TYPE_LIMITED = 'limited';
    public const RETRY_TYPE_ONCE = 'once';

    /** 合格判定方法 */
    public const PASSING_TYPE_NONE = 'none';
    public const PASSING_TYPE_SCORE = 'score';
    public const PASSING_TYPE_RATE = 'rate';

    /** 結果表示時期 */
    public const RESULT_DISPLAY_TIMING_IMMEDIATELY = 'immediately';
    public const RESULT_DISPLAY_TIMING_AFTER_GRADING = 'after_grading';
    public const RESULT_DISPLAY_TIMING_MANUAL = 'manual';

    /** 出題順 */
    public const QUESTION_ORDER_REGISTERED = 'registered';
    public const QUESTION_ORDER_RANDOM = 'random';

    /** 問題の表示単位 */
    public const QUESTION_DISPLAY_PAGE = 'page';
    public const QUESTION_DISPLAY_ONE_BY_ONE = 'one_by_one';

    /** 受験画面の問題番号 */
    public const QUESTION_NUMBER_NUMERIC = 'numeric';
    public const QUESTION_NUMBER_Q = 'q';
    public const QUESTION_NUMBER_NONE = 'none';

    /**
     * 一括代入を許可するカラム
     */
    protected $fillable = [
        'title',
        'description',
        'status',
        'publish_start_at',
        'publish_end_at',
        'estimated_minutes',
        'time_limit_minutes',
        'retry_type',
        'retry_limit',
        'passing_type',
        'passing_score',
        'passing_rate',
        'perfect_score',
        'use_category_scoring',
        'show_score',
        'show_pass_status',
        'show_question_result',
        'show_user_answer',
        'show_average_score',
        'show_highest_score',
        'show_lowest_score',
        'show_participant_count',
        'show_score_distribution',
        'show_correct_answer',
        'show_commentary',
        'show_grading_comment',
        'result_display_timing',
        'question_order',
        'question_display',
        'question_number_format',
        'created_id',
        'updated_id',
    ];

    /**
     * 属性の型変換
     */
    protected $casts = [
        'publish_start_at' => 'datetime',
        'publish_end_at' => 'datetime',
        'estimated_minutes' => 'integer',
        'time_limit_minutes' => 'integer',
        'retry_limit' => 'integer',
        'passing_score' => 'decimal:2',
        'passing_rate' => 'decimal:2',
        'perfect_score' => 'decimal:2',
        'use_category_scoring' => 'boolean',
        'show_score' => 'boolean',
        'show_pass_status' => 'boolean',
        'show_question_result' => 'boolean',
        'show_user_answer' => 'boolean',
        'show_average_score' => 'boolean',
        'show_highest_score' => 'boolean',
        'show_lowest_score' => 'boolean',
        'show_participant_count' => 'boolean',
        'show_score_distribution' => 'boolean',
        'show_correct_answer' => 'boolean',
        'show_commentary' => 'boolean',
        'show_grading_comment' => 'boolean',
        'created_id' => 'integer',
        'updated_id' => 'integer',
    ];

    /**
     * 新規小テストの初期値を設定する。
     *
     * DBのdefault値だけに依存せず、保存前の画面表示でも同じ初期値を
     * 利用できるようにする。
     */
    public function fillDefaultValues(): self
    {
        $this->status = self::STATUS_DRAFT;
        $this->publish_start_at = null;
        $this->publish_end_at = null;
        $this->estimated_minutes = null;
        $this->time_limit_minutes = null;
        $this->retry_type = self::RETRY_TYPE_UNLIMITED;
        $this->retry_limit = null;
        $this->passing_type = self::PASSING_TYPE_NONE;
        $this->passing_score = null;
        $this->passing_rate = null;
        $this->perfect_score = 0;
        $this->use_category_scoring = false;
        $this->show_score = true;
        $this->show_pass_status = true;
        $this->show_question_result = false;
        $this->show_user_answer = false;
        $this->show_average_score = false;
        $this->show_highest_score = false;
        $this->show_lowest_score = false;
        $this->show_participant_count = false;
        $this->show_score_distribution = false;
        $this->show_correct_answer = false;
        $this->show_commentary = false;
        $this->show_grading_comment = true;
        $this->result_display_timing = self::RESULT_DISPLAY_TIMING_AFTER_GRADING;
        $this->question_order = self::QUESTION_ORDER_REGISTERED;
        $this->question_display = self::QUESTION_DISPLAY_PAGE;
        $this->question_number_format = self::QUESTION_NUMBER_NUMERIC;

        return $this;
    }

    /**
     * 現在公開中の小テストに限定する。
     *
     * 正式値 public に加え、既存コードが保存していた published も
     * 移行期間中は公開として扱う。
     */
    public function scopePublished(Builder $query): Builder
    {
        $now = now();

        return $query
            ->whereIn('status', [
                self::STATUS_PUBLIC,
                self::STATUS_PUBLISHED_LEGACY,
            ])
            ->where(function (Builder $query) use ($now) {
                $query->whereNull('publish_start_at')
                    ->orWhere('publish_start_at', '<=', $now);
            })
            ->where(function (Builder $query) use ($now) {
                $query->whereNull('publish_end_at')
                    ->orWhere('publish_end_at', '>=', $now);
            });
    }

    /**
     * この小テストを利用しているフレーム
     */
    public function frames(): HasMany
    {
        return $this->hasMany(QuizzesFrames::class, 'quiz_id', 'id');
    }

    /**
     * 小テスト内の問題ページ
     */
    public function pages(): HasMany
    {
        return $this->hasMany(QuizzesPages::class, 'quiz_id', 'id')
            ->orderBy('sequence')
            ->orderBy('id');
    }

    /**
     * 小テストのページグループ
     */
    public function page_groups(): HasMany
    {
        return $this->hasMany(QuizzesPageGroups::class, 'quiz_id', 'id')
            ->orderBy('sequence')
            ->orderBy('id');
    }

    /**
     * 小テストの受験履歴
     */
    public function attempts(): HasMany
    {
        return $this->hasMany(QuizzesAttempts::class, 'quiz_id', 'id');
    }

    /**
     * カテゴリー別採点のグループ
     */
    public function category_groups(): HasMany
    {
        return $this->hasMany(QuizzesCategoryGroups::class, 'quiz_id', 'id')
            ->orderBy('sequence')
            ->orderBy('id');
    }

    /**
     * 有効なカテゴリーグループ
     */
    public function active_category_groups(): HasMany
    {
        return $this->category_groups()->where('is_active', true);
    }
}
