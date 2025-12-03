<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Converts string columns to enum types for better data integrity.
     * Requirements: 1.1, 1.5
     */
    public function up(): void
    {
        // Validate existing data before migration
        $this->validateExistingData();

        // Convert category column to enum
        DB::statement("ALTER TABLE notification_preferences 
            MODIFY COLUMN category ENUM(
                'system', 'assignment', 'assessment', 'grading', 'gamification', 
                'news', 'custom', 'course_completed', 'enrollment', 
                'forum_reply_to_thread', 'forum_reply_to_reply'
            ) NOT NULL");

        // Convert channel column to enum
        DB::statement("ALTER TABLE notification_preferences 
            MODIFY COLUMN channel ENUM('in_app', 'email', 'push') NOT NULL");

        // Convert frequency column to enum
        DB::statement("ALTER TABLE notification_preferences 
            MODIFY COLUMN frequency ENUM('immediate', 'daily', 'weekly', 'never') 
            NOT NULL DEFAULT 'immediate'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert category column to string
        DB::statement('ALTER TABLE notification_preferences 
            MODIFY COLUMN category VARCHAR(50) NOT NULL');

        // Revert channel column to string
        DB::statement('ALTER TABLE notification_preferences 
            MODIFY COLUMN channel VARCHAR(50) NOT NULL');

        // Revert frequency column to string
        DB::statement("ALTER TABLE notification_preferences 
            MODIFY COLUMN frequency VARCHAR(50) NOT NULL DEFAULT 'immediate'");
    }

    /**
     * Validate existing data matches expected enum values.
     *
     * @throws \RuntimeException if invalid data is found
     */
    private function validateExistingData(): void
    {
        if (! Schema::hasTable('notification_preferences')) {
            return;
        }

        $validCategories = [
            'system', 'assignment', 'assessment', 'grading', 'gamification',
            'news', 'custom', 'course_completed', 'enrollment',
            'forum_reply_to_thread', 'forum_reply_to_reply',
        ];

        // Check for invalid category values
        $invalidCategory = DB::table('notification_preferences')
            ->whereNotIn('category', $validCategories)
            ->count();

        if ($invalidCategory > 0) {
            throw new \RuntimeException(
                "Found {$invalidCategory} records with invalid category values. Please fix before migration."
            );
        }

        // Check for invalid channel values
        $invalidChannel = DB::table('notification_preferences')
            ->whereNotIn('channel', ['in_app', 'email', 'push'])
            ->count();

        if ($invalidChannel > 0) {
            throw new \RuntimeException(
                "Found {$invalidChannel} records with invalid channel values. Please fix before migration."
            );
        }

        // Check for invalid frequency values
        $invalidFrequency = DB::table('notification_preferences')
            ->whereNotIn('frequency', ['immediate', 'daily', 'weekly', 'never'])
            ->count();

        if ($invalidFrequency > 0) {
            throw new \RuntimeException(
                "Found {$invalidFrequency} records with invalid frequency values. Please fix before migration."
            );
        }
    }
};
