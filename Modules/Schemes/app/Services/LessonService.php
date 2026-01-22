<?php

declare(strict_types=1);

namespace Modules\Schemes\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Modules\Schemes\Contracts\Repositories\LessonRepositoryInterface;
use Modules\Schemes\Contracts\Services\LessonServiceInterface;
use Modules\Schemes\DTOs\CreateLessonDTO;
use Modules\Schemes\DTOs\UpdateLessonDTO;
use Modules\Schemes\Models\Course;
use Modules\Schemes\Models\Lesson;
use Modules\Schemes\Models\Unit;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class LessonService implements LessonServiceInterface
{
    use \App\Support\Traits\BuildsQueryBuilderRequest;

    public function __construct(
        private readonly LessonRepositoryInterface $repository
    ) {}

    public function validateHierarchy(int $courseId, int $unitId, ?int $lessonId = null): void
    {
        if ($lessonId) {
            $lesson = Lesson::with('unit')->findOrFail($lessonId);
            if ((int) $lesson->unit?->course_id !== $courseId || (int) $lesson->unit_id !== $unitId) {
                throw new \Illuminate\Database\Eloquent\ModelNotFoundException(__('messages.lessons.not_found'));
            }
        } else {
            $unit = Unit::findOrFail($unitId);
            if ((int) $unit->course_id !== $courseId) {
                throw new \Illuminate\Database\Eloquent\ModelNotFoundException(__('messages.units.not_in_course'));
            }
        }
    }

    public function paginate(int $unitId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $perPage = max(1, $perPage);

        $query = QueryBuilder::for(Lesson::class, $this->buildQueryBuilderRequest($filters))
            ->where('unit_id', $unitId)
            ->allowedFilters([
                AllowedFilter::exact('content_type'),
                AllowedFilter::exact('status'),
            ])
            ->allowedIncludes(['unit', 'blocks', 'assignments'])
            ->allowedSorts(['order', 'title', 'created_at'])
            ->defaultSort('order');

        return $query->paginate($perPage);
    }

    public function find(int $id): ?Lesson
    {
        return $this->repository->findById($id);
    }

    public function getLessonForUser(Lesson $lesson, Course $course, ?\Modules\Auth\Models\User $user): Lesson
    {
        if ($user?->hasRole('Student')) {
            $progression = app(ProgressionService::class);
            $enrollment = $progression->getEnrollmentForCourse($course->id, $user->id);
            if (!$enrollment || !$progression->canAccessLesson($lesson, $enrollment)) {
                throw new \App\Exceptions\BusinessException(__('messages.lessons.locked_prerequisite'), 403);
            }
        }

        return $lesson->load('blocks');
    }

    public function findOrFail(int $id): Lesson
    {
        return $this->repository->findByIdOrFail($id);
    }

    public function create(int $unitId, CreateLessonDTO|array $data): Lesson
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($unitId, $data) {
            $attributes = $data instanceof CreateLessonDTO ? $data->toArrayWithoutNull() : $data;
            $attributes['unit_id'] = $unitId;

            if (isset($attributes['order'])) {
                
                Lesson::where('unit_id', $unitId)
                    ->where('order', '>=', $attributes['order'])
                    ->increment('order');
            } else {
                $maxOrder = Lesson::where('unit_id', $unitId)->max('order');
                $attributes['order'] = $maxOrder ? $maxOrder + 1 : 1;
            }

            $attributes = Arr::except($attributes, ['slug']);

            return $this->repository->create($attributes);
        });
    }

    public function update(int $id, UpdateLessonDTO|array $data): Lesson
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($id, $data) {
            $lesson = $this->repository->findByIdOrFail($id);
            $attributes = $data instanceof UpdateLessonDTO ? $data->toArrayWithoutNull() : $data;

            
            if (isset($attributes['order']) && $attributes['order'] != $lesson->order) {
                $newOrder = $attributes['order'];
                $currentOrder = $lesson->order;
                $unitId = $lesson->unit_id;

                if ($newOrder < $currentOrder) {
                    
                    Lesson::where('unit_id', $unitId)
                        ->where('order', '>=', $newOrder)
                        ->where('order', '<', $currentOrder)
                        ->increment('order');
                } elseif ($newOrder > $currentOrder) {
                    
                    Lesson::where('unit_id', $unitId)
                        ->where('order', '>', $currentOrder)
                        ->where('order', '<=', $newOrder)
                        ->decrement('order');
                }
            }

            $attributes = Arr::except($attributes, ['slug']);

            return $this->repository->update($lesson, $attributes);
        });
    }

    public function delete(int $id): bool
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($id) {
            $lesson = $this->repository->findByIdOrFail($id);
            $unitId = $lesson->unit_id;
            $deletedOrder = $lesson->order;

            $deleted = $this->repository->delete($lesson);

            if ($deleted) {
                
                Lesson::where('unit_id', $unitId)
                    ->where('order', '>', $deletedOrder)
                    ->decrement('order');
            }

            return $deleted;
        });
    }

    public function publish(int $id): Lesson
    {
        $lesson = $this->repository->findByIdOrFail($id);
        $lesson->update(['status' => 'published']);

        
        $courseId = $lesson->unit->course_id;
        app(\Modules\Schemes\Services\SchemesCacheService::class)->invalidateCourse($courseId);

        return $lesson->fresh();
    }

    public function unpublish(int $id): Lesson
    {
        $lesson = $this->repository->findByIdOrFail($id);
        $lesson->update(['status' => 'draft']);

        
        $courseId = $lesson->unit->course_id;
        app(\Modules\Schemes\Services\SchemesCacheService::class)->invalidateCourse($courseId);

        return $lesson->fresh();
    }

    public function getRepository(): LessonRepositoryInterface
    {
        return $this->repository;
    }
}
