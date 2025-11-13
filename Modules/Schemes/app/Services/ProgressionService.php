<?php

namespace Modules\Schemes\Services;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Enrollments\Models\CourseProgress;
use Modules\Enrollments\Models\Enrollment;
use Modules\Enrollments\Models\LessonProgress;
use Modules\Enrollments\Models\UnitProgress;
use Modules\Schemes\Events\CourseCompleted;
use Modules\Schemes\Events\UnitCompleted;
use Modules\Schemes\Models\Course;
use Modules\Schemes\Models\Lesson;
use Modules\Schemes\Models\Unit;

class ProgressionService
{
    public function getEnrollmentForCourse(int $courseId, int $userId): ?Enrollment
    {
        return Enrollment::query()
            ->where('course_id', $courseId)
            ->where('user_id', $userId)
            ->whereIn('status', ['active', 'completed'])
            ->first();
    }

    public function markLessonCompleted(Lesson $lesson, Enrollment $enrollment): void
    {
        DB::transaction(function () use ($lesson, $enrollment) {
            $lessonModel = $lesson->fresh([
                'unit.course',
                'unit.lessons' => function ($query) {
                    $query->where('status', 'published')->orderBy('order');
                },
            ]);

            if (! $lessonModel || ! $lessonModel->unit || ! $lessonModel->unit->course) {
                return;
            }

            $this->storeLessonCompletion($lessonModel, $enrollment);

            $unitResult = $this->updateUnitProgress(
                $lessonModel->unit,
                $enrollment,
                $lessonModel->unit->lessons
            );

            $this->updateCourseProgress($lessonModel->unit->course, $enrollment);

            if ($unitResult['just_completed']) {
                UnitCompleted::dispatch($lessonModel->unit, $enrollment->user_id, $enrollment->id);
            }
        });
    }

    public function onLessonCompleted(Lesson $lesson, Enrollment $enrollment): void
    {
        $this->markLessonCompleted($lesson, $enrollment);
    }

    public function markUnitCompleted(Unit $unit, Enrollment $enrollment): void
    {
        DB::transaction(function () use ($unit, $enrollment) {
            $unitModel = $unit->fresh([
                'course',
                'lessons' => function ($query) {
                    $query->where('status', 'published')->orderBy('order');
                },
            ]);

            if (! $unitModel || ! $unitModel->course) {
                return;
            }

            $this->updateUnitProgress($unitModel, $enrollment, $unitModel->lessons, true);
            $this->updateCourseProgress($unitModel->course, $enrollment);
        });
    }

    public function canAccessLesson(Lesson $lesson, Enrollment $enrollment): bool
    {
        $lessonModel = $lesson->fresh([
            'unit.course',
            'unit.lessons' => function ($query) {
                $query->where('status', 'published')->orderBy('order');
            },
        ]);

        if (! $lessonModel || ! $lessonModel->unit || ! $lessonModel->unit->course) {
            return false;
        }

        $course = $lessonModel->unit->course;
        if ($course->progression_mode === 'free') {
            return true;
        }

        // Ensure previous units are completed
        $orderedUnits = $course->units()
            ->where('status', 'published')
            ->orderBy('order')
            ->get(['id']);

        foreach ($orderedUnits as $courseUnit) {
            if ((int) $courseUnit->id === (int) $lessonModel->unit->id) {
                break;
            }

            $unitStatus = UnitProgress::query()
                ->where('enrollment_id', $enrollment->id)
                ->where('unit_id', $courseUnit->id)
                ->value('status');

            if ($unitStatus !== 'completed') {
                return false;
            }
        }

        $lessons = $lessonModel->unit->lessons ?? new EloquentCollection();
        if ($lessons->isEmpty()) {
            return true;
        }

        $progressMap = LessonProgress::query()
            ->where('enrollment_id', $enrollment->id)
            ->whereIn('lesson_id', $lessons->pluck('id'))
            ->get()
            ->keyBy('lesson_id');

        foreach ($lessons as $unitLesson) {
            if ((int) $unitLesson->id === (int) $lessonModel->id) {
                return true;
            }

            if (($progressMap->get($unitLesson->id)?->status ?? 'not_started') !== 'completed') {
                return false;
            }
        }

        return true;
    }

