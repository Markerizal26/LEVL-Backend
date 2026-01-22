<?php

declare(strict_types=1);

namespace Modules\Schemes\Contracts\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Auth\Models\User;
use Modules\Schemes\Contracts\Repositories\LessonRepositoryInterface;
use Modules\Schemes\DTOs\CreateLessonDTO;
use Modules\Schemes\DTOs\UpdateLessonDTO;
use Modules\Schemes\Models\Course;
use Modules\Schemes\Models\Lesson;

interface LessonServiceInterface
{
    public function validateHierarchy(int $courseId, int $unitId, ?int $lessonId = null): void;

    public function paginate(int $unitId, array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function find(int $id): ?Lesson;

    public function getLessonForUser(Lesson $lesson, Course $course, ?User $user): Lesson;

    public function findOrFail(int $id): Lesson;

    public function create(int $unitId, CreateLessonDTO|array $data): Lesson;

    public function update(int $id, UpdateLessonDTO|array $data): Lesson;

    public function delete(int $id): bool;

    public function publish(int $id): Lesson;

    public function unpublish(int $id): Lesson;

    public function getRepository(): LessonRepositoryInterface;
}
