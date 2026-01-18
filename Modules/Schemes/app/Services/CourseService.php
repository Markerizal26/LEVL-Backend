<?php

declare(strict_types=1);

namespace Modules\Schemes\Services;

use App\Exceptions\BusinessException;
use App\Exceptions\DuplicateResourceException;
use App\Support\CodeGenerator;
use App\Support\Helpers\ArrayParser;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Schemes\Contracts\Repositories\CourseRepositoryInterface;
use Modules\Schemes\Contracts\Services\CourseServiceInterface;
use Modules\Schemes\DTOs\CreateCourseDTO;
use Modules\Schemes\DTOs\UpdateCourseDTO;
use Modules\Schemes\Models\Course;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class CourseService implements CourseServiceInterface
{
    use \App\Support\Traits\BuildsQueryBuilderRequest;

    public function __construct(
        private readonly CourseRepositoryInterface $repository,
        private readonly SchemesCacheService $cacheService
    ) {}

    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $perPage = max(1, $perPage);
        $query = $this->buildQuery($filters);

        return $query->paginate($perPage);
    }

    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        if (data_get($filters, 'status') === 'published') {
            return $this->listPublic($perPage, $filters);
        }

        return $this->paginate($filters, $perPage);
    }

    public function listPublic(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $perPage = max(1, $perPage);
        $page = request()->get('page', 1);

        // Try to get from cache first
        $cached = $this->cacheService->getPublicCourses($page, $perPage, $filters);
        if ($cached) {
            return $cached;
        }

        $query = $this->buildQuery($filters)->where('status', 'published');

        return $query->paginate($perPage);
    }

    private function buildQuery(array $filters = []): QueryBuilder
    {
        $searchQuery = data_get($filters, 'search');
        $builder = QueryBuilder::for(
            Course::with(['instructor', 'admins']),
            $this->buildQueryBuilderRequest($filters)
        );

        if ($searchQuery && trim((string) $searchQuery) !== '') {
            $ids = Course::search($searchQuery)->keys()->toArray();
            $builder->whereIn('id', $ids ?: [0]);
        }

        if ($tagFilter = data_get($filters, 'tag')) {
            $tags = ArrayParser::parseFilter($tagFilter);
            foreach ($tags as $tagValue) {
                $value = trim((string) $tagValue);
                if ($value === '') continue;
                $slug = Str::slug($value);
                $builder->whereHas('tags', fn ($q) => $q->where(fn ($iq) => $iq->where('slug', $slug)->orWhere('slug', $value)->orWhereRaw('LOWER(name) = ?', [mb_strtolower($value)])));
            }
        }

        return $builder
            ->allowedFilters([
                AllowedFilter::exact('status'),
                AllowedFilter::exact('level_tag'),
                AllowedFilter::exact('type'),
                AllowedFilter::exact('category_id'),
            ])
            ->allowedIncludes(['tags', 'category', 'instructor', 'units', 'admins'])
            ->allowedSorts(['id', 'code', 'title', 'created_at', 'updated_at', 'published_at'])
            ->defaultSort('title');
    }

    public function find(int $id): ?Course
    {
        return $this->cacheService->getCourse($id);
    }

    public function findOrFail(int $id): Course
    {
        $course = $this->cacheService->getCourse($id);
        if (! $course) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException;
        }

        return $course;
    }

    public function findBySlug(string $slug): ?Course
    {
        return $this->cacheService->getCourseBySlug($slug);
    }

    public function create(CreateCourseDTO|array $data, ?\Modules\Auth\Models\User $actor = null, array $files = []): Course
    {
        try {
            return DB::transaction(function () use ($data, $actor, $files) {
                $attributes = $data instanceof CreateCourseDTO ? $data->toArrayWithoutNull() : $data;

                if (! isset($attributes['code'])) {
                    $attributes['code'] = $this->generateCourseCode();
                }

                if (! isset($attributes['instructor_id'])) {
                    $attributes['instructor_id'] = null;
                }

                $tags = $attributes['tags'] ?? null;
                $attributes = Arr::except($attributes, ['slug', 'tags']);

                $course = $this->repository->create($attributes);

                if ($tags) {
                    $course->tags()->sync($tags);
                }

                if ($actor && $actor->hasRole(['Superadmin', 'Admin'])) {
                    $course->admins()->syncWithoutDetaching([$actor->id]);
                }

                $this->handleMedia($course, $files);

                // Invalidate listing cache
                $this->cacheService->invalidateListings();

                return $course->fresh(['tags']);
            });
        } catch (QueryException $e) {
            throw new DuplicateResourceException($this->parseCourseDuplicates($e));
        }
    }

    public function update(int $id, UpdateCourseDTO|array $data, array $files = []): Course
    {
        try {
            return DB::transaction(function () use ($id, $data, $files) {
                $course = $this->repository->findByIdOrFail($id);
                $attributes = $data instanceof UpdateCourseDTO ? $data->toArrayWithoutNull() : $data;

                $tags = $attributes['tags'] ?? null;
                $attributes = Arr::except($attributes, ['slug', 'tags']);

                $this->repository->update($course, $attributes);

                if ($tags !== null) {
                    $course->tags()->sync($tags);
                }

                $this->handleMedia($course, $files);

                // Invalidate specific course cache + listings
                $this->cacheService->invalidateCourse($course->id, $course->slug);

                return $course->fresh(['tags']);
            });
        } catch (QueryException $e) {
            throw new DuplicateResourceException($this->parseCourseDuplicates($e));
        }
    }

    public function delete(int $id): bool
    {
        $course = $this->findOrFail($id);

        return $this->repository->delete($course);
    }

    /**
     * @throws BusinessException
     */
    public function publish(int $id): Course
    {
        $course = $this->findOrFail($id);

        if ($course->units()->count() === 0) {
            throw new BusinessException(
                __('messages.courses.cannot_publish_without_units'),
                ['units' => [__('messages.courses.must_have_one_unit')]]
            );
        }

        $hasLessons = $course->units()->whereHas('lessons')->exists();
        if (! $hasLessons) {
            throw new BusinessException(
                __('messages.courses.cannot_publish_without_lessons'),
                ['lessons' => [__('messages.courses.must_have_one_lesson')]]
            );
        }

        $this->repository->update($course, [
            'status' => 'published',
            'published_at' => now(),
        ]);

        return $course->fresh();
    }

    public function unpublish(int $id): Course
    {
        $course = $this->repository->findByIdOrFail($id);

        $this->repository->update($course, [
            'status' => 'draft',
            'published_at' => null,
        ]);

        return $course->fresh();
    }

    public function updateEnrollmentSettings(int $id, array $data): array
    {
        $plainKey = null;

        if ($data['enrollment_type'] === 'key_based' && empty($data['enrollment_key'])) {
            $plainKey = $this->generateEnrollmentKey(12);
            $data['enrollment_key'] = $plainKey;
        } elseif ($data['enrollment_type'] === 'key_based' && ! empty($data['enrollment_key'])) {
            $plainKey = $data['enrollment_key'];
        }

        if ($data['enrollment_type'] !== 'key_based') {
            $data['enrollment_key'] = null;
        }

        $updated = $this->update($id, $data);

        return [
            'course' => $updated,
            'enrollment_key' => $plainKey,
        ];
    }

    private function handleMedia(Course $course, array $files): void
    {
        foreach (['thumbnail', 'banner'] as $collection) {
            if (! empty($files[$collection])) {
                $course->clearMediaCollection($collection);
                $course->addMedia($files[$collection])->toMediaCollection($collection);
            }
        }
    }

    private function parseCourseDuplicates(QueryException $e): array
    {
        $message = $e->getMessage();
        $errors = [];

        if (preg_match('/courses?_code_unique/i', $message)) {
            $errors['code'] = [__('messages.courses.code_exists')];
        }
        
        if (preg_match('/courses?_slug_unique/i', $message)) {
            $errors['slug'] = [__('messages.courses.slug_exists')];
        }

        if (empty($errors)) {
            // Attempt to extract column name from common DB error formats
            if (preg_match('/Key \(([^)]+)\)=\([^)]+\) already exists/i', $message, $matches)) {
                $column = $matches[1];
                $errors[$column] = [__('messages.courses.duplicate_data_field', ['field' => $column])];
            }
        }

        return $errors ?: ['general' => [__('messages.courses.duplicate_data')]];
    }

    private function generateCourseCode(): string
    {
        return CodeGenerator::generate('CRS-', 6, Course::class);
    }

    public function uploadThumbnail(int $id, \Illuminate\Http\UploadedFile $file): Course
    {
        $course = $this->findOrFail($id);

        $course->clearMediaCollection('thumbnail');
        $course->addMedia($file)->toMediaCollection('thumbnail');

        return $course->fresh();
    }

    public function uploadBanner(int $id, \Illuminate\Http\UploadedFile $file): Course
    {
        $course = $this->findOrFail($id);

        $course->clearMediaCollection('banner');
        $course->addMedia($file)->toMediaCollection('banner');

        return $course->fresh();
    }

    public function deleteThumbnail(int $id): Course
    {
        $course = $this->findOrFail($id);
        $course->clearMediaCollection('thumbnail');

        return $course->fresh();
    }

    /**
     * Delete the course banner.
     */
    public function deleteBanner(int $id): Course
    {
        $course = $this->findOrFail($id);
        $course->clearMediaCollection('banner');

        return $course->fresh();
    }

    /**
     * Verify an enrollment key against a course's stored hash.
     */
    public function verifyEnrollmentKey(Course $course, string $plainKey): bool
    {
        if (empty($course->enrollment_key_hash)) {
            return false;
        }

        // Get the hasher from container
        $hasher = app(\App\Contracts\EnrollmentKeyHasherInterface::class);

        return $hasher->verify($plainKey, $course->enrollment_key_hash);
    }

    /**
     * Generate a new enrollment key.
     */
    public function generateEnrollmentKey(int $length = 12): string
    {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $key = '';

        for ($i = 0; $i < $length; $i++) {
            $key .= $characters[random_int(0, strlen($characters) - 1)];
        }

        return $key;
    }

    /**
     * Check if a course has an enrollment key set.
     */
    public function hasEnrollmentKey(Course $course): bool
    {
        return ! empty($course->enrollment_key_hash);
    }
}
