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

        // Convert status column to enum
        DB::statement("ALTER TABLE assessment_registrations 
            MODIFY COLUMN status ENUM('pending', 'confirmed', 'completed', 'cancelled', 'no_show') 
            NOT NULL DEFAULT 'pending'");

        // Convert payment_status column to enum
        DB::statement("ALTER TABLE assessment_registrations 
            MODIFY COLUMN payment_status ENUM('pending', 'paid', 'failed', 'refunded') 
            NOT NULL DEFAULT 'pending'");

        // Convert payment_method column to enum (nullable)
        DB::statement("ALTER TABLE assessment_registrations 
            MODIFY COLUMN payment_method ENUM('bank_transfer', 'credit_card', 'e_wallet', 'cash') 
            NULL DEFAULT NULL");

        // Convert result column to enum (nullable)
        DB::statement("ALTER TABLE assessment_registrations 
            MODIFY COLUMN result ENUM('passed', 'failed', 'pending') 
            NULL DEFAULT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert status column to string
        DB::statement("ALTER TABLE assessment_registrations 
            MODIFY COLUMN status VARCHAR(50) NOT NULL DEFAULT 'pending'");

        // Revert payment_status column to string
        DB::statement("ALTER TABLE assessment_registrations 
            MODIFY COLUMN payment_status VARCHAR(50) NOT NULL DEFAULT 'pending'");

        // Revert payment_method column to string
        DB::statement('ALTER TABLE assessment_registrations 
            MODIFY COLUMN payment_method VARCHAR(50) NULL DEFAULT NULL');

        // Revert result column to string
        DB::statement('ALTER TABLE assessment_registrations 
            MODIFY COLUMN result VARCHAR(50) NULL DEFAULT NULL');
    }

    /**
     * Validate existing data matches expected enum values.
     *
     * @throws \RuntimeException if invalid data is found
     */
    private function validateExistingData(): void
    {
        if (! Schema::hasTable('assessment_registrations')) {
            return;
        }

        // Check for invalid status values
        $invalidStatus = DB::table('assessment_registrations')
            ->whereNotIn('status', ['pending', 'confirmed', 'completed', 'cancelled', 'no_show'])
            ->whereNotNull('status')
            ->count();

        if ($invalidStatus > 0) {
            throw new \RuntimeException(
                "Found {$invalidStatus} records with invalid status values. Please fix before migration."
            );
        }

        // Check for invalid payment_status values
        $invalidPaymentStatus = DB::table('assessment_registrations')
            ->whereNotIn('payment_status', ['pending', 'paid', 'failed', 'refunded'])
            ->whereNotNull('payment_status')
            ->count();

        if ($invalidPaymentStatus > 0) {
            throw new \RuntimeException(
                "Found {$invalidPaymentStatus} records with invalid payment_status values. Please fix before migration."
            );
        }

        // Check for invalid payment_method values (nullable, so only check non-null)
        $invalidPaymentMethod = DB::table('assessment_registrations')
            ->whereNotIn('payment_method', ['bank_transfer', 'credit_card', 'e_wallet', 'cash'])
            ->whereNotNull('payment_method')
            ->count();

        if ($invalidPaymentMethod > 0) {
            throw new \RuntimeException(
                "Found {$invalidPaymentMethod} records with invalid payment_method values. Please fix before migration."
            );
        }

        // Check for invalid result values (nullable, so only check non-null)
        $invalidResult = DB::table('assessment_registrations')
            ->whereNotIn('result', ['passed', 'failed', 'pending'])
            ->whereNotNull('result')
            ->count();

        if ($invalidResult > 0) {
            throw new \RuntimeException(
                "Found {$invalidResult} records with invalid result values. Please fix before migration."
            );
        }
    }
};
