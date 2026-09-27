<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Throwable;

class ResetQuizzesDemo extends Command
{
    protected $signature = 'quizzes:reset-demo
                            {quiz_id : 初期化するデモ用小テストのID}
                            {--create-baseline : 現在の受験・解答・採点データを初期状態として保存する}
                            {--force : 確認を省略して実行する}';

    protected $description = '指定した小テストだけの受験・解答・採点データを保存済みの初期状態へ戻す';

    private const TABLE_ORDER = [
        'quiz_attempts',
        'quiz_attempt_pages',
        'quiz_attempt_questions',
        'quiz_attempt_question_choices',
        'quiz_attempt_category_groups',
        'quiz_attempt_categories',
        'quiz_attempt_question_categories',
        'quiz_answers',
        'quiz_answer_grades',
    ];

    private const REQUIRED_TABLES = [
        'quizzes',
        'quiz_attempts',
        'quiz_attempt_pages',
        'quiz_attempt_questions',
        'quiz_attempt_question_choices',
        'quiz_answers',
        'quiz_answer_grades',
    ];

    public function handle()
    {
        $quiz_id = filter_var($this->argument('quiz_id'), FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);

        if ($quiz_id === false) {
            $this->error('quiz_id には1以上の整数を指定してください。');
            return self::FAILURE;
        }

        try {
            $this->assertRequiredTablesExist();

            $quiz = DB::table('quizzes')->where('id', $quiz_id)->first();
            if (!$quiz) {
                $this->error("小テストID {$quiz_id} は存在しません。");
                return self::FAILURE;
            }

            if ($this->option('create-baseline')) {
                return $this->createBaseline((int) $quiz_id, $quiz);
            }

            return $this->restoreBaseline((int) $quiz_id, $quiz);
        } catch (Throwable $e) {
            report($e);
            $this->error('処理を中止しました: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    private function createBaseline(int $quiz_id, $quiz): int
    {
        $path = $this->baselinePath($quiz_id);
        $name = $quiz->name ?? ('ID ' . $quiz_id);

        if (is_file($path) && !$this->option('force')) {
            if (!$this->confirm("小テスト「{$name}」の既存の初期状態を上書きしますか？", false)) {
                $this->warn('保存を中止しました。');
                return self::FAILURE;
            }
        } elseif (!is_file($path) && !$this->option('force')) {
            if (!$this->confirm("小テスト「{$name}」の現在の受験データを初期状態として保存しますか？", false)) {
                $this->warn('保存を中止しました。');
                return self::FAILURE;
            }
        }

        DB::transaction(function () use ($quiz_id, $path) {
            $this->lockQuiz($quiz_id);
            $snapshot = $this->makeSnapshot($quiz_id);
            $this->writeSnapshot($path, $snapshot);
        }, 3);

        $snapshot = $this->readSnapshot($path, $quiz_id);
        $this->info('初期状態を保存しました。');
        $this->line('保存先: ' . $path);
        $this->displayCounts($snapshot['tables']);

        return self::SUCCESS;
    }

    private function restoreBaseline(int $quiz_id, $quiz): int
    {
        $path = $this->baselinePath($quiz_id);
        if (!is_file($path)) {
            $this->error('初期状態が保存されていません。先に次を実行してください。');
            $this->line("php artisan quizzes:reset-demo {$quiz_id} --create-baseline");
            return self::FAILURE;
        }

        $baseline = $this->readSnapshot($path, $quiz_id);
        $name = $quiz->name ?? ('ID ' . $quiz_id);

        if (!$this->option('force')) {
            $this->warn('指定した小テストの現在の受験・解答・採点データは削除されます。');
            if (!$this->confirm("小テスト「{$name}」だけを保存済みの初期状態へ戻しますか？", false)) {
                $this->warn('初期化を中止しました。');
                return self::FAILURE;
            }
        }

        $safety_path = null;

        DB::transaction(function () use ($quiz_id, $baseline, &$safety_path) {
            $this->lockQuiz($quiz_id);

            // 誤操作や復元失敗の調査に使えるよう、削除直前の状態を必ず保存する。
            $current = $this->makeSnapshot($quiz_id);
            $safety_path = $this->safetyBackupPath($quiz_id);
            $this->writeSnapshot($safety_path, $current);

            DB::table('quiz_attempts')->where('quiz_id', $quiz_id)->delete();

            foreach (self::TABLE_ORDER as $table) {
                if (!Schema::hasTable($table)) {
                    continue;
                }

                $rows = $baseline['tables'][$table] ?? [];
                foreach (array_chunk($rows, 500) as $chunk) {
                    DB::table($table)->insert($chunk);
                }
            }

            $restored = $this->makeSnapshot($quiz_id);
            $this->assertSameTableCounts($baseline['tables'], $restored['tables']);
        }, 3);

        $this->info("小テスト「{$name}」を初期状態へ戻しました。");
        $this->line('初期化直前の安全バックアップ: ' . $safety_path);
        $this->displayCounts($baseline['tables']);

        return self::SUCCESS;
    }

    private function makeSnapshot(int $quiz_id): array
    {
        $attempt_ids = $this->ids('quiz_attempts', 'id', 'quiz_id', [$quiz_id]);
        $page_ids = $this->ids('quiz_attempt_pages', 'id', 'quiz_attempt_id', $attempt_ids);
        $question_ids = $this->ids('quiz_attempt_questions', 'id', 'quiz_attempt_page_id', $page_ids);
        $answer_ids = $this->ids('quiz_answers', 'id', 'quiz_attempt_id', $attempt_ids);
        $group_ids = $this->ids('quiz_attempt_category_groups', 'id', 'quiz_attempt_id', $attempt_ids);
        $category_ids = $this->ids('quiz_attempt_categories', 'id', 'quiz_attempt_category_group_id', $group_ids);

        $tables = [
            'quiz_attempts' => $this->rows('quiz_attempts', 'quiz_id', [$quiz_id]),
            'quiz_attempt_pages' => $this->rows('quiz_attempt_pages', 'quiz_attempt_id', $attempt_ids),
            'quiz_attempt_questions' => $this->rows('quiz_attempt_questions', 'quiz_attempt_page_id', $page_ids),
            'quiz_attempt_question_choices' => $this->rows('quiz_attempt_question_choices', 'quiz_attempt_question_id', $question_ids),
            'quiz_attempt_category_groups' => $this->rows('quiz_attempt_category_groups', 'quiz_attempt_id', $attempt_ids),
            'quiz_attempt_categories' => $this->rows('quiz_attempt_categories', 'quiz_attempt_category_group_id', $group_ids),
            'quiz_attempt_question_categories' => $this->rows('quiz_attempt_question_categories', 'quiz_attempt_category_id', $category_ids),
            'quiz_answers' => $this->rows('quiz_answers', 'quiz_attempt_id', $attempt_ids),
            'quiz_answer_grades' => $this->rows('quiz_answer_grades', 'quiz_answer_id', $answer_ids),
        ];

        $snapshot = [
            'format_version' => 1,
            'quiz_id' => $quiz_id,
            'created_at' => now()->toIso8601String(),
            'tables' => $tables,
        ];
        $snapshot['checksum'] = $this->checksum($snapshot);

        return $snapshot;
    }

    private function ids(string $table, string $column, string $foreign_column, array $foreign_ids): array
    {
        if (!Schema::hasTable($table) || empty($foreign_ids)) {
            return [];
        }

        return DB::table($table)
            ->whereIn($foreign_column, $foreign_ids)
            ->orderBy($column)
            ->pluck($column)
            ->map(function ($id) {
                return (int) $id;
            })
            ->all();
    }

    private function rows(string $table, string $foreign_column, array $foreign_ids): array
    {
        if (!Schema::hasTable($table) || empty($foreign_ids)) {
            return [];
        }

        return DB::table($table)
            ->whereIn($foreign_column, $foreign_ids)
            ->orderBy('id')
            ->get()
            ->map(function ($row) {
                return (array) $row;
            })
            ->all();
    }

    private function assertRequiredTablesExist(): void
    {
        $missing = array_values(array_filter(self::REQUIRED_TABLES, function ($table) {
            return !Schema::hasTable($table);
        }));

        if (!empty($missing)) {
            throw new RuntimeException(
                '必要なテーブルがありません。Migrationを確認してください: ' . implode(', ', $missing)
            );
        }
    }

    private function lockQuiz(int $quiz_id): void
    {
        $quiz = DB::table('quizzes')->where('id', $quiz_id)->lockForUpdate()->first();
        if (!$quiz) {
            throw new RuntimeException("処理中に小テストID {$quiz_id} が見つからなくなりました。");
        }
    }

    private function writeSnapshot(string $path, array $snapshot): void
    {
        $directory = dirname($path);
        if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
            throw new RuntimeException('保存先ディレクトリを作成できません: ' . $directory);
        }

        $json = json_encode($snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw new RuntimeException('初期状態をJSONへ変換できませんでした。');
        }

        $temporary = $path . '.tmp-' . bin2hex(random_bytes(6));
        if (file_put_contents($temporary, $json, LOCK_EX) === false) {
            throw new RuntimeException('一時ファイルへ保存できません: ' . $temporary);
        }
        @chmod($temporary, 0640);

        if (!rename($temporary, $path)) {
            @unlink($temporary);
            throw new RuntimeException('初期状態ファイルを確定できません: ' . $path);
        }
    }

    private function readSnapshot(string $path, int $quiz_id): array
    {
        $json = file_get_contents($path);
        $snapshot = $json === false ? null : json_decode($json, true);

        if (!is_array($snapshot)) {
            throw new RuntimeException('初期状態ファイルを読み込めません: ' . $path);
        }
        if (($snapshot['format_version'] ?? null) !== 1) {
            throw new RuntimeException('未対応の初期状態ファイルです。');
        }
        if ((int) ($snapshot['quiz_id'] ?? 0) !== $quiz_id) {
            throw new RuntimeException('初期状態ファイルの小テストIDが一致しません。');
        }

        $checksum = $snapshot['checksum'] ?? null;
        unset($snapshot['checksum']);
        if (!is_string($checksum) || !hash_equals($checksum, $this->checksum($snapshot))) {
            throw new RuntimeException('初期状態ファイルの整合性を確認できません。');
        }
        $snapshot['checksum'] = $checksum;

        return $snapshot;
    }

    private function checksum(array $snapshot): string
    {
        unset($snapshot['checksum']);
        $json = json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw new RuntimeException('整合性確認用データを作成できませんでした。');
        }

        return hash('sha256', $json);
    }

    private function assertSameTableCounts(array $expected, array $actual): void
    {
        foreach (self::TABLE_ORDER as $table) {
            $expected_count = count($expected[$table] ?? []);
            $actual_count = count($actual[$table] ?? []);
            if ($expected_count !== $actual_count) {
                throw new RuntimeException(
                    "復元件数が一致しません: {$table}（予定 {$expected_count}件、実際 {$actual_count}件）"
                );
            }
        }
    }

    private function displayCounts(array $tables): void
    {
        $this->table(
            ['テーブル', '件数'],
            array_map(function ($table) use ($tables) {
                return [$table, count($tables[$table] ?? [])];
            }, self::TABLE_ORDER)
        );
    }

    private function baselinePath(int $quiz_id): string
    {
        return storage_path("app/quizzes-demo/baselines/quiz-{$quiz_id}.json");
    }

    private function safetyBackupPath(int $quiz_id): string
    {
        $timestamp = now()->format('Ymd-His');
        $suffix = bin2hex(random_bytes(3));

        return storage_path("app/quizzes-demo/safety/quiz-{$quiz_id}-before-reset-{$timestamp}-{$suffix}.json");
    }
}