    public function getCourseProgressData(Course $course, Enrollment $enrollment): array
    {
        $courseModel = $course->fresh([
            'units' => function ($query) {
                $query->where('status', 'published')
                    ->orderBy('order')
                    ->with(['lessons' => function ($lessonQuery) {
                        $lessonQuery->where('status', 'published')->orderBy('order');
                    }]);
            },
        ]);

        if (! $courseModel) {
            return [];
        }

        DB::transaction(function () use ($courseModel, $enrollment) {
            foreach ($courseModel->units as $unit) {
                $this->updateUnitProgress($unit, $enrollment, $unit->lessons);
            }
            $this->updateCourseProgress($courseModel, $enrollment);
        });

        $unitIds = $courseModel->units->pluck('id');
        $lessonIds = $courseModel->units
            ->flatMap(fn ($unit) => $unit->lessons->pluck('id'))
            ->values();

        $lessonProgressMap = LessonProgress::query()
            ->where('enrollment_id', $enrollment->id)
            ->whereIn('lesson_id', $lessonIds)
            ->get()
            ->keyBy('lesson_id');

        $unitProgressMap = UnitProgress::query()
            ->where('enrollment_id', $enrollment->id)
            ->whereIn('unit_id', $unitIds)
            ->get()
            ->keyBy('unit_id');

        $courseProgress = CourseProgress::query()
            ->where('enrollment_id', $enrollment->id)
            ->first();

        $unitsData = [];
        $previousUnitsCompleted = true;
        $completedUnitsCount = 0;

        foreach ($courseModel->units as $unit) {
            $lessons = $unit->lessons ?? new EloquentCollection();
            $unitProgress = $unitProgressMap->get($unit->id);

            $lessonsData = [];
            $completedLessonCount = 0;
            $previousLessonsCompleted = true;

            foreach ($lessons as $lessonItem) {
                $lessonProgress = $lessonProgressMap->get($lessonItem->id);
                $lessonStatus = $lessonProgress->status ?? 'not_started';
                $lessonPercent = $lessonProgress->progress_percent ?? 0;

                if ($lessonStatus === 'completed') {
                    $completedLessonCount++;
                }

                $isLessonLocked = false;
                if ($courseModel->progression_mode === 'sequential') {
                    $isLessonLocked = ! $previousUnitsCompleted || ! $previousLessonsCompleted;
                }

                $lessonsData[] = [
                    'id' => $lessonItem->id,
                    'slug' => $lessonItem->slug,
                    'title' => $lessonItem->title,
                    'order' => $lessonItem->order,
                    'status' => $lessonStatus,
                    'progress_percent' => round($lessonPercent, 2),
                    'is_locked' => $isLessonLocked,
                    'completed_at' => optional($lessonProgress?->completed_at)->toIso8601String(),
                ];

                if ($lessonStatus !== 'completed') {
                    $previousLessonsCompleted = false;
                }
            }

            $totalLessons = max(1, $lessons->count());
            $derivedUnitPercent = round(($completedLessonCount / $totalLessons) * 100, 2);

            $unitStatus = $unitProgress->status ?? (
                $lessons->isEmpty() ? 'completed' :
                ($completedLessonCount === $lessons->count() ? 'completed' :
                    ($completedLessonCount > 0 ? 'in_progress' : 'not_started'))
            );

            $unitPercent = $unitProgress->progress_percent ?? $derivedUnitPercent;

            if ($unitStatus === 'completed') {
                $completedUnitsCount++;
            }

            $isUnitLocked = $courseModel->progression_mode === 'sequential' && ! $previousUnitsCompleted;

            $unitsData[] = [
                'id' => $unit->id,
                'slug' => $unit->slug,
                'title' => $unit->title,
                'order' => $unit->order,
                'status' => $unitStatus,
                'progress_percent' => round($unitPercent, 2),
                'is_locked' => $isUnitLocked,
                'completed_at' => optional($unitProgress?->completed_at)->toIso8601String(),
                'lessons' => $lessonsData,
            ];

            if ($unitStatus !== 'completed') {
                $previousUnitsCompleted = false;
            }
        }

        $totalUnits = max(1, $courseModel->units->count());
        $derivedCoursePercent = round(($completedUnitsCount / $totalUnits) * 100, 2);

        $courseStatus = $courseProgress->status ?? (
            $courseModel->units->isEmpty() ? 'completed' :
            ($completedUnitsCount === $courseModel->units->count() ? 'completed' :
                ($completedUnitsCount > 0 ? 'in_progress' : 'not_started'))
        );

        $coursePercent = $courseProgress->progress_percent ?? $derivedCoursePercent;

        return [
            'course' => [
                'id' => $courseModel->id,
                'slug' => $courseModel->slug,
                'title' => $courseModel->title,
                'progression_mode' => $courseModel->progression_mode,
                'status' => $courseStatus,
                'progress_percent' => round($coursePercent, 2),
                'completed_at' => optional($courseProgress?->completed_at)->toIso8601String(),
            ],
            'units' => $unitsData,
        ];
    }

    private function storeLessonCompletion(Lesson $lesson, Enrollment $enrollment): void
    {
        $progress = LessonProgress::query()
            ->firstOrNew([
                'enrollment_id' => $enrollment->id,
                'lesson_id' => $lesson->id,
            ]);

        if (! $progress->started_at) {
            $progress->started_at = Carbon::now();
        }

        $progress->status = 'completed';
        $progress->progress_percent = 100;
        $progress->completed_at = Carbon::now();
        $progress->save();
    }

