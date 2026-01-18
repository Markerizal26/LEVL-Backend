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
        Schema::table('courses', function (Blueprint $table) {
            // Index for filtering by category
            $table->index('category_id', 'idx_courses_category');
            
            // Index for filtering by instructor
            $table->index('instructor_id', 'idx_courses_instructor');
            
            // Composite index for category + status (common filter)
            $table->index(['category_id', 'status'], 'idx_courses_category_status');
            
            // Ensure unique slug index exists (if not already)
            // $table->unique('slug'); // Already exists in most schemes
        });

        Schema::table('units', function (Blueprint $table) {
            // Index for unit ordering within a course
            $table->index(['course_id', 'order'], 'idx_units_course_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropIndex('idx_courses_category');
            $table->dropIndex('idx_courses_instructor');
            $table->dropIndex('idx_courses_category_status');
        });

        Schema::table('units', function (Blueprint $table) {
            $table->dropIndex('idx_units_course_order');
        });
    }
};
