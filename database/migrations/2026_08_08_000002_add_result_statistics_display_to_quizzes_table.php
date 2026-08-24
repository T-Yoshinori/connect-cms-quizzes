<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->boolean('show_average_score')
                ->default(false)
                ->after('show_user_answer');
            $table->boolean('show_highest_score')
                ->default(false)
                ->after('show_average_score');
            $table->boolean('show_lowest_score')
                ->default(false)
                ->after('show_highest_score');
            $table->boolean('show_participant_count')
                ->default(false)
                ->after('show_lowest_score');
        });
    }

    public function down(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->dropColumn([
                'show_average_score',
                'show_highest_score',
                'show_lowest_score',
                'show_participant_count',
            ]);
        });
    }
};
