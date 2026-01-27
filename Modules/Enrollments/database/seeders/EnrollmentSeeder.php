<?php
declare(strict_types=1);

namespace Modules\Enrollments\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Auth\Models\User;
use Modules\Schemes\Models\Course;
use Modules\Enrollments\Models\Enrollment;
use Modules\Enrollments\Enums\ProgressStatus;

class EnrollmentSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info("Seeding enrollments and progress...");

        $students = User::whereHas('roles', function ($q) {
            $q->where('name', 'Student');
        })->limit(800)->get();

        $courses = Course::with('units.lessons')->get();

        if ($students->isEmpty() || $courses->isEmpty()) {
            echo "⚠️  No students or courses found. Skipping enrollment seeding.\n";
            return;
        }

        $this->command->info("Creating 500-800 enrollments...");

        $enrollments = [];
        $courseProgressData = [];
        $unitProgressData = [];
        $lessonProgressData = [];

        // Pre-fetch existing enrollments to avoid N+1 checks
        $existingPairs = \Illuminate\Support\Facades\DB::table('enrollments')
            ->select(['user_id', 'course_id'])
            ->get()
            ->map(fn($r) => $r->user_id . ':' . $r->course_id)
            ->flip()
            ->all();

        $processedStudents = 0;
        foreach ($students as $student) {
            $enrollmentCount = rand(3, 8);
            $randomCourses = $courses->random(min($enrollmentCount, $courses->count()));
            // ... (rest of loop)

            foreach ($randomCourses as $course) {
                $pairKey = $student->id . ':' . $course->id;
                if (isset($existingPairs[$pairKey])) {
                    continue;
                }

                $statusRandom = rand(1, 100);
                $status = match (true) {
                    $statusRandom <= 70 => 'active',
                    $statusRandom <= 85 => 'pending',
                    default => 'completed'
                };

                $enrollmentData = [
                    'user_id' => $student->id,
                    'course_id' => $course->id,
                    'status' => $status,
                    'enrolled_at' => now()->subDays(rand(1, 180)),
                    'completed_at' => $status === 'completed' ? now()->subDays(rand(1, 30)) : null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                $enrollments[] = $enrollmentData;

                $enrollmentIndex = count($enrollments) - 1;
                $courseProgressData[] = [
                    'enrollment_id' => $enrollmentIndex,
                    'status' => $this->mapStatus($status),
                    'progress_percent' => $status === 'completed' ? 100 : rand(0, 100),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                if (\Illuminate\Support\Facades\Schema::hasTable('unit_progress')) {
                    foreach ($course->units as $unit) {
                        $unitProgressData[] = [
                            'enrollment_id' => $enrollmentIndex,
                            'unit_id' => $unit->id,
                            'status' => 'not_started',
                            'progress_percent' => 0,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];

                        if (\Illuminate\Support\Facades\Schema::hasTable('lesson_progress')) {
                            foreach ($unit->lessons as $lesson) {
                                $lessonProgressData[] = [
                                    'enrollment_id' => $enrollmentIndex,
                                    'lesson_id' => $lesson->id,
                                    'status' => 'not_started',
                                    'progress_percent' => 0,
                                    'attempt_count' => 0,
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ];
                            }
                        }
                    }
                }
            }
        }

        if (!empty($enrollments)) {
            foreach (array_chunk($enrollments, 1000) as $chunk) {
                \Illuminate\Support\Facades\DB::table('enrollments')->insertOrIgnore($chunk);
                $this->command->info("  ✅ Inserted " . count($chunk) . " enrollments");
            }
            $this->insertProgressData($enrollments, $courseProgressData, $unitProgressData, $lessonProgressData);
        }

        $this->command->info("✅ Enrollment seeding completed!");
    }

    private function insertProgressData(array $enrollments, array $courseProgressData, array $unitProgressData, array $lessonProgressData): void
    {
        $userIds = collect($enrollments)->pluck('user_id')->unique()->all();
        $courseIds = collect($enrollments)->pluck('course_id')->unique()->all();

        $enrollmentModels = Enrollment::whereIn('user_id', $userIds)
            ->whereIn('course_id', $courseIds)
            ->get(['id', 'user_id', 'course_id']);

        if ($enrollmentModels->isEmpty()) {
            return;
        }

        if (!empty($enrollmentModels)) {
            $pairToId = [];
            foreach ($enrollmentModels as $model) {
                $pairToId[$model->user_id . ':' . $model->course_id] = $model->id;
            }

            $indexToId = [];
            foreach ($enrollments as $idx => $enroll) {
                $key = $enroll['user_id'] . ':' . $enroll['course_id'];
                if (isset($pairToId[$key])) {
                    $indexToId[$idx] = $pairToId[$key];
                }
            }

            // Insert Course Progress
            if (!empty($courseProgressData) && \Illuminate\Support\Facades\Schema::hasTable('course_progress')) {
                $courseBatch = [];
                foreach ($courseProgressData as $idx => $data) {
                    if (isset($indexToId[$idx])) {
                        $data['enrollment_id'] = $indexToId[$idx];
                        $courseBatch[] = $data;
                    }
                }
                if (!empty($courseBatch)) {
                    foreach (array_chunk($courseBatch, 1000) as $chunk) {
                        \Illuminate\Support\Facades\DB::table('course_progress')->insertOrIgnore($chunk);
                    }
                }
            }

            // Insert Unit Progress
            if (!empty($unitProgressData) && \Illuminate\Support\Facades\Schema::hasTable('unit_progress')) {
                $unitBatch = [];
                foreach ($unitProgressData as $data) {
                    $idx = $data['enrollment_id'];
                    if (isset($indexToId[$idx])) {
                        $data['enrollment_id'] = $indexToId[$idx];
                        $unitBatch[] = $data;
                    }
                }

                if (!empty($unitBatch)) {
                    foreach (array_chunk($unitBatch, 1000) as $chunk) {
                        \Illuminate\Support\Facades\DB::table('unit_progress')->insertOrIgnore($chunk);
                    }
                }
            }

            // Insert Lesson Progress
            if (!empty($lessonProgressData) && \Illuminate\Support\Facades\Schema::hasTable('lesson_progress')) {
                $lessonBatch = [];
                foreach ($lessonProgressData as $data) {
                    $idx = $data['enrollment_id'];
                    if (isset($indexToId[$idx])) {
                        $data['enrollment_id'] = $indexToId[$idx];
                        $lessonBatch[] = $data;
                    }
                }
                if (!empty($lessonBatch)) {
                    foreach (array_chunk($lessonBatch, 1000) as $chunk) {
                         \Illuminate\Support\Facades\DB::table('lesson_progress')->insertOrIgnore($chunk);
                    }
                }
            }
        }
    }

    private function mapStatus(string|ProgressStatus $enrollmentStatus): string
    {
        $status = is_string($enrollmentStatus) ? $enrollmentStatus : $enrollmentStatus->value;

        return match ($status) {
            'active' => ProgressStatus::InProgress->value,
            'completed' => ProgressStatus::Completed->value,
            default => ProgressStatus::NotStarted->value,
        };
    }
}
