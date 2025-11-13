<?php

namespace Modules\Enrollments\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Modules\Enrollments\Events\EnrollmentCreated;
use Modules\Enrollments\Models\CourseProgress;
use Modules\Enrollments\Models\LessonProgress;
use Modules\Enrollments\Models\UnitProgress;

class InitializeProgressForEnrollment implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(EnrollmentCreated $event): void
    {
        $enrollment = $event->enrollment->fresh([
            'course.units.lessons' => function ($query) {
                $query->where('status', 'published')->orderBy('order');
            },
        ]);

        if (! $enrollment || ! $enrollment->course) {
            return;
        }

        $course = $enrollment->course;

        DB::transaction(function () use ($enrollment, $course) {
            foreach ($course->units as $unit) {
                $this->ensureUnitProgressExists($enrollment->id, $unit->id);

                foreach ($unit->lessons as $lesson) {
                    $this->ensureLessonProgressExists($enrollment->id, $lesson->id);
                }
            }

            $this->ensureCourseProgressExists($enrollment->id, $course->id);

            // Progress is now stored in course_progress table, not in enrollments
            if ($enrollment->status === 'completed') {
                $enrollment->status = 'active';
            }
            $enrollment->completed_at = null;
            $enrollment->save();
        });
    }

    private function ensureUnitProgressExists(int $enrollmentId, int $unitId): void
    {
        UnitProgress::query()->updateOrCreate(
            [
                'enrollment_id' => $enrollmentId,
                'unit_id' => $unitId,
            ],
            [
                'status' => 'not_started',
                'progress_percent' => 0,
                'started_at' => null,
                'completed_at' => null,
            ]
        );
    }

    private function ensureLessonProgressExists(int $enrollmentId, int $lessonId): void
    {
        LessonProgress::query()->updateOrCreate(
            [
                'enrollment_id' => $enrollmentId,
                'lesson_id' => $lessonId,
            ],
            [
                'status' => 'not_started',
                'progress_percent' => 0,
                'started_at' => null,
                'completed_at' => null,
            ]
        );
    }

    private function ensureCourseProgressExists(int $enrollmentId, int $courseId): void
    {
        CourseProgress::query()->updateOrCreate(
            [
                'enrollment_id' => $enrollmentId,
            ],
            [
                'status' => 'not_started',
                'progress_percent' => 0,
                'started_at' => null,
                'completed_at' => null,
            ]
        );
    }
}

