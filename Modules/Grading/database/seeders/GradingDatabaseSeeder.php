<?php

namespace Modules\Grading\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Learning\Database\Seeders\PendingManualGradingSeeder;
use Modules\Grading\Database\Seeders\GradeReviewSeeder;

class GradingDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Seeds the Grading module with:
     * 1. Grades for submitted assignments
     * 2. Appeals with different statuses
     * 3. Grade reviews with different statuses
     * 4. Submissions with PendingManualGrading state
     */
    public function run(): void
    {
        $this->call([
            GradeAndAppealSeeder::class,
            GradeReviewSeeder::class,
            PendingManualGradingSeeder::class,
        ]);
    }
}
