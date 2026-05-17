<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\Enrollments\Models\Enrollment;
use Modules\Schemes\Models\Course;

class ResetLoadTestEnrollments extends Command
{
    protected $signature = 'levl:reset-loadtest-enrollments
        {--course= : Course slug to reset}
        {--force : Reset without confirmation}';

    protected $description = 'Delete enrollments for a course so load-test tokens can be reused';

    public function handle(): int
    {
        $courseSlug = (string) $this->option('course');

        if ($courseSlug === '') {
            $this->error('Course slug is required.');

            return self::FAILURE;
        }

        $course = Course::query()->where('slug', $courseSlug)->first();

        if (! $course) {
            $this->error("Course not found: {$courseSlug}");

            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm("Delete all enrollments for course {$courseSlug}?")) {
            $this->warn('Reset cancelled.');

            return self::SUCCESS;
        }

        $deleted = DB::transaction(function () use ($course): int {
            return Enrollment::query()
                ->where('course_id', $course->id)
                ->delete();
        });

        $this->info("Deleted {$deleted} enrollments for course {$courseSlug}.");

        return self::SUCCESS;
    }
}