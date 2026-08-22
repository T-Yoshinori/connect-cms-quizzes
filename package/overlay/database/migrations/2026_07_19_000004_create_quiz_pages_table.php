<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('quiz_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained('quizzes')->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->longText('description')->nullable();
            $table->unsignedInteger('sequence')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['quiz_id','sequence']);
        });
    }
    public function down(): void { Schema::dropIfExists('quiz_pages'); }
};
