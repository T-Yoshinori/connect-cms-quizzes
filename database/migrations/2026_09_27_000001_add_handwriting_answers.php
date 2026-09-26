<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('yuyu_quiz_question_revisions', function (Blueprint $table) {
            $table->string('essay_input_mode', 20)->default('text');
        });
        Schema::create('yuyu_quiz_handwriting_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_answer_id')->constrained('yuyu_quiz_answers')->cascadeOnDelete();
            $table->string('path');
            $table->unsignedInteger('bytes');
            $table->unsignedInteger('width');
            $table->unsignedInteger('height');
            $table->timestamps();
            $table->index('quiz_answer_id');
        });
    }

    public function down(): void {
        Schema::dropIfExists('yuyu_quiz_handwriting_images');
        Schema::table('yuyu_quiz_question_revisions', function (Blueprint $table) {
            $table->dropColumn('essay_input_mode');
        });
    }
};
