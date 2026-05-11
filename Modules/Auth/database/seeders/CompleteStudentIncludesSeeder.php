<?php

declare(strict_types=1);

namespace Modules\Auth\Database\Seeders;

use App\Support\SeederDate;
use App\Support\RealisticSeederContent;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Modules\Auth\Models\User;
use Modules\Enrollments\Models\Enrollment;
use Modules\Forums\Models\Thread;
use Modules\Gamification\Models\Badge;
use Modules\Gamification\Models\LearningStreak;
use Modules\Gamification\Models\Level;
use Modules\Gamification\Models\Point;
use Modules\Gamification\Models\UserBadge;
use Modules\Gamification\Models\UserGamificationStat;
use Modules\Learning\Enums\OverrideType;
use Modules\Learning\Models\Assignment;
use Modules\Learning\Models\Override;
use Modules\Learning\Models\Submission;
use Modules\Schemes\Models\Course;
use Modules\Schemes\Models\Lesson;
use Modules\Schemes\Models\Unit;
use Spatie\Permission\Models\Role;


class CompleteStudentIncludesSeeder extends Seeder
{
    public function run(): void
    {
        
        Role::firstOrCreate(['name' => 'Superadmin', 'guard_name' => 'api']);
        Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'api']);
        Role::firstOrCreate(['name' => 'Instructor', 'guard_name' => 'api']);
        Role::firstOrCreate(['name' => 'Student', 'guard_name' => 'api']);

        
        $studentEmail = RealisticSeederContent::demoEmail('student.full.includes');
        $studentUsername = 'student_full_includes';

        $adminEmail = RealisticSeederContent::demoEmail('admin.full.includes');
        $adminUsername = 'admin_full_includes';

        $instructorEmail = RealisticSeederContent::demoEmail('instructor.full.includes');
        $instructorUsername = 'instructor_full_includes';

        $otherStudentEmail = RealisticSeederContent::demoEmail('student.granted.overrides');
        $otherStudentUsername = 'student_granted_overrides';

        $seedBase = SeederDate::randomPastCarbonBetween(45, 150);
        $verifiedAt = $seedBase->copy()->subDays(2);
        $publishedAt = $seedBase->copy()->subDays(4);
        $enrolledAt = $seedBase->copy()->subDays(7);
        $streakDate = $seedBase->copy()->subDay();

        $student = User::updateOrCreate(
            ['email' => $studentEmail],
            $this->onlyExistingColumns('users', [
                'name' => 'Student Full Includes',
                'username' => $studentUsername,
                'password' => Hash::make('password'),
                'status' => 'active',
                'email_verified_at' => $verifiedAt->toDateTimeString(),
                'is_password_set' => $this->pgsqlBool(true),
            ]),
        );
        $student->syncRoles(['Student']);

        $admin = User::updateOrCreate(
            ['email' => $adminEmail],
            $this->onlyExistingColumns('users', [
                'name' => 'Admin Full Includes',
                'username' => $adminUsername,
                'password' => Hash::make('password'),
                'status' => 'active',
                'email_verified_at' => $verifiedAt->toDateTimeString(),
                'is_password_set' => $this->pgsqlBool(true),
            ]),
        );
        $admin->syncRoles(['Admin']);

        $instructor = User::updateOrCreate(
            ['email' => $instructorEmail],
            $this->onlyExistingColumns('users', [
                'name' => 'Instructor Full Includes',
                'username' => $instructorUsername,
                'password' => Hash::make('password'),
                'status' => 'active',
                'email_verified_at' => $verifiedAt->toDateTimeString(),
                'is_password_set' => $this->pgsqlBool(true),
            ]),
        );
        $instructor->syncRoles(['Instructor']);

        $otherStudent = User::updateOrCreate(
            ['email' => $otherStudentEmail],
            $this->onlyExistingColumns('users', [
                'name' => 'Student Granted Overrides',
                'username' => $otherStudentUsername,
                'password' => Hash::make('password'),
                'status' => 'active',
                'email_verified_at' => $verifiedAt->toDateTimeString(),
                'is_password_set' => $this->pgsqlBool(true),
            ]),
        );
        $otherStudent->syncRoles(['Student']);

        $course = Course::updateOrCreate(
            ['slug' => 'seed-complete-student-course'],
            $this->onlyExistingColumns('courses', [
                'code' => 'SEEDC001',
                'title' => 'Seed: Complete Student Course',
                'short_desc' => 'Deterministic course for complete student includes seeder.',
                'type' => 'okupasi',
                'level_tag' => 'dasar',
                'enrollment_type' => 'auto_accept',
                'status' => 'published',
                'published_at' => $publishedAt->toDateTimeString(),
                'instructor_id' => $instructor->id,
            ]),
        );

        $unit = Unit::updateOrCreate(
            ['slug' => 'seed-complete-student-unit'],
            $this->onlyExistingColumns('units', [
                'course_id' => $course->id,
                'code' => 'SEEDU01',
                'title' => 'Seed Unit',
                'description' => 'Unit for deterministic seeded course.',
                'order' => 1,
            ]),
        );

        $lesson = Lesson::updateOrCreate(
            ['slug' => 'seed-complete-student-lesson'],
            $this->onlyExistingColumns('lessons', [
                'unit_id' => $unit->id,
                'title' => 'Seed Lesson',
                'description' => 'Lesson for deterministic seeded unit.',
                'markdown_content' => 'Seeded lesson content.',
                'content_type' => 'markdown',
                'content_url' => null,
                'order' => 1,
            ]),
        );

        
        $enrollment = Enrollment::firstOrCreate(
            [
                'user_id' => $student->id,
                'course_id' => $course->id,
            ],
            $this->onlyExistingColumns('enrollments', [
                'status' => 'active',
                'enrolled_at' => $enrolledAt->toDateTimeString(),
                'completed_at' => null,
            ]),
        );

        
        $student->managedCourses()->syncWithoutDetaching([$course->id]);

        
        UserGamificationStat::updateOrCreate(
            ['user_id' => $student->id],
            $this->onlyExistingColumns('user_gamification_stats', [
                'total_xp' => 1200,
                'global_level' => 3,
                'current_streak' => 5,
                'longest_streak' => 7,
                'last_activity_date' => $streakDate->toDateString(),
                'stats_updated_at' => $seedBase->toDateTimeString(),
            ]),
        );

        
        $badge = Badge::firstOrCreate(
            ['code' => 'BADGE-SEED-FULL-001'],
            $this->onlyExistingColumns('badges', [
                'name' => 'Seed Badge',
                'description' => 'Badge created by CompleteStudentIncludesSeeder.',
                'type' => 'achievement',
                'threshold' => 1,
            ]),
        );
        UserBadge::firstOrCreate(
            ['user_id' => $student->id, 'badge_id' => $badge->id],
            $this->onlyExistingColumns('user_badges', ['earned_at' => $seedBase->copy()->subDays(2)->toDateTimeString()]),
        );

        Point::firstOrCreate(
            [
                'user_id' => $student->id,
                'source_type' => 'lesson',
                'source_id' => 900001,
            ],
            $this->onlyExistingColumns('points', [
                'points' => 75,
                'reason' => 'completion',
                'description' => 'Seeded points for complete includes.',
            ]),
        );

        
        Level::updateOrCreate(
            ['user_id' => $student->id, 'course_id' => null],
            $this->onlyExistingColumns('levels', ['current_level' => 3]),
        );
        Level::updateOrCreate(
            ['user_id' => $student->id, 'course_id' => $course->id],
            $this->onlyExistingColumns('levels', ['current_level' => 2]),
        );

        
        LearningStreak::updateOrCreate(
            ['user_id' => $student->id, 'activity_date' => $seedBase->toDateString()],
            $this->onlyExistingColumns('learning_streaks', ['xp_earned' => 120]),
        );
        LearningStreak::updateOrCreate(
            ['user_id' => $student->id, 'activity_date' => $streakDate->toDateString()],
            $this->onlyExistingColumns('learning_streaks', ['xp_earned' => 80]),
        );

        
        $assignmentForSubmission = Assignment::updateOrCreate(
            [
                'lesson_id' => $lesson->id,
                'created_by' => $instructor->id,
                'title' => 'Seed Assignment: Complete Student',
            ],
            $this->onlyExistingColumns('assignments', [
                'description' => 'Assignment created by CompleteStudentIncludesSeeder.',
                'submission_type' => 'text',
                'max_score' => 100,
                'available_from' => $seedBase->copy()->subDays(3)->toDateTimeString(),
                'status' => 'published',
            ]),
        );

        Submission::updateOrCreate(
            [
                'assignment_id' => $assignmentForSubmission->id,
                'user_id' => $student->id,
            ],
            $this->onlyExistingColumns('submissions', [
                'enrollment_id' => $enrollment->id,
                'answer_text' => 'This is a seeded submission answer.',
                'status' => 'submitted',
                'score' => null,
                'feedback' => null,
                'submitted_at' => $streakDate->toDateTimeString(),
                'graded_at' => null,
            ]),
        );

        
        Assignment::updateOrCreate(
            [
                'lesson_id' => $lesson->id,
                'created_by' => $student->id,
                'title' => 'Seed Assignment: Student Created',
            ],
            $this->onlyExistingColumns('assignments', [
                'description' => 'Assignment created by the seeded student (for include coverage).',
                'submission_type' => 'text',
                'max_score' => 50,
                'available_from' => $streakDate->toDateTimeString(),
                'status' => 'published',
            ]),
        );

        
        Override::firstOrCreate(
            [
                'assignment_id' => $assignmentForSubmission->id,
                'student_id' => $student->id,
                'grantor_id' => $admin->id,
                'type' => OverrideType::Deadline,
            ],
            $this->onlyExistingColumns('overrides', [
                'reason' => 'Seeded deadline extension override.',
                'value' => ['extended_deadline' => $seedBase->copy()->addDays(14)->toISOString()],
                'granted_at' => $seedBase->copy()->subHours(2)->toDateTimeString(),
                'expires_at' => $seedBase->copy()->addDays(30)->toDateTimeString(),
            ]),
        );

        Override::firstOrCreate(
            [
                'assignment_id' => $assignmentForSubmission->id,
                'student_id' => $otherStudent->id,
                'grantor_id' => $student->id,
                'type' => OverrideType::Attempts,
            ],
            $this->onlyExistingColumns('overrides', [
                'reason' => 'Seeded additional attempts override (grantor=seeded student).',
                'value' => ['additional_attempts' => 2],
                'granted_at' => $seedBase->copy()->subHour()->toDateTimeString(),
                'expires_at' => $seedBase->copy()->addDays(30)->toDateTimeString(),
            ]),
        );

        
        Thread::firstOrCreate(
            [
                'course_id' => $course->id,
                'author_id' => $student->id,
                'title' => '[Seed] Complete Student Thread',
            ],
            $this->onlyExistingColumns('threads', [
                'content' => 'Thread created by CompleteStudentIncludesSeeder.',
                'is_pinned' => $this->pgsqlBool(false),
                'is_closed' => $this->pgsqlBool(false),
                'is_resolved' => $this->pgsqlBool(false),
                'views_count' => 10,
                'replies_count' => 0,
                'last_activity_at' => $seedBase->toDateTimeString(),
            ]),
        );

        $this->command?->info('✅ CompleteStudentIncludesSeeder done.');
        $this->command?->info("   Student email: {$studentEmail}");
        $this->command?->info("   Student id: {$student->id}");
        $this->command?->info('   Try: GET /api/v1/users/'.$student->id.'?include=roles,privacySettings,enrollments,managedCourses,gamificationStats,badges,points,levels,learningStreaks,submissions,assignments,receivedOverrides,grantedOverrides,threads');
    }

    private function pgsqlBool(mixed $value): string
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false';
    }

    
    private function onlyExistingColumns(string $table, array $attributes): array
    {
        static $columnsCache = [];

        if (! isset($columnsCache[$table])) {
            if (! Schema::hasTable($table)) {
                return [];
            }

            $columnsCache[$table] = array_flip(Schema::getColumnListing($table));
        }

        return array_intersect_key($attributes, $columnsCache[$table]);
    }
}
