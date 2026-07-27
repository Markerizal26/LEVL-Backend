<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('grades', function (Blueprint $table) {
            $table->text('ai_scoring_reference')->nullable()->after('ai_reasoning');
        });

        Schema::table('quiz_answers', function (Blueprint $table) {
            $table->text('ai_scoring_reference')->nullable()->after('ai_reasoning');
        });
    }

    public function down(): void
    {
        Schema::table('grades', function (Blueprint $table) {
            $table->dropColumn('ai_scoring_reference');
        });

        Schema::table('quiz_answers', function (Blueprint $table) {
            $table->dropColumn('ai_scoring_reference');
        });
    }
};
