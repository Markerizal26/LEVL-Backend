<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('grades', function (Blueprint $table) {
            $table->boolean('is_ai_assisted')->default(false)->after('is_draft');
            $table->decimal('ai_suggested_score', 8, 2)->nullable()->after('is_ai_assisted');
        });

        Schema::table('quiz_answers', function (Blueprint $table) {
            $table->boolean('is_ai_assisted')->default(false)->after('is_auto_graded');
            $table->decimal('ai_suggested_score', 8, 2)->nullable()->after('is_ai_assisted');
        });
    }

    public function down(): void
    {
        Schema::table('grades', function (Blueprint $table) {
            $table->dropColumn(['is_ai_assisted', 'ai_suggested_score']);
        });

        Schema::table('quiz_answers', function (Blueprint $table) {
            $table->dropColumn(['is_ai_assisted', 'ai_suggested_score']);
        });
    }
};
