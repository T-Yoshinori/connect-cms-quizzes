<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('quiz_page_groups', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('page_id');
            $table->unsignedInteger('frame_id');
            $table->foreignId('quiz_id')->constrained('quizzes')->cascadeOnDelete();
            $table->unsignedInteger('group_id');
            $table->timestamps();
            $table->unique(['page_id','frame_id','quiz_id','group_id'], 'quiz_page_groups_unique');
            $table->index(['quiz_id','group_id']);
            $table->foreign('page_id')->references('id')->on('pages')->cascadeOnDelete();
            $table->foreign('frame_id')->references('id')->on('frames')->cascadeOnDelete();
            $table->foreign('group_id')->references('id')->on('groups')->cascadeOnDelete();
        });
    }
    public function down(): void { Schema::dropIfExists('quiz_page_groups'); }
};
