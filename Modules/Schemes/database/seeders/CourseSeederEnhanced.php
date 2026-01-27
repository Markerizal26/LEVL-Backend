<?php

declare(strict_types=1);

namespace Modules\Schemes\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Auth\Models\User;
use Modules\Common\Models\Category;
use Modules\Schemes\Models\Course;
use Modules\Schemes\Models\Tag;
use Modules\Schemes\Enums\CourseStatus;
use Modules\Schemes\Enums\CourseType;
use Modules\Schemes\Enums\EnrollmentType;
use Modules\Schemes\Enums\LevelTag;

class CourseSeederEnhanced extends Seeder
{
    private const CHUNK_SIZE = 20;
    private const TOTAL_COURSES = 100;

    public function run(): void
    {
        $this->command->info("\n📚 Creating realistic courses...");

        $instructors = User::whereHas('roles', function ($q) {
            $q->whereIn('name', ['Instructor', 'Admin', 'Superadmin']);
        })->get();

        if ($instructors->isEmpty()) {
            $this->command->warn("  ⚠️  No instructors found. Please run UserSeeder first.");
            return;
        }

        $categories = Category::where('status', 'active')->get();
        $tags = Tag::all();

        if ($categories->isEmpty() || $tags->isEmpty()) {
            $this->command->warn("  ⚠️  No categories or tags found. Please run CategorySeeder and TagSeeder first.");
            return;
        }

        $this->command->info("  📊 Creating " . self::TOTAL_COURSES . " courses across all scenarios...");

        $distribution = [
            'published_auto' => 50,       // Published + Auto-accept enrollment
            'published_approval' => 20,    // Published + Approval required
            'published_key' => 15,         // Published + Key-based enrollment
            'draft' => 15,                 // Draft courses
        ];

        $created = 0;
        foreach ($distribution as $scenario => $count) {
            $this->command->info("\n  📝 Scenario: {$scenario} ({$count} courses)");
            $scenarioCourses = $this->createCoursesForScenario($scenario, $count);
            
            $this->assignInstructorsToCourses($scenarioCourses, $instructors);
            $this->attachTagsToCourses($scenarioCourses, $tags);
            $this->createCourseOutcomes($scenarioCourses);
            
            $created += $scenarioCourses->count();
            $this->command->info("    ✓ Created {$scenarioCourses->count()} courses for {$scenario}");
        }

        $total = Course::count();
        $this->command->info("\n✅ Course seeding completed!");
        $this->command->info("   📊 Total courses: {$total}");
        $this->printCourseSummary();
    }

    private function createCoursesForScenario(string $scenario, int $count): \Illuminate\Support\Collection
    {
        $factory = Course::factory()->count($count);

        return match ($scenario) {
            'published_auto' => $factory->published()->openEnrollment()->create(),
            'published_approval' => $factory->published()->state([
                'enrollment_type' => EnrollmentType::Approval->value
            ])->create(),
            'published_key' => $factory->published()->state([
                'enrollment_type' => EnrollmentType::KeyBased->value,
                'enrollment_key' => $this->generateEnrollmentKey(),
            ])->create(),
            'draft' => $factory->draft()->create(),
            default => collect(),
        };
    }

    private function generateEnrollmentKey(): string
    {
        $plainKey = \Illuminate\Support\Str::random(12);
        return bcrypt($plainKey);
    }

