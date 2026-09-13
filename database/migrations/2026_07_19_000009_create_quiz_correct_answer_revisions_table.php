<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('yuyu_quiz_correct_answer_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_revision_id')->constrained('yuyu_quiz_question_revisions')->cascadeOnDelete();
            $table->unsignedSmallInteger('answer_group')->default(1);
            $table->text('answer_text');
            $table->unsignedInteger('sequence')->default(0);
            $table->timestamps();
            $table->index(['question_revision_id','answer_group','sequence'], 'quiz_correct_answer_group_index');
        });
    }
    public function down(): void { Schema::dropIfExists('yuyu_quiz_correct_answer_revisions'); }
};
