<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assignments', function (Blueprint $table) {
            // Add polymorphic relationship fields (nullable to support existing lesson_id)
            $table->string('assignable_type')->nullable()->after('id');
            $table->unsignedBigInteger('assignable_id')->nullable()->after('assignable_type');

            // Deadline tolerance in minutes (grace period after deadline)
            $table->integer('tolerance_minutes')->default(0)->after('deadline_at');

            // Attempt management
            $table->integer('max_attempts')->nullable()->after('tolerance_minutes');
            $table->integer('cooldown_minutes')->default(0)->after('max_attempts');

            // Re-take mode
            $table->boolean('retake_enabled')->default(false)->after('cooldown_minutes');

            // Review mode: immediate, deferred, hidden
            $table->string('review_mode', 20)->default('immediate')->after('retake_enabled');

            // Question randomization
            $table->string('randomization_type', 20)->default('static')->after('review_mode');
            $table->integer('question_bank_count')->nullable()->after('randomization_type');

            // Add index for polymorphic relationship
            $table->index(['assignable_type', 'assignable_id'], 'idx_assignments_assignable');
        });

        // Migrate existing lesson_id data to polymorphic fields
        DB::statement("
            UPDATE assignments 
            SET assignable_type = 'Modules\\\\Schemes\\\\Models\\\\Lesson',
                assignable_id = lesson_id
            WHERE lesson_id IS NOT NULL
        ");
    }

    public function down(): void
    {
        Schema::table('assignments', function (Blueprint $table) {
            $table->dropIndex('idx_assignments_assignable');
            $table->dropColumn([
                'assignable_type',
                'assignable_id',
                'tolerance_minutes',
                'max_attempts',
                'cooldown_minutes',
                'retake_enabled',
                'review_mode',
                'randomization_type',
                'question_bank_count',
            ]);
        });
    }
};
