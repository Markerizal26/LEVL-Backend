<?php

namespace Modules\Schemes\Services;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Enrollments\Enums\EnrollmentStatus;
use Modules\Enrollments\Enums\ProgressStatus;
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
            ->whereIn('status', [
                EnrollmentStatus::Active,
                EnrollmentStatus::Completed,
            ])
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

            // Emit LessonCompleted event for gamification
            \Modules\Schemes\Events\LessonCompleted::dispatch($lessonModel, $enrollment->user_id, $enrollment->id);

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

            if ($unitStatus !== ProgressStatus::Completed) {
                return false;
            }
        }

        $lessons = $lessonModel->unit->lessons ?? new EloquentCollection;
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

            if (($progressMap->get($unitLesson->id)?->status ?? ProgressStatus::NotStarted) !== ProgressStatus::Completed) {
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
            $lessons = $unit->lessons ?? new EloquentCollection;
            $unitProgress = $unitProgressMap->get($unit->id);

            $lessonsData = [];
            $completedLessonCount = 0;
            $previousLessonsCompleted = true;

            foreach ($lessons as $lessonItem) {
                $lessonProgress = $lessonProgressMap->get($lessonItem->id);
                $lessonStatus = $lessonProgress->status ?? ProgressStatus::NotStarted;
                $lessonPercent = $lessonProgress->progress_percent ?? 0;

                if ($lessonStatus === ProgressStatus::Completed) {
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
                    'status' => $lessonStatus instanceof ProgressStatus ? $lessonStatus->value : $lessonStatus,
                    'progress_percent' => round($lessonPercent, 2),
                    'is_locked' => $isLessonLocked,
                    'completed_at' => optional($lessonProgress?->completed_at)->toIso8601String(),
                ];

                if ($lessonStatus !== ProgressStatus::Completed) {
                    $previousLessonsCompleted = false;
                }
            }

            $totalLessons = max(1, $lessons->count());
            $derivedUnitPercent = round(($completedLessonCount / $totalLessons) * 100, 2);

            $unitStatus = $unitProgress->status ?? (
                $lessons->isEmpty() ? ProgressStatus::Completed :
                ($completedLessonCount === $lessons->count() ? ProgressStatus::Completed :
                    ($completedLessonCount > 0 ? ProgressStatus::InProgress : ProgressStatus::NotStarted))
            );

            $unitPercent = $unitProgress->progress_percent ?? $derivedUnitPercent;

            if ($unitStatus === ProgressStatus::Completed) {
                $completedUnitsCount++;
            }

            $isUnitLocked = $courseModel->progression_mode === 'sequential' && ! $previousUnitsCompleted;

            $unitsData[] = [
                'id' => $unit->id,
                'slug' => $unit->slug,
                'title' => $unit->title,
                'order' => $unit->order,
                'status' => $unitStatus instanceof ProgressStatus ? $unitStatus->value : $unitStatus,
                'progress_percent' => round($unitPercent, 2),
                'is_locked' => $isUnitLocked,
                'completed_at' => optional($unitProgress?->completed_at)->toIso8601String(),
                'lessons' => $lessonsData,
            ];

            if ($unitStatus !== ProgressStatus::Completed) {
                $previousUnitsCompleted = false;
            }
        }

        $totalUnits = max(1, $courseModel->units->count());
        $derivedCoursePercent = round(($completedUnitsCount / $totalUnits) * 100, 2);

        $courseStatus = $courseProgress->status ?? (
            $courseModel->units->isEmpty() ? ProgressStatus::Completed :
            ($completedUnitsCount === $courseModel->units->count() ? ProgressStatus::Completed :
                ($completedUnitsCount > 0 ? ProgressStatus::InProgress : ProgressStatus::NotStarted))
        );

        $coursePercent = $courseProgress->progress_percent ?? $derivedCoursePercent;

        return [
            'course' => [
                'id' => $courseModel->id,
                'slug' => $courseModel->slug,
                'title' => $courseModel->title,
                'progression_mode' => $courseModel->progression_mode,
                'status' => $courseStatus instanceof ProgressStatus ? $courseStatus->value : $courseStatus,
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

        $progress->status = ProgressStatus::Completed;
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
            $status = ProgressStatus::Completed;
            $progressPercent = 100;
        } else {
            $completedLessons = LessonProgress::query()
                ->where('enrollment_id', $enrollment->id)
                ->whereIn('lesson_id', $lessonIds)
                ->where('status', ProgressStatus::Completed->value)
                ->count();

            $hasProgress = LessonProgress::query()
                ->where('enrollment_id', $enrollment->id)
                ->whereIn('lesson_id', $lessonIds)
                ->whereIn('status', [ProgressStatus::InProgress->value, ProgressStatus::Completed->value])
                ->exists();

            if ($forceComplete || $completedLessons === $totalLessons) {
                $status = ProgressStatus::Completed;
                $progressPercent = 100;
            } elseif ($hasProgress || $completedLessons > 0) {
                $status = ProgressStatus::InProgress;
                $progressPercent = round(($completedLessons / $totalLessons) * 100, 2);
            } else {
                $status = ProgressStatus::NotStarted;
                $progressPercent = 0;
            }
        }

        $progress = UnitProgress::query()
            ->firstOrNew([
                'enrollment_id' => $enrollment->id,
                'unit_id' => $unit->id,
            ]);

        $previousStatus = $progress->exists ? $progress->status : ProgressStatus::NotStarted;

        $progress->status = $status;
        $progress->progress_percent = $progressPercent;

        if ($status !== ProgressStatus::NotStarted && ! $progress->started_at) {
            $progress->started_at = Carbon::now();
        }

        if ($status === ProgressStatus::Completed && ! $progress->completed_at) {
            $progress->completed_at = Carbon::now();
        }

        if ($status !== ProgressStatus::Completed) {
            $progress->completed_at = null;
        }

        $progress->save();

        return [
            'status' => $status,
            'progress_percent' => $progressPercent,
            'just_completed' => $previousStatus !== ProgressStatus::Completed && $status === ProgressStatus::Completed,
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
            $status = ProgressStatus::Completed;
            $progressPercent = 100;
        } else {
            $completedUnits = UnitProgress::query()
                ->where('enrollment_id', $enrollment->id)
                ->whereIn('unit_id', $unitIds)
                ->where('status', ProgressStatus::Completed->value)
                ->count();

            $hasProgress = UnitProgress::query()
                ->where('enrollment_id', $enrollment->id)
                ->whereIn('unit_id', $unitIds)
                ->whereIn('status', [ProgressStatus::InProgress->value, ProgressStatus::Completed->value])
                ->exists();

            if ($completedUnits === $totalUnits) {
                $status = ProgressStatus::Completed;
                $progressPercent = 100;
            } elseif ($hasProgress || $completedUnits > 0) {
                $status = ProgressStatus::InProgress;
                $progressPercent = round(($completedUnits / $totalUnits) * 100, 2);
            } else {
                $status = ProgressStatus::NotStarted;
                $progressPercent = 0;
            }
        }

        $progress = CourseProgress::query()
            ->firstOrNew([
                'enrollment_id' => $enrollment->id,
            ]);

        $previousStatus = $progress->exists ? $progress->status : ProgressStatus::NotStarted;

        $progress->status = $status;
        $progress->progress_percent = $progressPercent;

        if ($status !== ProgressStatus::NotStarted && ! $progress->started_at) {
            $progress->started_at = Carbon::now();
        }

        if ($status === ProgressStatus::Completed && ! $progress->completed_at) {
            $progress->completed_at = Carbon::now();
        }

        if ($status !== ProgressStatus::Completed) {
            $progress->completed_at = null;
        }

        $progress->save();

        $courseJustCompleted = $previousStatus !== ProgressStatus::Completed && $status === ProgressStatus::Completed;

        // Update enrollment status and completed_at, but not progress_percent
        // Progress is now stored in course_progress table
        if ($status === ProgressStatus::Completed) {
            $enrollment->completed_at = $enrollment->completed_at ?? Carbon::now();
            $enrollment->status = EnrollmentStatus::Completed;
        } elseif ($enrollment->status === EnrollmentStatus::Completed && $status !== ProgressStatus::Completed) {
            $enrollment->status = EnrollmentStatus::Active;
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
