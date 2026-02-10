<?php

declare(strict_types=1);

namespace Modules\Learning\Services\Support;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Learning\Enums\AssignmentStatus;
use Modules\Learning\Models\Assignment;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class AssignmentFinder
{
    use \App\Support\Traits\BuildsQueryBuilderRequest;

    public function resolveCourseFromScope(string $assignableType, int $assignableId): ?\Modules\Schemes\Models\Course
    {
        return match ($assignableType) {
            'Modules\\Schemes\\Models\\Course' => \Modules\Schemes\Models\Course::find($assignableId),
            'Modules\\Schemes\\Models\\Unit' => \Modules\Schemes\Models\Unit::find($assignableId)?->course,
            'Modules\\Schemes\\Models\\Lesson' => \Modules\Schemes\Models\Lesson::find($assignableId)?->unit?->course,
            default => null,
        };
    }

    public function resolveCourseFromScopeOrFail(?array $scope): \Modules\Schemes\Models\Course
    {
        if (! $scope) {
            throw new \InvalidArgumentException(__('messages.assignments.invalid_scope'));
        }

        $course = $this->resolveCourseFromScope(
            $scope['assignable_type'],
            $scope['assignable_id']
        );

        if (! $course) {
            throw new \InvalidArgumentException(__('messages.assignments.invalid_scope'));
        }

        return $course;
    }

    public function listForIndex(\Modules\Schemes\Models\Course $course, array $filters = []): LengthAwarePaginator
    {
        $unitSlug = data_get($filters, 'unit_slug');
        $lessonSlug = data_get($filters, 'lesson_slug');

        if ($lessonSlug) {
            $lesson = \Modules\Schemes\Models\Lesson::where('slug', $lessonSlug)->first();

            if (! $lesson) {
                throw new \Illuminate\Database\Eloquent\ModelNotFoundException(__('messages.lessons.not_found'));
            }

            $lesson->loadMissing('unit');
            if ($lesson->unit && $lesson->unit->course_id !== $course->id) {
                 throw new \InvalidArgumentException(__('messages.assignments.invalid_scope_hierarchy'));
            }

            return $this->listByLessonForIndex($lesson, $filters);
        }

        if ($unitSlug) {
             $unit = \Modules\Schemes\Models\Unit::where('slug', $unitSlug)->first();

             if (! $unit) {
                 throw new \Illuminate\Database\Eloquent\ModelNotFoundException(__('messages.units.not_found'));
             }

             if ($unit->course_id !== $course->id) {
                  throw new \InvalidArgumentException(__('messages.assignments.invalid_scope_hierarchy'));
             }

             return $this->listByUnitForIndex($unit, $filters);
        }

        return $this->listByCourseForIndex($course, $filters);
    }

    public function listByLesson(\Modules\Schemes\Models\Lesson $lesson, array $filters = []): LengthAwarePaginator
    {
        $perPage = max(1, (int) data_get($filters, 'per_page', 15));

        return $this->buildQuery($filters)
            ->forLesson($lesson->id)
            ->paginate($perPage);
    }

    public function listByLessonForIndex(\Modules\Schemes\Models\Lesson $lesson, array $filters = []): LengthAwarePaginator
    {
        $perPage = max(1, (int) data_get($filters, 'per_page', 15));

        return $this->buildQueryForIndex($filters)
            ->forLesson($lesson->id)
            ->paginate($perPage);
    }

    public function listByUnit(\Modules\Schemes\Models\Unit $unit, array $filters = []): LengthAwarePaginator
    {
        $perPage = max(1, (int) data_get($filters, 'per_page', 15));

        return $this->buildQuery($filters)
            ->forUnit($unit->id)
            ->paginate($perPage);
    }

    public function listByUnitForIndex(\Modules\Schemes\Models\Unit $unit, array $filters = []): LengthAwarePaginator
    {
        $perPage = max(1, (int) data_get($filters, 'per_page', 15));

        return $this->buildQueryForIndex($filters)
            ->forUnit($unit->id)
            ->paginate($perPage);
    }

    public function listByCourse(\Modules\Schemes\Models\Course $course, array $filters = []): LengthAwarePaginator
    {
        $perPage = max(1, (int) data_get($filters, 'per_page', 15));

        return $this->buildQuery($filters)
            ->forCourse($course->id)
            ->paginate($perPage);
    }

    public function listByCourseForIndex(\Modules\Schemes\Models\Course $course, array $filters = []): LengthAwarePaginator
    {
        $perPage = max(1, (int) data_get($filters, 'per_page', 15));

        return $this->buildQueryForIndex($filters)
            ->forCourse($course->id)
            ->paginate($perPage);
    }

    public function listIncomplete(\Modules\Schemes\Models\Course $course, int $studentId, array $filters = []): LengthAwarePaginator
    {
        $perPage = max(1, (int) data_get($filters, 'per_page', 15));

        $query = $this->buildQuery($filters)
            ->forCourse($course->id)
            ->where('assignments.status', AssignmentStatus::Published->value);

        // Left join with submissions to find incomplete assignments
        $query->leftJoin('submissions', function ($join) use ($studentId) {
            $join->on('assignments.id', '=', 'submissions.assignment_id')
                 ->where('submissions.user_id', $studentId)
                 ->whereIn('submissions.status', ['submitted', 'graded']);
        })
        ->whereNull('submissions.id')
        ->select('assignments.*')
        ->distinct();

        return $query->paginate($perPage);
    }

    private function buildQuery(array $payload = []): QueryBuilder
    {
        $searchQuery = data_get($payload, 'search');
        $filters = data_get($payload, 'filter', []);

        $builder = QueryBuilder::for(
            Assignment::with(['creator', 'lesson.unit.course', 'assignable']),
            $this->buildQueryBuilderRequest($filters)
        );

        if ($searchQuery && trim((string) $searchQuery) !== '') {
            $builder->search($searchQuery);
        }

        return $builder
            ->allowedFilters([
                AllowedFilter::exact('status'),
                AllowedFilter::exact('submission_type'),
            ])
            ->allowedIncludes(['questions', 'prerequisites', 'overrides', 'creator', 'lesson', 'assignable'])
            ->allowedSorts(['id', 'title', 'created_at', 'updated_at', 'deadline_at', 'available_from'])
            ->defaultSort('-created_at');
    }

    private function buildQueryForIndex(array $payload = []): QueryBuilder
    {
        $searchQuery = data_get($payload, 'search');
        $filters = data_get($payload, 'filter', []);

        $builder = QueryBuilder::for(
            Assignment::with(['creator:id,name,email', 'lesson:id,unit_id,title,slug', 'lesson.unit:id,course_id,title,slug', 'assignable:id,title,slug']),
            $this->buildQueryBuilderRequest($filters)
        );

        if ($searchQuery && trim((string) $searchQuery) !== '') {
            $builder->search($searchQuery);
        }

        return $builder
            ->allowedFilters([
                AllowedFilter::exact('status'),
                AllowedFilter::exact('submission_type'),
            ])
            ->allowedSorts(['id', 'title', 'created_at', 'updated_at', 'deadline_at', 'available_from'])
            ->defaultSort('-created_at');
    }
}
