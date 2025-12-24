<?php

namespace Modules\Schemes\Services;

use App\Exceptions\BusinessException;
use App\Support\CodeGenerator;
use App\Support\Helpers\ArrayParser;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
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
    public function __construct(
        private readonly CourseRepositoryInterface $repository
    ) {}

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        $perPage = max(1, $perPage);
        $query = $this->buildQuery();

        return $query->paginate($perPage);
    }

    public function list(int $perPage = 15): LengthAwarePaginator
    {
        return $this->paginate($perPage);
    }

    public function listPublic(int $perPage = 15): LengthAwarePaginator
    {
        $perPage = max(1, $perPage);
        $query = $this->buildQuery()->where('status', 'published');

        return $query->paginate($perPage);
    }

    private function buildQuery(): QueryBuilder
    {
        $searchQuery = request('filter.search') ?? request('search');

        $builder = QueryBuilder::for(Course::class);

        if ($searchQuery && trim($searchQuery) !== '') {
            $ids = Course::search($searchQuery)->keys()->toArray();

            if (! empty($ids)) {
                $builder->whereIn('id', $ids);
            } else {
                $builder->whereRaw('1 = 0');
            }
        }

        $tagFilter = request('filter.tag');
        if ($tagFilter) {
            $tags = ArrayParser::parseFilter($tagFilter);

            if (! empty($tags)) {
                foreach ($tags as $tagValue) {
                    $value = trim((string) $tagValue);
                    if ($value === '') {
                        continue;
                    }

                    $slug = Str::slug($value);

                    $builder->whereHas('tags', function (Builder $tagQuery) use ($value, $slug) {
                        $tagQuery->where(function (Builder $inner) use ($value, $slug) {
                            $inner->where('slug', $slug)
                                ->orWhere('slug', $value)
                                ->orWhereRaw('LOWER(name) = ?', [mb_strtolower($value)]);
                        });
                    });
                }
            }
        }

        return $builder
            ->allowedFilters([
                AllowedFilter::exact('status'),
                AllowedFilter::exact('level_tag'),
                AllowedFilter::exact('type'),
                AllowedFilter::exact('category_id'),
            ])
            ->allowedIncludes(['tags', 'category', 'instructor', 'units'])
            ->allowedSorts(['id', 'code', 'title', 'created_at', 'updated_at', 'published_at'])
            ->defaultSort('title');
    }

    public function find(int $id): ?Course
    {
        return $this->repository->findById($id);
    }

    public function findOrFail(int $id): Course
    {
        $course = $this->repository->findById($id);
        if (! $course) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException;
        }

        return $course;
    }

    public function findBySlug(string $slug): ?Course
    {
        return $this->repository->findBySlug($slug);
    }

    public function create(CreateCourseDTO|array $data, ?\Modules\Auth\Models\User $actor = null): Course
    {
        $attributes = $data instanceof CreateCourseDTO ? $data->toArrayWithoutNull() : $data;

        if (! isset($attributes['code'])) {
            $attributes['code'] = $this->generateCourseCode();
        }

        // Set instructor_id from actor if provided and not already set
        if ($actor && ! isset($attributes['instructor_id'])) {
            $attributes['instructor_id'] = $actor->id;
        }

        $tags = $attributes['tags'] ?? null;
        $attributes = Arr::except($attributes, ['slug', 'tags']);

        $course = $this->repository->create($attributes);

        if ($tags) {
            $course->tags()->sync($tags);
        }

        return $course->fresh(['tags']);
    }

    public function update(int $id, UpdateCourseDTO|array $data): Course
    {
        $course = $this->repository->findByIdOrFail($id);
        $attributes = $data instanceof UpdateCourseDTO ? $data->toArrayWithoutNull() : $data;

        $tags = $attributes['tags'] ?? null;
        $attributes = Arr::except($attributes, ['slug', 'tags']);

        $this->repository->update($course, $attributes);

        if ($tags !== null) {
            $course->tags()->sync($tags);
        }

        return $course->fresh(['tags']);
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
                'Kursus tidak dapat dipublikasikan karena belum memiliki unit.',
                ['units' => ['Kursus harus memiliki minimal satu unit.']]
            );
        }

        $hasLessons = $course->units()->whereHas('lessons')->exists();
        if (! $hasLessons) {
            throw new BusinessException(
                'Kursus tidak dapat dipublikasikan karena belum memiliki lesson.',
                ['lessons' => ['Kursus harus memiliki minimal satu lesson.']]
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