    private function assignInstructorsToCourses($courses, $instructors): void
    {
        if ($courses->isEmpty() || $instructors->isEmpty()) {
            return;
        }

        $courseAdmins = [];

        foreach ($courses as $course) {
            $numInstructors = fake()->numberBetween(1, 3);
            $selectedInstructors = $instructors->random(min($numInstructors, $instructors->count()));
            
            foreach ($selectedInstructors as $instructor) {
                $courseAdmins[] = [
                    'course_id' => $course->id,
                    'user_id' => $instructor->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        if (!empty($courseAdmins)) {
            foreach (array_chunk($courseAdmins, 100) as $chunk) {
                DB::table('course_admins')->insertOrIgnore($chunk);
            }
        }
    }

    private function attachTagsToCourses($courses, $tags): void
    {
        if ($courses->isEmpty() || $tags->isEmpty()) {
            return;
        }

        $courseTags = [];

        foreach ($courses as $course) {
            $numTags = fake()->numberBetween(3, 8);
            $selectedTags = $tags->random(min($numTags, $tags->count()));
            
            foreach ($selectedTags as $tag) {
                $courseTags[] = [
                    'course_id' => $course->id,
                    'tag_id' => $tag->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        if (!empty($courseTags)) {
            foreach (array_chunk($courseTags, 200) as $chunk) {
                DB::table('course_tag_pivot')->insertOrIgnore($chunk);
            }
        }
    }

    private function printCourseSummary(): void
    {
        $this->command->info("\n📋 Course Distribution:");

        $byStatus = Course::select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->get();
        
        foreach ($byStatus as $stat) {
            $statusValue = (string)($stat->status instanceof CourseStatus ? $stat->status->value : $stat->status);
            $this->command->info("   • Status '{$statusValue}': {$stat->total}");
        }

        $this->command->info("\n📊 Enrollment Type Distribution:");
        $byEnrollment = Course::select('enrollment_type', DB::raw('count(*) as total'))
            ->groupBy('enrollment_type')
            ->get();
        
        foreach ($byEnrollment as $stat) {
            $enrollmentValue = (string)($stat->enrollment_type instanceof EnrollmentType ? $stat->enrollment_type->value : $stat->enrollment_type);
            $this->command->info("   • {$enrollmentValue}: {$stat->total}");
        }

        $this->command->info("\n🎓 Level Distribution:");
        $byLevel = Course::select('level_tag', DB::raw('count(*) as total'))
            ->groupBy('level_tag')
            ->get();
        
        foreach ($byLevel as $stat) {
            $levelTagValue = (string)($stat->level_tag instanceof LevelTag ? $stat->level_tag->value : $stat->level_tag);
            $this->command->info("   • {$levelTagValue}: {$stat->total}");
        }

        $this->command->info("\n📦 Type Distribution:");
        $byType = Course::select('type', DB::raw('count(*) as total'))
            ->groupBy('type')
            ->get();
        
        foreach ($byType as $stat) {
            $typeValue = (string)($stat->type instanceof CourseType ? $stat->type->value : $stat->type);
            $this->command->info("   • {$typeValue}: {$stat->total}");
        }
    }

    private function createCourseOutcomes($courses): void
    {
        if ($courses->isEmpty()) {
            return;
        }

        $outcomes = [];
        $outcomeTexts = [
            'Build real-world projects from scratch',
            'Master industry-standard tools and technologies',
            'Understand advanced concepts and best practices',
            'Prepare for professional certification exams',
            'Develop a strong portfolio of work',
            'Collaborate effectively in team environments',
            'Apply theoretical knowledge to practical scenarios',
            'Troubleshoot and debug complex problems',
            'Implement security and performance best practices',
            'Stay updated with latest industry trends',
        ];

        foreach ($courses as $course) {
            $numOutcomes = rand(4, 7);
            $selectedOutcomes = array_rand($outcomeTexts, $numOutcomes);
            $selectedOutcomes = is_array($selectedOutcomes) ? $selectedOutcomes : [$selectedOutcomes];

            foreach ($selectedOutcomes as $order => $idx) {
                $outcomes[] = [
                    'course_id' => $course->id,
                    'outcome_text' => $outcomeTexts[$idx],
                    'order' => $order,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        if (!empty($outcomes)) {
            foreach (array_chunk($outcomes, 500) as $chunk) {
                DB::table('course_outcomes')->insertOrIgnore($chunk);
            }
        }
    }
}
