<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('grades', function (Blueprint $table) {
            $table->text('ai_reasoning')->nullable()->after('ai_suggested_score');
        });

        Schema::table('quiz_answers', function (Blueprint $table) {
            $table->text('ai_reasoning')->nullable()->after('ai_suggested_score');
        });
    }

    public function down(): void
    {
        Schema::table('grades', function (Blueprint $table) {
            $table->dropColumn('ai_reasoning');
        });

        Schema::table('quiz_answers', function (Blueprint $table) {
            $table->dropColumn('ai_reasoning');
        });
    }
};
