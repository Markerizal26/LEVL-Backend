<?php

declare(strict_types=1);

namespace Modules\Learning\Contracts\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Modules\Learning\Models\Assignment;
use Modules\Schemes\Models\Lesson;

interface AssignmentRepositoryInterface
{
    public function listForLesson(Lesson $lesson, array $filters = []): Collection;

    public function create(array $attributes): Assignment;

    public function update(Assignment $assignment, array $attributes): Assignment;

    public function delete(Assignment $assignment): bool;

    /**
     * Find an assignment by ID with eager loading and caching.
     * Requirements: 28.5, 28.7
     */
    public function find(int $id): ?Assignment;

    /**
     * Find an assignment with all questions for detailed view with caching.
     * Requirements: 28.5, 28.7
     */
    public function findWithQuestions(int $id): ?Assignment;

    /**
     * Find assignments by scope (polymorphic) with eager loading and caching.
     * Requirements: 28.5, 28.7, 28.10
     */
    public function findByScope(string $scopeType, int $scopeId): Collection;

    /**
     * Duplicate an assignment with all questions and settings.
     * Requirements: 25.1, 25.2, 25.4, 28.5
     */
    public function duplicate(int $id): Assignment;

    /**
     * Invalidate cache for a single assignment.
     * Requirements: 28.7
     */
    public function invalidateAssignmentCache(int $id): void;

    /**
     * Invalidate list cache for a specific scope.
     * Requirements: 28.7, 28.10
     */
    public function invalidateListCache(string $type, int $id, ?string $scopeType = null): void;
}
