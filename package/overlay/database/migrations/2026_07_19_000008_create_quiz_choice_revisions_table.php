<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('quiz_choice_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_revision_id')->constrained('quiz_question_revisions')->cascadeOnDelete();
            $table->longText('label');
            $table->unsignedInteger('sequence')->default(0);
            $table->boolean('is_correct')->default(false);
            $table->timestamps();
            $table->index(['question_revision_id','sequence'], 'quiz_choice_revision_sequence_index');
        });
    }
    public function down(): void { Schema::dropIfExists('quiz_choice_revisions'); }
};
