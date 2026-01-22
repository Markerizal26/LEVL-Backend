<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration to add performance indexes for the Assessment & Grading System.
 *
 * This migration adds database indexes on frequently queried fields to improve
 * query performance as specified in Requirement 28.4.
 *
 * Requirement 28.4: THE System SHALL use database indexing on frequently queried fields
 * (student_id, assignment_id, state, submission_time)
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        
        Schema::table('submissions', function (Blueprint $table) {
            
            
            if (! $this->indexExists('submissions', 'idx_submissions_student_assignment')) {
                $table->index(['user_id', 'assignment_id'], 'idx_submissions_student_assignment');
            }

            
            if (! $this->indexExists('submissions', 'idx_submissions_submitted_at')) {
                $table->index('submitted_at', 'idx_submissions_submitted_at');
            }

            
            if (! $this->indexExists('submissions', 'idx_submissions_score')) {
                $table->index('score', 'idx_submissions_score');
            }
        });

        
        Schema::table('grades', function (Blueprint $table) {
            
            if (! $this->indexExists('grades', 'idx_grades_grader')) {
                $table->index('graded_by', 'idx_grades_grader');
            }
        });

        
        Schema::table('assignments', function (Blueprint $table) {
            
            if (! $this->indexExists('assignments', 'idx_assignments_deadline')) {
                $table->index('deadline_at', 'idx_assignments_deadline');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('submissions', function (Blueprint $table) {
            if ($this->indexExists('submissions', 'idx_submissions_student_assignment')) {
                $table->dropIndex('idx_submissions_student_assignment');
            }
            if ($this->indexExists('submissions', 'idx_submissions_submitted_at')) {
                $table->dropIndex('idx_submissions_submitted_at');
            }
            if ($this->indexExists('submissions', 'idx_submissions_score')) {
                $table->dropIndex('idx_submissions_score');
            }
        });

        Schema::table('grades', function (Blueprint $table) {
            if ($this->indexExists('grades', 'idx_grades_grader')) {
                $table->dropIndex('idx_grades_grader');
            }
        });

        Schema::table('assignments', function (Blueprint $table) {
            if ($this->indexExists('assignments', 'idx_assignments_deadline')) {
                $table->dropIndex('idx_assignments_deadline');
            }
        });
    }

    /**
     * Check if an index exists on a table.
     */
    private function indexExists(string $table, string $indexName): bool
    {
        $connection = Schema::getConnection();
        $databaseName = $connection->getDatabaseName();
        $driver = $connection->getDriverName();

        if ($driver === 'mysql') {
            $indexes = $connection->select(
                "SHOW INDEX FROM `{$table}` WHERE Key_name = ?",
                [$indexName]
            );

            return count($indexes) > 0;
        }

        if ($driver === 'pgsql') {
            $indexes = $connection->select(
                'SELECT indexname FROM pg_indexes WHERE tablename = ? AND indexname = ?',
                [$table, $indexName]
            );

            return count($indexes) > 0;
        }

        if ($driver === 'sqlite') {
            $indexes = $connection->select(
                "SELECT name FROM sqlite_master WHERE type = 'index' AND tbl_name = ? AND name = ?",
                [$table, $indexName]
            );

            return count($indexes) > 0;
        }

        
        return false;
    }
};
