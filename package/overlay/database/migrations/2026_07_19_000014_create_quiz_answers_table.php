<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('quiz_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_attempt_id')->constrained('quiz_attempts')->cascadeOnDelete();
            $table->foreignId('quiz_attempt_question_id')->constrained('quiz_attempt_questions')->cascadeOnDelete();
            $table->json('answer_data')->nullable();
            $table->decimal('current_score', 8, 2)->nullable();
            $table->string('correctness', 30)->default('unanswered');
            $table->string('grading_status', 30)->default('ungraded');
            $table->dateTime('answered_at')->nullable();
            $table->timestamps();
            $table->unique(['quiz_attempt_id','quiz_attempt_question_id'], 'quiz_answer_attempt_question_unique');
            $table->index(['quiz_attempt_id','grading_status']);
        });
    }
    public function down(): void { Schema::dropIfExists('quiz_answers'); }
};
