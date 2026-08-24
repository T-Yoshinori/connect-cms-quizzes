<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('quiz_attempt_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_attempt_page_id')->constrained('quiz_attempt_pages')->cascadeOnDelete();
            $table->unsignedBigInteger('quiz_question_id')->nullable();
            $table->foreignId('question_revision_id')->constrained('quiz_question_revisions')->restrictOnDelete();
            $table->unsignedInteger('display_sequence')->default(0);
            $table->decimal('points', 8, 2)->default(0);
            $table->string('scoring_status', 30)->default('scored');
            $table->timestamps();
            $table->unique(['quiz_attempt_page_id','display_sequence'], 'quiz_attempt_question_sequence_unique');
            $table->index('question_revision_id');
            $table->index('scoring_status');
            $table->foreign('quiz_question_id')->references('id')->on('quiz_questions')->nullOnDelete();
        });
    }
    public function down(): void { Schema::dropIfExists('quiz_attempt_questions'); }
};
