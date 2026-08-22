<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('quizzes', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->longText('description')->nullable();
            $table->string('status', 30)->default('draft');
            $table->dateTime('publish_start_at')->nullable();
            $table->dateTime('publish_end_at')->nullable();
            $table->unsignedSmallInteger('estimated_minutes')->nullable();
            $table->unsignedSmallInteger('time_limit_minutes')->nullable();
            $table->string('retry_type', 30)->default('unlimited');
            $table->unsignedSmallInteger('retry_limit')->nullable();
            $table->string('passing_type', 30)->default('none');
            $table->decimal('passing_score', 8, 2)->nullable();
            $table->decimal('passing_rate', 5, 2)->nullable();
            $table->decimal('perfect_score', 8, 2)->default(0);
            $table->boolean('show_score')->default(true);
            $table->boolean('show_pass_status')->default(true);
            $table->boolean('show_question_result')->default(false);
            $table->boolean('show_correct_answer')->default(false);
            $table->boolean('show_commentary')->default(false);
            $table->boolean('show_grading_comment')->default(true);
            $table->string('result_display_timing', 30)->default('after_grading');
            $table->unsignedBigInteger('created_id')->nullable();
            $table->unsignedBigInteger('updated_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index('status');
            $table->index(['publish_start_at', 'publish_end_at']);
            $table->foreign('created_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_id')->references('id')->on('users')->nullOnDelete();
        });
    }
    public function down(): void { Schema::dropIfExists('quizzes'); }
};
