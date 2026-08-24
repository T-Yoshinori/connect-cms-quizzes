<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('quiz_question_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_question_id')->constrained('quiz_questions')->cascadeOnDelete();
            $table->unsignedInteger('revision_no');
            $table->string('question_type', 30);
            $table->longText('question_text');
            $table->decimal('points', 8, 2)->default(10);
            $table->longText('commentary')->nullable();
            $table->longText('model_answer')->nullable()->comment('管理者作成の模範解答');
            $table->longText('grading_guide')->nullable()->comment('管理者専用の採点基準');
            $table->boolean('choice_random')->default(false);
            $table->boolean('answer_order_fixed')->default(true);
            $table->json('normalization_options')->nullable();
            $table->unsignedSmallInteger('answer_rows')->nullable();
            $table->unsignedInteger('character_limit')->nullable();
            $table->unsignedBigInteger('created_id')->nullable();
            $table->timestamps();
            $table->unique(['quiz_question_id','revision_no'], 'quiz_question_revision_unique');
            $table->index('question_type');
            $table->foreign('created_id')->references('id')->on('users')->nullOnDelete();
        });
    }
    public function down(): void { Schema::dropIfExists('quiz_question_revisions'); }
};
