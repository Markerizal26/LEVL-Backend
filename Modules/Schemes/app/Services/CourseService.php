<?php

namespace Modules\Schemes\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Modules\Schemes\Entities\Course;
use Modules\Schemes\Events\CourseCreated;
use Modules\Schemes\Events\CourseDeleted;
use Modules\Schemes\Events\CoursePublished;
use Modules\Schemes\Repositories\CourseRepository;

class CourseService
{
    public function __construct(private CourseRepository $repository) {}

    public function listPublic(array $params): LengthAwarePaginator
    {
        $params['visibility'] = 'public';
        $params['status'] = $params['status'] ?? 'published';

        return Cache::remember('courses_public', now()->addMinutes(10), function () use ($params) {

            $perPage = isset($params['per_page']) ? max(1, (int) $params['per_page']) : 15;

            return $this->repository->paginate($params, $perPage);
        });
    }

    public function list(array $params): LengthAwarePaginator
    {
        $perPage = isset($params['per_page']) ? max(1, (int) $params['per_page']) : 15;

        return $this->repository->paginate($params, $perPage);
    }

    public function create(array $data): Course
    {

        if (empty($data['slug'])) {
            $data['slug'] = $this->generateUniqueSlug($data['title'] ?? $data['code'] ?? Str::random(8));
        } else {
            $data['slug'] = $this->generateUniqueSlug($data['slug']);
        }

        $course = $this->repository->create($data);

        Cache::forget('courses_public');

        CourseCreated::dispatch($course);

        if (($course->status ?? null) === 'published') {
            CoursePublished::dispatch($course);
        }

        return $course;
    }

    public function update(int $id, array $data): ?Course
    {
        $course = $this->repository->findById($id);
        if (! $course) {
            return null;
        }

        if (! empty($data['slug'])) {
            $data['slug'] = $this->generateUniqueSlug($data['slug'], $course->id);
        }

        $course = $this->repository->update($course, $data);

        if (array_key_exists('status', $data) && $data['status'] === 'published') {
            CoursePublished::dispatch($course);
        }

        Cache::forget('courses_public');

        return $course;
    }

    public function delete(int $id): bool
    {
        $course = $this->repository->findById($id);
        if (! $course) {
            return false;
        }

        $this->repository->delete($course);

        Cache::forget('courses_public');

        CourseDeleted::dispatch($course);

        return true;
    }

    private function generateUniqueSlug(string $source, ?int $ignoreId = null): string
    {
        $base = Str::slug($source);
        $slug = $base !== '' ? $base : Str::random(8);
        $suffix = 0;
        do {
            $candidate = $suffix > 0 ? $slug.'-'.$suffix : $slug;
            $existsQuery = Course::query()->where('slug', $candidate);
            if ($ignoreId) {
                $existsQuery->where('id', '!=', $ignoreId);
            }
            $exists = $existsQuery->exists();
            if (! $exists) {
                return $candidate;
            }
            $suffix++;
        } while (true);
    }
}