    private function updateUnitProgress(
        Unit $unit,
        Enrollment $enrollment,
        ?EloquentCollection $lessons = null,
        bool $forceComplete = false
    ): array {
        $lessonsCollection = $lessons ?? $unit->lessons()
            ->where('status', 'published')
            ->orderBy('order')
            ->get();

        $lessonIds = $lessonsCollection->pluck('id');
        $totalLessons = $lessonIds->count();

        if ($totalLessons === 0) {
            $status = 'completed';
            $progressPercent = 100;
        } else {
            $completedLessons = LessonProgress::query()
                ->where('enrollment_id', $enrollment->id)
                ->whereIn('lesson_id', $lessonIds)
                ->where('status', 'completed')
                ->count();

            $hasProgress = LessonProgress::query()
                ->where('enrollment_id', $enrollment->id)
                ->whereIn('lesson_id', $lessonIds)
                ->whereIn('status', ['in_progress', 'completed'])
                ->exists();

            if ($forceComplete || $completedLessons === $totalLessons) {
                $status = 'completed';
                $progressPercent = 100;
            } elseif ($hasProgress || $completedLessons > 0) {
                $status = 'in_progress';
                $progressPercent = round(($completedLessons / $totalLessons) * 100, 2);
            } else {
                $status = 'not_started';
                $progressPercent = 0;
            }
        }

        $progress = UnitProgress::query()
            ->firstOrNew([
                'enrollment_id' => $enrollment->id,
                'unit_id' => $unit->id,
            ]);

        $previousStatus = $progress->exists ? $progress->status : 'not_started';

        $progress->status = $status;
        $progress->progress_percent = $progressPercent;

        if ($status !== 'not_started' && ! $progress->started_at) {
            $progress->started_at = Carbon::now();
        }

        if ($status === 'completed' && ! $progress->completed_at) {
            $progress->completed_at = Carbon::now();
        }

        if ($status !== 'completed') {
            $progress->completed_at = null;
        }

        $progress->save();

        return [
            'status' => $status,
            'progress_percent' => $progressPercent,
            'just_completed' => $previousStatus !== 'completed' && $status === 'completed',
        ];
    }

    private function updateCourseProgress(Course $course, Enrollment $enrollment): array
    {
        $unitIds = $course->units()
            ->where('status', 'published')
            ->orderBy('order')
            ->pluck('id');

        $totalUnits = $unitIds->count();

        if ($totalUnits === 0) {
            $status = 'completed';
            $progressPercent = 100;
        } else {
            $completedUnits = UnitProgress::query()
                ->where('enrollment_id', $enrollment->id)
                ->whereIn('unit_id', $unitIds)
                ->where('status', 'completed')
                ->count();

            $hasProgress = UnitProgress::query()
                ->where('enrollment_id', $enrollment->id)
                ->whereIn('unit_id', $unitIds)
                ->whereIn('status', ['in_progress', 'completed'])
                ->exists();

            if ($completedUnits === $totalUnits) {
                $status = 'completed';
                $progressPercent = 100;
            } elseif ($hasProgress || $completedUnits > 0) {
                $status = 'in_progress';
                $progressPercent = round(($completedUnits / $totalUnits) * 100, 2);
            } else {
                $status = 'not_started';
                $progressPercent = 0;
            }
        }

        $progress = CourseProgress::query()
            ->firstOrNew([
                'enrollment_id' => $enrollment->id,
            ]);

        $previousStatus = $progress->exists ? $progress->status : 'not_started';

        $progress->status = $status;
        $progress->progress_percent = $progressPercent;

        if ($status !== 'not_started' && ! $progress->started_at) {
            $progress->started_at = Carbon::now();
        }

        if ($status === 'completed' && ! $progress->completed_at) {
            $progress->completed_at = Carbon::now();
        }

        if ($status !== 'completed') {
            $progress->completed_at = null;
        }

        $progress->save();

        $courseJustCompleted = $previousStatus !== 'completed' && $status === 'completed';

        // Update enrollment status and completed_at, but not progress_percent
        // Progress is now stored in course_progress table
        if ($status === 'completed') {
            $enrollment->completed_at = $enrollment->completed_at ?? Carbon::now();
            $enrollment->status = 'completed';
        } elseif ($enrollment->status === 'completed' && $status !== 'completed') {
            $enrollment->status = 'active';
            $enrollment->completed_at = null;
        }
        $enrollment->save();

        if ($courseJustCompleted) {
            CourseCompleted::dispatch($course->fresh(), $enrollment->fresh());
        }

        return [
            'status' => $status,
            'progress_percent' => $progressPercent,
            'just_completed' => $courseJustCompleted,
        ];
    }
}


