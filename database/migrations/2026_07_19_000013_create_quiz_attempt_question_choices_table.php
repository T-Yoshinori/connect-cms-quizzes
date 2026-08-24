<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('quiz_attempt_question_choices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_attempt_question_id')->constrained('quiz_attempt_questions')->cascadeOnDelete();
            $table->foreignId('choice_revision_id')->constrained('quiz_choice_revisions')->restrictOnDelete();
            $table->unsignedInteger('display_sequence')->default(0);
            $table->timestamps();
            $table->unique(['quiz_attempt_question_id','choice_revision_id'], 'quiz_attempt_choice_unique');
            $table->unique(['quiz_attempt_question_id','display_sequence'], 'quiz_attempt_choice_sequence_unique');
        });
    }
    public function down(): void { Schema::dropIfExists('quiz_attempt_question_choices'); }
};
