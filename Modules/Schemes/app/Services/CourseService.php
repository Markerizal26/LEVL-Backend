<?php

namespace Modules\Schemes\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Modules\Schemes\Events\CourseCreated;
use Modules\Schemes\Events\CourseDeleted;
use Modules\Schemes\Events\CoursePublished;
use Modules\Schemes\Models\Course;
use Modules\Schemes\Repositories\CourseRepository;

class CourseService
{
    public function __construct(
        private CourseRepository $repository,
        private TagService $tagService
    ) {}

    public function listPublic(array $params): LengthAwarePaginator
    {
        $params['status'] = $params['status'] ?? 'published';

        $perPage = isset($params['per_page']) ? max(1, (int) $params['per_page']) : 15;

        return $this->repository->paginate($params, $perPage);
    }

    public function list(array $params): LengthAwarePaginator
    {
        $perPage = isset($params['per_page']) ? max(1, (int) $params['per_page']) : 15;

        return $this->repository->paginate($params, $perPage);
    }

    public function create(array $data, ?\Modules\Auth\Models\User $actor = null): Course
    {
        $tags = $data['tags_list'] ?? [];
        unset($data['tags_list']);

        if (! isset($data['tags_json'])) {
            $data['tags_json'] = [];
        }
        if (empty($data['slug'])) {
            $data['slug'] = $this->generateUniqueSlug($data['title'] ?? $data['code'] ?? Str::random(8));
        } else {
            $data['slug'] = $this->generateUniqueSlug($data['slug']);
        }

        if (empty($data['status'])) {
            $data['status'] = 'draft';
        }

        $enrollmentType = $data['enrollment_type'] ?? 'auto_accept';
        if ($enrollmentType !== 'key_based') {
            $data['enrollment_key'] = null;
        }

        $course = $this->repository->create($data);

        $adminIds = [];
        if (! empty($data['course_admins']) && is_array($data['course_admins'])) {
            $adminIds = array_map('intval', $data['course_admins']);
        }
        if ($actor && ($actor->hasRole('admin') || $actor->hasRole('superadmin'))) {
            $adminIds[] = (int) $actor->id;
        }
        if (! empty($adminIds) && method_exists($course, 'admins')) {
            $course->admins()->syncWithoutDetaching(array_unique($adminIds));
        }

        $course->load('tags');

        $this->tagService->syncCourseTags($course, $tags);

        $freshCourse = $course->fresh(['tags', 'admins', 'instructor']);

        CourseCreated::dispatch($freshCourse);

        if (($freshCourse->status ?? null) === 'published') {
            CoursePublished::dispatch($freshCourse);
        }

        return $freshCourse;
    }

    public function update(int $id, array $data): ?Course
    {
        $course = $this->repository->findById($id);
        if (! $course) {
            return null;
        }

        $tags = $data['tags_list'] ?? null;
        unset($data['tags_list']);

        if (! empty($data['slug'])) {
            $data['slug'] = $this->generateUniqueSlug($data['slug'], $course->id);
        }

        $enrollmentType = $data['enrollment_type'] ?? $course->enrollment_type;
        if ($enrollmentType !== 'key_based') {
            $data['enrollment_key'] = null;
        } elseif (! array_key_exists('enrollment_key', $data)) {
            // keep existing key
            unset($data['enrollment_key']);
        }

        $course = $this->repository->update($course, $data);

        if ($tags !== null) {
            $this->tagService->syncCourseTags($course, $tags);
        }

        $course->load('tags');

        if (array_key_exists('status', $data) && $data['status'] === 'published') {
            CoursePublished::dispatch($course);
        }

        return $course->fresh(['tags', 'admins', 'instructor']);
    }

    public function delete(int $id): bool
    {
        $course = $this->repository->findById($id);
        if (! $course) {
            return false;
        }

        $this->repository->delete($course);

        CourseDeleted::dispatch($course);

        return true;
    }

    public function publish(int $id): ?Course
    {
        $course = $this->repository->findById($id);
        if (! $course) {
            return null;
        }

        $course->update([
            'status' => 'published',
            'published_at' => now(),
        ]);

        CoursePublished::dispatch($course->fresh());

        return $course->fresh();
    }

    public function unpublish(int $id): ?Course
    {
        $course = $this->repository->findById($id);
        if (! $course) {
            return null;
        }

        $course->update([
            'status' => 'draft',
            'published_at' => null,
        ]);

        return $course->fresh();
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
