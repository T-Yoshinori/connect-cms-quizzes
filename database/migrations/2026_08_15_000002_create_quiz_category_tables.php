<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_category_groups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('quiz_id');
            $table->string('name');
            $table->unsignedInteger('sequence')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('quiz_id')
                ->references('id')
                ->on('quizzes')
                ->onDelete('cascade');
            $table->index(['quiz_id', 'is_active', 'sequence'], 'quiz_category_groups_display_index');
        });

        Schema::create('quiz_categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('quiz_category_group_id');
            $table->string('name');
            $table->unsignedInteger('sequence')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('quiz_category_group_id')
                ->references('id')
                ->on('quiz_category_groups')
                ->onDelete('cascade');
            $table->index(
                ['quiz_category_group_id', 'is_active', 'sequence'],
                'quiz_categories_display_index'
            );
        });

        Schema::create('quiz_question_revision_categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('question_revision_id');
            $table->unsignedBigInteger('quiz_category_id');
            $table->timestamps();

            $table->foreign('question_revision_id', 'quiz_question_revision_categories_revision_fk')
                ->references('id')
                ->on('quiz_question_revisions')
                ->onDelete('cascade');
            $table->foreign('quiz_category_id', 'quiz_question_revision_categories_category_fk')
                ->references('id')
                ->on('quiz_categories')
                ->onDelete('cascade');
            $table->unique(
                ['question_revision_id', 'quiz_category_id'],
                'quiz_question_revision_categories_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_question_revision_categories');
        Schema::dropIfExists('quiz_categories');
        Schema::dropIfExists('quiz_category_groups');
    }
};
