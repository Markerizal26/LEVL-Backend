<?php

declare(strict_types=1);

namespace Modules\Learning\Repositories;

use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Modules\Learning\Contracts\Repositories\OverrideRepositoryInterface;
use Modules\Learning\Enums\OverrideType;
use Modules\Learning\Models\Override;

class OverrideRepository extends BaseRepository implements OverrideRepositoryInterface
{
    protected function model(): string
    {
        return Override::class;
    }

    /**
     * Default eager loading relationships for overrides.
     * Prevents N+1 query problems when loading overrides with related data.
     * Requirements: 28.5
     */
    protected const DEFAULT_EAGER_LOAD = [
        'student:id,name,email',
        'grantor:id,name,email',
        'assignment:id,title',
    ];

    /**
     * Create a new override with eager loading.
     */
    public function create(array $attributes): Override
    {
        $override = Override::create($attributes);

        return $override->load(self::DEFAULT_EAGER_LOAD);
    }

    /**
     * Find an active override for a student on an assignment by type with eager loading.
     * Requirements: 28.5
     */
    public function findActiveOverride(int $assignmentId, int $studentId, OverrideType $type): ?Override
    {
        return Override::query()
            ->forAssignment($assignmentId)
            ->forStudent($studentId)
            ->ofType($type)
            ->active()
            ->with(self::DEFAULT_EAGER_LOAD)
            ->first();
    }

    /**
     * Find all active overrides for a student on an assignment with eager loading.
     * Requirements: 28.5
     */
    public function findActiveOverridesForStudent(int $assignmentId, int $studentId): Collection
    {
        return Override::query()
            ->forAssignment($assignmentId)
            ->forStudent($studentId)
            ->active()
            ->with(self::DEFAULT_EAGER_LOAD)
            ->get();
    }

    /**
     * Find all overrides for an assignment with eager loading.
     * Requirements: 28.5
     */
    public function findByAssignment(int $assignmentId): Collection
    {
        return Override::query()
            ->forAssignment($assignmentId)
            ->with(self::DEFAULT_EAGER_LOAD)
            ->orderByDesc('granted_at')
            ->get();
    }

    public function getOverridesForAssignment(int $assignmentId): Collection
    {
        return Override::query()
            ->where('assignment_id', $assignmentId)
            ->with(['student', 'grantor'])
            ->orderByDesc('granted_at')
            ->get();
    }

    /**
     * Check if a student has an active override of a specific type.
     */
    public function hasActiveOverride(int $assignmentId, int $studentId, OverrideType $type): bool
    {
        return Override::query()
            ->forAssignment($assignmentId)
            ->forStudent($studentId)
            ->ofType($type)
            ->active()
            ->exists();
    }

    /**
     * Find all overrides for a student with eager loading.
     * Requirements: 28.5
     */
    public function findByStudent(int $studentId): Collection
    {
        return Override::query()
            ->forStudent($studentId)
            ->with(self::DEFAULT_EAGER_LOAD)
            ->orderByDesc('granted_at')
            ->get();
    }

    /**
     * Find all overrides granted by an instructor with eager loading.
     * Requirements: 28.5
     */
    public function findByGrantor(int $grantorId): Collection
    {
        return Override::query()
            ->where('grantor_id', $grantorId)
            ->with(self::DEFAULT_EAGER_LOAD)
            ->orderByDesc('granted_at')
            ->get();
    }

    /**
     * Find all active overrides with eager loading.
     * Requirements: 28.5
     */
    public function findAllActive(): Collection
    {
        return Override::query()
            ->active()
            ->with(self::DEFAULT_EAGER_LOAD)
            ->orderByDesc('granted_at')
            ->get();
    }

    /**
     * Find expired overrides with eager loading.
     * Requirements: 28.5
     */
    public function findExpired(): Collection
    {
        return Override::query()
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->with(self::DEFAULT_EAGER_LOAD)
            ->orderByDesc('expires_at')
            ->get();
    }
}
