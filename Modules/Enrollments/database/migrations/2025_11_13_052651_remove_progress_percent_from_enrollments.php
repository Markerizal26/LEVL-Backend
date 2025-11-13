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
        if (Schema::hasTable('enrollments') && Schema::hasColumn('enrollments', 'progress_percent')) {
            Schema::table('enrollments', function (Blueprint $table) {
                $table->dropColumn('progress_percent');
            });

            Schema::table('enrollments', function (Blueprint $table) {
                $table->dropIndex(['status', 'progress_percent']);
            });

            Schema::table('enrollments', function (Blueprint $table) {
                $table->index('status');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('enrollments') && !Schema::hasColumn('enrollments', 'progress_percent')) {
            Schema::table('enrollments', function (Blueprint $table) {
                $table->float('progress_percent')->default(0)->after('completed_at');
            });

            Schema::table('enrollments', function (Blueprint $table) {
                $table->index(['status', 'progress_percent']);
            });
        }
    }
};
