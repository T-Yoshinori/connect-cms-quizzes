<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_attempt_category_groups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('quiz_attempt_id');
            $table->unsignedBigInteger('source_category_group_id')->nullable();
            $table->string('name');
            $table->unsignedInteger('display_sequence');
            $table->timestamps();
            $table->foreign('quiz_attempt_id')->references('id')->on('quiz_attempts')->onDelete('cascade');
            $table->index(['quiz_attempt_id', 'display_sequence'], 'quiz_attempt_category_groups_display_index');
        });

        Schema::create('quiz_attempt_categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('quiz_attempt_category_group_id');
            $table->unsignedBigInteger('source_category_id')->nullable();
            $table->string('name');
            $table->unsignedInteger('display_sequence');
            $table->timestamps();
            $table->foreign('quiz_attempt_category_group_id', 'quiz_attempt_categories_group_fk')
                ->references('id')->on('quiz_attempt_category_groups')->onDelete('cascade');
            $table->index(['quiz_attempt_category_group_id', 'display_sequence'], 'quiz_attempt_categories_display_index');
        });

        Schema::create('quiz_attempt_question_categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('quiz_attempt_question_id');
            $table->unsignedBigInteger('quiz_attempt_category_id');
            $table->timestamps();
            $table->foreign('quiz_attempt_question_id', 'quiz_attempt_question_categories_question_fk')
                ->references('id')->on('quiz_attempt_questions')->onDelete('cascade');
            $table->foreign('quiz_attempt_category_id', 'quiz_attempt_question_categories_category_fk')
                ->references('id')->on('quiz_attempt_categories')->onDelete('cascade');
            $table->unique(['quiz_attempt_question_id', 'quiz_attempt_category_id'], 'quiz_attempt_question_categories_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_attempt_question_categories');
        Schema::dropIfExists('quiz_attempt_categories');
        Schema::dropIfExists('quiz_attempt_category_groups');
    }
};
