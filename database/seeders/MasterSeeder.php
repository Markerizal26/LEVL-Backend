<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class MasterSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info("\n╔══════════════════════════════════════════════════╗");
        $this->command->info("║   MASTER SEEDER - Complete System Data Setup    ║");
        $this->command->info("╚══════════════════════════════════════════════════╝\n");

        $startTime = microtime(true);

        // Auth + Users
        $this->call(\Modules\Auth\Database\Seeders\AuthComprehensiveDataSeeder::class);

        // Categories & Tags
        $this->call(\Modules\Common\Database\Seeders\CategorySeederEnhanced::class);
        $this->call(\Modules\Schemes\Database\Seeders\TagSeederEnhanced::class);

        // Courses & Content
        $this->call(\Modules\Schemes\Database\Seeders\CourseSeederEnhanced::class);
        $this->call(\Modules\Schemes\Database\Seeders\LearningContentSeeder::class);

        // Assignments, Questions, Enrollments, Grading
        $this->call(\Modules\Learning\Database\Seeders\AssignmentSeederEnhanced::class);
        $this->call(\Modules\Learning\Database\Seeders\QuestionSeederEnhanced::class);
        $this->call(\Modules\Enrollments\Database\Seeders\EnrollmentSeeder::class);
        $this->call(\Modules\Grading\Database\Seeders\GradeAndAppealSeeder::class);
        $this->call(\Modules\Grading\Database\Seeders\GradeReviewSeeder::class);

        $duration = round(microtime(true) - $startTime, 2);
        $this->command->info("\n╔══════════════════════════════════════════════════╗");
        $this->command->info("║          ✅ MASTER SEEDING COMPLETED!            ║");
        $this->command->info("║   Total Time: {$duration} seconds                   ║");
        $this->command->info("╚══════════════════════════════════════════════════╝\n");
    }
}
