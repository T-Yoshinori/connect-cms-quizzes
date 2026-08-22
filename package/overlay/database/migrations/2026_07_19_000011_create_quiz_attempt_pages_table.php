<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('quiz_attempt_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_attempt_id')->constrained('quiz_attempts')->cascadeOnDelete();
            $table->unsignedBigInteger('quiz_page_id')->nullable();
            $table->string('title')->nullable();
            $table->longText('description')->nullable();
            $table->unsignedInteger('display_sequence')->default(0);
            $table->timestamps();
            $table->unique(['quiz_attempt_id','display_sequence'], 'quiz_attempt_page_sequence_unique');
            $table->foreign('quiz_page_id')->references('id')->on('quiz_pages')->nullOnDelete();
        });
    }
    public function down(): void { Schema::dropIfExists('quiz_attempt_pages'); }
};
