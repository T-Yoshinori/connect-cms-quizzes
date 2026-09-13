<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('yuyu_quiz_questions', function (Blueprint $table) {
            $table->foreign('current_revision_id')->references('id')->on('yuyu_quiz_question_revisions')->nullOnDelete();
        });
    }
    public function down(): void {
        Schema::table('yuyu_quiz_questions', function (Blueprint $table) {
            $table->dropForeign(['current_revision_id']);
        });
    }
};
