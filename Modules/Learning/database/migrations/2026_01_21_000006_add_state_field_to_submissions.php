<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('submissions', function (Blueprint $table) {
            // Add state field for new state machine
            $table->string('state', 30)->nullable()->after('status');

            // Index for state-based queries
            $table->index('state', 'idx_submissions_state');
        });

        // Migrate existing status values to new state field
        DB::statement("
            UPDATE submissions 
            SET state = CASE 
                WHEN status = 'draft' THEN 'in_progress'
                WHEN status = 'submitted' THEN 'submitted'
                WHEN status = 'late' THEN 'submitted'
                WHEN status = 'graded' THEN 'graded'
                ELSE 'in_progress'
            END
        ");
    }

    public function down(): void
    {
        Schema::table('submissions', function (Blueprint $table) {
            $table->dropIndex('idx_submissions_state');
            $table->dropColumn('state');
        });
    }
};
