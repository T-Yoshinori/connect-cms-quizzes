<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->string('question_order', 30)
                ->default('registered')
                ->after('result_display_timing');
            $table->string('question_display', 30)
                ->default('page')
                ->after('question_order');
            $table->string('question_number_format', 30)
                ->default('numeric')
                ->after('question_display');
        });
    }

    public function down(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->dropColumn([
                'question_order',
                'question_display',
                'question_number_format',
            ]);
        });
    }
};
