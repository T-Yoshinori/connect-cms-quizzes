<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('quiz_answer_grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_answer_id')->constrained('quiz_answers')->cascadeOnDelete();
            $table->decimal('score', 8, 2)->default(0);
            $table->string('correctness', 30)->default('not_applicable');
            $table->string('grading_type', 30);
            $table->text('reason')->nullable();
            $table->longText('comment')->nullable();
            $table->longText('internal_comment')->nullable();
            $table->unsignedBigInteger('graded_by')->nullable();
            $table->dateTime('graded_at');
            $table->boolean('is_current')->default(true);
            $table->timestamps();
            $table->index(['quiz_answer_id','is_current'], 'quiz_answer_current_grade_index');
            $table->index('grading_type');
            $table->index('graded_at');
            $table->foreign('graded_by')->references('id')->on('users')->nullOnDelete();
        });
    }
    public function down(): void { Schema::dropIfExists('quiz_answer_grades'); }
};
