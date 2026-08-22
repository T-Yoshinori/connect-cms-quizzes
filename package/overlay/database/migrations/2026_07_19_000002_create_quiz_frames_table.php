<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('quiz_frames', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('frame_id');
            $table->foreignId('quiz_id')->constrained('quizzes')->cascadeOnDelete();
            $table->timestamps();
            $table->unique('frame_id');
            $table->foreign('frame_id')->references('id')->on('frames')->cascadeOnDelete();
        });
    }
    public function down(): void { Schema::dropIfExists('quiz_frames'); }
};
