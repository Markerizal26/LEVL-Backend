<?php

declare(strict_types=1);

namespace Modules\Learning\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Schemes\Models\Lesson;
use Modules\Auth\Models\User;
use Modules\Enrollments\Models\Enrollment;
use Carbon\Carbon;

class AssignmentSeederEnhanced extends Seeder
{
    private array $assignmentTitles = [
        'Complete Your First Project',
        'Weekly Quiz Assessment',
        'Build a Real-World Application',
        'Code Review Challenge',
        'Design System Implementation',
        'API Integration Exercise',
        'Database Design Task',
        'Security Audit Assignment',
        'Performance Optimization Challenge',
        'UI/UX Improvement Project',
        'Testing & Quality Assurance',
        'Documentation Sprint',
        'Debugging Exercise',
        'Refactoring Challenge',
        'Best Practices Implementation',
        'Team Collaboration Task',
        'Code Quality Assessment',
        'Architecture Design Document',
        'Research & Analysis Report',
        'Final Project Submission',
    ];

    public function run(): void
    {
        $this->command->info("\n╔══════════════════════════════════════════════════╗");
        $this->command->info("║     📝 ASSIGNMENT & SUBMISSION SEEDER            ║");
        $this->command->info("╚══════════════════════════════════════════════════╝\n");

        $instructorIds = User::whereHas('roles', fn ($q) => $q->where('name', 'Instructor'))
            ->pluck('id')
            ->toArray();

        if (empty($instructorIds)) {
            $this->command->warn("⚠️  No instructors found. Skipping assignment seeding.");
            return;
        }

        $this->command->info("📚 Pre-fetching instructors: " . count($instructorIds));

        $lessons = Lesson::with(['unit.course'])->whereHas('unit.course', function ($q) {
            $q->where('status', 'published');
        })->get();

        if ($lessons->isEmpty()) {
            $this->command->warn("⚠️  No published lessons found. Skipping assignment seeding.");
            return;
        }

        $this->command->info("📖 Found {$lessons->count()} lessons");

        $assignments = [];
        $assignmentCount = 0;

        $this->command->info("🔨 Creating assignments for lessons...\n");

        foreach ($lessons as $lesson) {
            $numAssignments = rand(5, 8);

            for ($i = 0; $i < $numAssignments; $i++) {
                $daysFromNow = rand(-30, 60);
                $deadline = Carbon::now()->addDays($daysFromNow);
                $availableFrom = $daysFromNow > 0
                    ? Carbon::now()->subDays(rand(1, 7))
                    : Carbon::now()->subDays(rand(7, 90));

                $randomTitle = $this->assignmentTitles[array_rand($this->assignmentTitles)];
                $titleVariation = $randomTitle . ' - ' . ucfirst(fake()->word());

                $assignments[] = [
                    'assignable_type' => 'Modules\\\\Schemes\\\\Models\\\\Lesson',
                    'assignable_id' => $lesson->id,
                    'lesson_id' => $lesson->id,
                    'title' => $titleVariation,
                    'description' => $this->generateAssignmentDescription(),
                    'submission_type' => fake()->randomElement(['text', 'file', 'mixed']),
                    'max_score' => $this->getRandomMaxScore(),
                    'available_from' => $availableFrom,
                    'deadline_at' => $deadline,
                    'tolerance_minutes' => rand(0, 30),
                    'time_limit_minutes' => rand(0, 1) ? rand(30, 180) : null,
                    'max_attempts' => $this->getRandomAttempts(),
                    'cooldown_minutes' => rand(0, 60),
                    'retake_enabled' => (bool) rand(0, 1),
                    'review_mode' => fake()->randomElement(['immediate', 'deferred', 'hidden']),
                    'randomization_type' => fake()->randomElement(['static', 'random_order', 'bank']),
                    'status' => 'published',
                    'created_by' => $instructorIds[array_rand($instructorIds)],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                $assignmentCount++;

                if (count($assignments) >= 1000) {
                    DB::table('assignments')->insertOrIgnore($assignments);
                    $this->command->info("  ✅ Inserted {$assignmentCount} assignments");
                    $assignments = [];
                }
            }
        }

        if (!empty($assignments)) {
            DB::table('assignments')->insertOrIgnore($assignments);
            $this->command->info("  ✅ Inserted {$assignmentCount} assignments (final batch)");
        }

        $this->command->info("\n📊 Assignment Creation Summary:");
        $this->command->info("  Total Assignments: {$assignmentCount}");
        $this->command->info("  Average per Lesson: " . round($assignmentCount / $lessons->count(), 2));

        $this->createSubmissions();

        $this->command->info("\n╔══════════════════════════════════════════════════╗");
        $this->command->info("║   ✅ ASSIGNMENT SEEDING COMPLETED!               ║");
        $this->command->info("╚══════════════════════════════════════════════════╝\n");
    }

    private function createSubmissions(): void
    {
        $this->command->info("\n📤 Creating student submissions...\n");

        $enrollmentsByCourse = Enrollment::whereIn('status', ['active', 'completed'])
            ->with(['user', 'course'])
            ->get()
            ->groupBy('course_id');

        $assignments = DB::table('assignments')
            ->join('lessons', 'assignments.lesson_id', '=', 'lessons.id')
            ->join('units', 'lessons.unit_id', '=', 'units.id')
            ->select('assignments.*', 'units.course_id')
            ->get()
            ->groupBy('course_id');

        $submissionCount = 0;
        $submissions = [];

        foreach ($assignments as $courseId => $courseAssignments) {
            $enrollments = $enrollmentsByCourse->get($courseId, collect());

            if ($enrollments->isEmpty()) {
                continue;
            }

            foreach ($courseAssignments as $assignment) {
                $numSubmissions = (int) ceil($enrollments->count() * (rand(50, 70) / 100));
                $randomEnrollments = $enrollments->random(min($numSubmissions, $enrollments->count()));

                foreach ($randomEnrollments as $enrollment) {
                    $statusRandom = rand(1, 100);
                    $status = match (true) {
                        $statusRandom <= 40 => 'graded',
                        $statusRandom <= 70 => 'submitted',
                        default => 'draft',
                    };

                    $submittedAt = in_array($status, ['submitted', 'graded'])
                        ? Carbon::now()->subDays(rand(1, 30))
                        : null;

                    $submissions[] = [
                        'assignment_id' => $assignment->id,
                        'user_id' => $enrollment->user_id,
                        'status' => $status,
                        'submitted_at' => $submittedAt,
                        'started_at' => $submittedAt ? $submittedAt->subMinutes(rand(5, 120)) : null,
                        'score' => $status === 'graded' ? rand(0, (int) $assignment->max_score) : null,
                        'attempt_number' => rand(1, min(3, (int) $assignment->max_attempts)),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    $submissionCount++;

                    if (count($submissions) >= 1000) {
                        DB::table('submissions')->insertOrIgnore($submissions);
                        $this->command->info("  ✅ Inserted {$submissionCount} submissions");
                        $submissions = [];
                    }
                }
            }
        }

        if (!empty($submissions)) {
            DB::table('submissions')->insertOrIgnore($submissions);
            $this->command->info("  ✅ Inserted {$submissionCount} submissions (final batch)");
        }

        $this->command->info("\n📊 Submission Summary:");
        $this->command->info("  Total Submissions: {$submissionCount}");
        $this->command->info("  Submission Rate: 50-70% of enrolled students");

        $statusDistribution = DB::table('submissions')
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        foreach ($statusDistribution as $status => $count) {
            $percent = round(($count / $submissionCount) * 100, 1);
            $this->command->info("    - {$status}: {$count} ({$percent}%)");
        }
    }

    private function generateAssignmentDescription(): string
    {
        $templates = [
            "Complete this assignment to demonstrate your understanding of the concepts covered in this lesson. " .
            "Submit your work by the deadline and ensure all requirements are met.",

            "This assignment will test your practical skills in implementing the techniques we've learned. " .
            "Make sure to follow best practices and document your approach.",

            "Apply what you've learned to solve real-world problems. Your submission should include " .
            "working code, documentation, and a brief explanation of your approach.",

            "Demonstrate mastery of this topic by completing the following tasks. " .
            "Quality and attention to detail will be key factors in evaluation.",

            "This practical exercise will help reinforce your learning. Complete all sections and " .
            "submit your best work. Late submissions may incur penalties.",
        ];

        return $templates[array_rand($templates)];
    }

    private function generateFeedback(): string
    {
        $positives = [
            "Excellent work!",
            "Great job!",
            "Well done!",
            "Good effort!",
            "Nice implementation!",
        ];

        $improvements = [
            "Consider refactoring for better readability.",
            "Add more test coverage for edge cases.",
            "Improve error handling in some sections.",
            "Documentation could be more comprehensive.",
            "Consider performance optimizations.",
        ];

        return $positives[array_rand($positives)] . " " . $improvements[array_rand($improvements)];
    }

    private function getRandomMaxScore(): int
    {
        $scores = [10, 20, 25, 50, 100];
        return $scores[array_rand($scores)];
    }

    private function getRandomAttempts(): int
    {
        $weights = [
            1 => 20,
            2 => 30,
            3 => 35,
            99 => 15,
        ];

        $rand = rand(1, 100);
        $cumulative = 0;

        foreach ($weights as $attempts => $weight) {
            $cumulative += $weight;
            if ($rand <= $cumulative) {
                return $attempts;
            }
        }

        return 3;
    }
}
