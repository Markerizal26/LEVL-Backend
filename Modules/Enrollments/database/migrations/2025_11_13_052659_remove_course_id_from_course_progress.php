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
        if (Schema::hasTable('course_progress') && Schema::hasColumn('course_progress', 'course_id')) {
            Schema::table('course_progress', function (Blueprint $table) {
                $table->dropForeign(['course_id']);
                $table->dropColumn('course_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('course_progress') && !Schema::hasColumn('course_progress', 'course_id')) {
            Schema::table('course_progress', function (Blueprint $table) {
                $table->foreignId('course_id')->after('enrollment_id')
                    ->constrained('courses')->onDelete('cascade');
            });
        }
    }
};
