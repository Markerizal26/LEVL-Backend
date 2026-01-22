<?php

declare(strict_types=1);

namespace Modules\Learning\Contracts\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Modules\Learning\Enums\OverrideType;
use Modules\Learning\Models\Override;

interface OverrideRepositoryInterface
{
    public function create(array $attributes): Override;

    public function findActiveOverride(int $assignmentId, int $studentId, OverrideType $type): ?Override;

    public function findActiveOverridesForStudent(int $assignmentId, int $studentId): Collection;

    public function findByAssignment(int $assignmentId): Collection;

    public function hasActiveOverride(int $assignmentId, int $studentId, OverrideType $type): bool;
}
