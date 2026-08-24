<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('quiz_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained('quizzes')->restrictOnDelete();
            $table->unsignedInteger('page_id')->nullable();
            $table->unsignedInteger('frame_id')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->unsignedSmallInteger('attempt_no')->default(1);
            $table->string('status', 30)->default('in_progress');
            $table->string('grading_status', 30)->default('not_started');
            $table->string('pass_status', 30)->default('pending');
            $table->dateTime('started_at');
            $table->dateTime('reviewed_at')->nullable();
            $table->dateTime('submitted_at')->nullable();
            $table->dateTime('graded_at')->nullable();
            $table->dateTime('expires_at')->nullable();
            $table->unsignedInteger('elapsed_seconds')->nullable();
            $table->decimal('total_score', 8, 2)->default(0);
            $table->decimal('effective_max_score', 8, 2)->default(0);
            $table->decimal('score_rate', 5, 2)->nullable();
            $table->string('passing_type_snapshot', 30)->default('none');
            $table->decimal('passing_score_snapshot', 8, 2)->nullable();
            $table->decimal('passing_rate_snapshot', 5, 2)->nullable();
            $table->unsignedSmallInteger('time_limit_minutes_snapshot')->nullable();
            $table->boolean('is_preview')->default(false);
            $table->timestamps();
            $table->unique(['quiz_id','user_id','attempt_no','is_preview'], 'quiz_attempt_user_number_unique');
            $table->index(['quiz_id','status']);
            $table->index(['user_id','status']);
            $table->index(['quiz_id','grading_status']);
            $table->index(['quiz_id','pass_status']);
            $table->foreign('page_id')->references('id')->on('pages')->nullOnDelete();
            $table->foreign('frame_id')->references('id')->on('frames')->nullOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->restrictOnDelete();
        });
    }
    public function down(): void { Schema::dropIfExists('quiz_attempts'); }
};
