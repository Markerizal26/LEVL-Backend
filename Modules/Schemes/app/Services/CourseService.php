<?php

namespace Modules\Schemes\Services;

use App\Contracts\EnrollmentKeyHasherInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Modules\Schemes\Contracts\Services\CourseServiceInterface;
use Modules\Schemes\Enums\CourseStatus;
use Modules\Schemes\Events\CourseCreated;
use Modules\Schemes\Events\CourseDeleted;
use Modules\Schemes\Events\CoursePublished;
use Modules\Schemes\Models\Course;
use Modules\Schemes\Repositories\CourseRepository;

class CourseService implements CourseServiceInterface
{
    public function __construct(
        private CourseRepository $repository,
        private TagService $tagService,
        private EnrollmentKeyHasherInterface $keyHasher
    ) {}

    public function listPublic(array $params): LengthAwarePaginator
    {
        $params['status'] = $params['status'] ?? CourseStatus::Published->value;

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
        $outcomes = $data['outcomes'] ?? [];
        $hasPrereqText = array_key_exists('prereq_text', $data);
        $prereqText = $hasPrereqText ? $data['prereq_text'] : null;
        unset($data['tags_list'], $data['outcomes'], $data['prereq_text']);

        if (! isset($data['tags_json'])) {
            $data['tags_json'] = [];
        }
        if (empty($data['slug'])) {
            $data['slug'] = $this->generateUniqueSlug($data['title'] ?? $data['code'] ?? Str::random(8));
        } else {
            $data['slug'] = $this->generateUniqueSlug($data['slug']);
        }

        if (empty($data['status'])) {
            $data['status'] = CourseStatus::Draft->value;
        }

        $enrollmentType = $data['enrollment_type'] ?? 'auto_accept';
        if ($enrollmentType !== 'key_based') {
            $data['enrollment_key_hash'] = null;
            unset($data['enrollment_key']);
        } elseif (isset($data['enrollment_key'])) {
            // Hash the enrollment key before storing
            $data['enrollment_key_hash'] = $this->keyHasher->hash($data['enrollment_key']);
            unset($data['enrollment_key']);
        }

        $course = $this->repository->create($data);

        $adminIds = [];
        if (! empty($data['course_admins']) && is_array($data['course_admins'])) {
            $adminIds = array_map('intval', $data['course_admins']);
        }
        if ($actor && ($actor->hasRole('Admin') || $actor->hasRole('Superadmin'))) {
            $adminIds[] = (int) $actor->id;
        }
        if (! empty($adminIds) && method_exists($course, 'admins')) {
            $course->admins()->syncWithoutDetaching(array_unique($adminIds));
        }

        $course->load('tags');

        $this->tagService->syncCourseTags($course, $tags);
        $this->syncCourseOutcomes($course, $outcomes);

        if ($hasPrereqText) {
            $course->prereq_text = $prereqText;
            $course->save();
        }

        $freshCourse = $course->fresh(['tags', 'admins', 'instructor', 'outcomes']);

        CourseCreated::dispatch($freshCourse);

        if (($freshCourse->status ?? null) === CourseStatus::Published) {
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
            $data['enrollment_key_hash'] = null;
            unset($data['enrollment_key']);
        } elseif (array_key_exists('enrollment_key', $data)) {
            // Hash the new enrollment key before storing
            if ($data['enrollment_key'] !== null) {
                $data['enrollment_key_hash'] = $this->keyHasher->hash($data['enrollment_key']);
            } else {
                $data['enrollment_key_hash'] = null;
            }
            unset($data['enrollment_key']);
        }
        // If enrollment_key is not in data, keep existing hash

        $outcomes = $data['outcomes'] ?? null;
        $hasPrereqText = array_key_exists('prereq_text', $data);
        $prereqText = $hasPrereqText ? $data['prereq_text'] : null;
        unset($data['outcomes'], $data['prereq_text']);

        $course = $this->repository->update($course, $data);

        if ($tags !== null) {
            $this->tagService->syncCourseTags($course, $tags);
        }

        if ($outcomes !== null) {
            $this->syncCourseOutcomes($course, $outcomes);
        }

        if ($hasPrereqText) {
            $course->prereq_text = $prereqText;
            $course->save();
        }

        $course->load('tags');

        if (array_key_exists('status', $data) && $data['status'] === CourseStatus::Published->value) {
            CoursePublished::dispatch($course);
        }

        return $course->fresh(['tags', 'admins', 'instructor', 'outcomes']);
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
            'status' => CourseStatus::Published->value,
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
            'status' => CourseStatus::Draft->value,
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

    private function syncCourseOutcomes(Course $course, array $outcomes): void
    {
        $course->outcomes()->delete();

        foreach ($outcomes as $index => $outcome) {
            if (empty($outcome)) {
                continue;
            }

            $course->outcomes()->create([
                'outcome_text' => is_string($outcome) ? $outcome : (is_array($outcome) ? ($outcome['text'] ?? $outcome['outcome_text'] ?? json_encode($outcome)) : (string) $outcome),
                'order' => is_array($outcome) ? ($outcome['order'] ?? $index) : $index,
            ]);
        }
    }

    /**
     * Verify an enrollment key against a course's stored hash.
     *
     * @param  Course  $course  The course to verify against
     * @param  string  $plainKey  The plain text enrollment key to verify
     * @return bool True if the key is valid, false otherwise
     */
    public function verifyEnrollmentKey(Course $course, string $plainKey): bool
    {
        if (empty($course->enrollment_key_hash)) {
            return false;
        }

        return $this->keyHasher->verify($plainKey, $course->enrollment_key_hash);
    }

    /**
     * Generate a new enrollment key for a course.
     *
     * @param  int  $length  The length of the key to generate (default: 12)
     * @return string The generated plain text enrollment key
     */
    public function generateEnrollmentKey(int $length = 12): string
    {
        return $this->keyHasher->generate($length);
    }

    /**
     * Check if a course has an enrollment key set.
     *
     * @param  Course  $course  The course to check
     * @return bool True if the course has an enrollment key hash, false otherwise
     */
    public function hasEnrollmentKey(Course $course): bool
    {
        return ! empty($course->enrollment_key_hash);
    }
}
