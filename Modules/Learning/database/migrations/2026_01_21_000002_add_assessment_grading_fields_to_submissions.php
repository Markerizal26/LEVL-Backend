<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('submissions', function (Blueprint $table) {
            // Drop the unique constraint to allow multiple submissions per user/assignment
            $table->dropUnique(['assignment_id', 'user_id']);

            // Add score field back (was removed in earlier migration, needed for state machine)
            $table->decimal('score', 8, 2)->nullable()->after('status');

            // Store the question set used for this submission (for randomization audit)
            $table->json('question_set')->nullable()->after('score');

            // Add composite index for student-assignment queries
            $table->index(['assignment_id', 'user_id', 'attempt_number'], 'idx_submissions_assignment_user_attempt');

            // Add index for state-based queries
            $table->index(['status', 'submitted_at'], 'idx_submissions_status_submitted');
        });
    }

    public function down(): void
    {
        Schema::table('submissions', function (Blueprint $table) {
            $table->dropIndex('idx_submissions_assignment_user_attempt');
            $table->dropIndex('idx_submissions_status_submitted');
            $table->dropColumn(['score', 'question_set']);

            // Restore unique constraint
            $table->unique(['assignment_id', 'user_id']);
        });
    }
};
