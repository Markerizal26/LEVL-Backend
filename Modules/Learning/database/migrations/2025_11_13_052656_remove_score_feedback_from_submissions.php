<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('submissions') && Schema::hasColumn('submissions', 'score')) {
            Schema::table('submissions', function (Blueprint $table) {
                $table->dropColumn(['score', 'feedback', 'graded_at']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('submissions') && !Schema::hasColumn('submissions', 'score')) {
            Schema::table('submissions', function (Blueprint $table) {
                $table->integer('score')->nullable()->after('status');
                $table->text('feedback')->nullable()->after('score');
                $table->timestamp('graded_at')->nullable()->after('feedback');
            });
        }
    }
};
