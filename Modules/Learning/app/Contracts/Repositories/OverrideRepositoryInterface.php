<?php

declare(strict_types=1);

namespace Modules\Learning\Contracts\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Modules\Learning\Enums\OverrideType;
use Modules\Learning\Models\Override;

interface OverrideRepositoryInterface
{
    /**
     * Create a new override.
     */
    public function create(array $attributes): Override;

    /**
     * Find an active override for a student on an assignment by type.
     */
    public function findActiveOverride(int $assignmentId, int $studentId, OverrideType $type): ?Override;

    /**
     * Find all active overrides for a student on an assignment.
     */
    public function findActiveOverridesForStudent(int $assignmentId, int $studentId): Collection;

    /**
     * Find all overrides for an assignment.
     */
    public function findByAssignment(int $assignmentId): Collection;

    /**
     * Check if a student has an active override of a specific type.
     */
    public function hasActiveOverride(int $assignmentId, int $studentId, OverrideType $type): bool;
}
