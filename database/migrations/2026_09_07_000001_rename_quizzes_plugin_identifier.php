<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RenameQuizzesPluginIdentifier extends Migration
{
    /**
     * Table names owned exclusively by YuyuQuizzes.
     *
     * @var array<string, string>
     */
    private $tables = [
        'quizzes' => 'yuyu_quizzes',
        'quiz_frames' => 'yuyu_quiz_frames',
        'quiz_page_groups' => 'yuyu_quiz_page_groups',
        'quiz_pages' => 'yuyu_quiz_pages',
        'quiz_questions' => 'yuyu_quiz_questions',
        'quiz_question_revisions' => 'yuyu_quiz_question_revisions',
        'quiz_choice_revisions' => 'yuyu_quiz_choice_revisions',
        'quiz_correct_answer_revisions' => 'yuyu_quiz_correct_answer_revisions',
        'quiz_attempts' => 'yuyu_quiz_attempts',
        'quiz_attempt_pages' => 'yuyu_quiz_attempt_pages',
        'quiz_attempt_questions' => 'yuyu_quiz_attempt_questions',
        'quiz_attempt_question_choices' => 'yuyu_quiz_attempt_question_choices',
        'quiz_answers' => 'yuyu_quiz_answers',
        'quiz_answer_grades' => 'yuyu_quiz_answer_grades',
        'quiz_category_groups' => 'yuyu_quiz_category_groups',
        'quiz_categories' => 'yuyu_quiz_categories',
        'quiz_question_revision_categories' => 'yuyu_quiz_question_revision_categories',
        'quiz_attempt_category_groups' => 'yuyu_quiz_attempt_category_groups',
        'quiz_attempt_categories' => 'yuyu_quiz_attempt_categories',
        'quiz_attempt_question_categories' => 'yuyu_quiz_attempt_question_categories',
    ];

    public function up()
    {
        $this->assertNoTableNameConflicts($this->tables);
        $this->assertNoPluginNameConflict('quizzes', 'yuyuquizzes');

        $this->renameTables($this->tables);
        $this->renameInstalledPlugin('quizzes', 'Yuyuquizzes');

        DB::table('frames')->where('plugin_name', 'quizzes')->update(['plugin_name' => 'yuyuquizzes']);

        if (Schema::hasTable('yuyu_learning_contents')) {
            DB::table('yuyu_learning_contents')
                ->where('plugin_name', 'quizzes')
                ->update(['plugin_name' => 'yuyuquizzes']);
        }
    }

    public function down()
    {
        $reverse = array_reverse(array_flip($this->tables), true);

        $this->assertNoTableNameConflicts($reverse);
        $this->assertNoPluginNameConflict('yuyuquizzes', 'quizzes');

        DB::table('frames')->where('plugin_name', 'yuyuquizzes')->update(['plugin_name' => 'quizzes']);

        if (Schema::hasTable('yuyu_learning_contents')) {
            DB::table('yuyu_learning_contents')
                ->where('plugin_name', 'yuyuquizzes')
                ->update(['plugin_name' => 'quizzes']);
        }

        $this->renameInstalledPlugin('yuyuquizzes', 'Quizzes');
        $this->renameTables($reverse);
    }

    /**
     * Stop instead of choosing between two independently populated tables.
     *
     * @param array<string, string> $tables
     */
    private function assertNoTableNameConflicts(array $tables)
    {
        foreach ($tables as $from => $to) {
            if (Schema::hasTable($from) && Schema::hasTable($to)) {
                throw new \RuntimeException(
                    "YuyuQuizzes migration found both {$from} and {$to}; no table was selected automatically."
                );
            }
        }
    }

    private function assertNoPluginNameConflict($from, $to)
    {
        if (!Schema::hasTable('plugins')) {
            return;
        }

        $hasFrom = DB::table('plugins')->whereRaw('LOWER(plugin_name) = ?', [$from])->exists();
        $hasTo = DB::table('plugins')->whereRaw('LOWER(plugin_name) = ?', [$to])->exists();

        if ($hasFrom && $hasTo) {
            throw new \RuntimeException(
                "YuyuQuizzes migration found both {$from} and {$to} in plugins; no record was selected automatically."
            );
        }
    }

    private function renameInstalledPlugin($from, $to)
    {
        if (!Schema::hasTable('plugins')) {
            return;
        }

        DB::table('plugins')
            ->whereRaw('LOWER(plugin_name) = ?', [strtolower($from)])
            ->update(['plugin_name' => $to]);
    }

    /**
     * @param array<string, string> $tables
     */
    private function renameTables(array $tables)
    {
        Schema::disableForeignKeyConstraints();

        try {
            foreach ($tables as $from => $to) {
                if (Schema::hasTable($from) && !Schema::hasTable($to)) {
                    Schema::rename($from, $to);
                }
            }
        } finally {
            Schema::enableForeignKeyConstraints();
        }
    }
}
