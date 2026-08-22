<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('quiz_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_page_id')->constrained('quiz_pages')->cascadeOnDelete();
            $table->unsignedBigInteger('current_revision_id')->nullable();
            $table->unsignedInteger('sequence')->default(0);
            $table->string('status', 30)->default('active');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['quiz_page_id','sequence']);
            $table->index('current_revision_id');
        });
    }
    public function down(): void { Schema::dropIfExists('quiz_questions'); }
};
