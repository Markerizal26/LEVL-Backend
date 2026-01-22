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

        protected const DEFAULT_EAGER_LOAD = [
        'student:id,name,email',
        'grantor:id,name,email',
        'assignment:id,title',
    ];

        public function create(array $attributes): Override
    {
        $override = Override::create($attributes);

        return $override->load(self::DEFAULT_EAGER_LOAD);
    }

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

        public function findActiveOverridesForStudent(int $assignmentId, int $studentId): Collection
    {
        return Override::query()
            ->forAssignment($assignmentId)
            ->forStudent($studentId)
            ->active()
            ->with(self::DEFAULT_EAGER_LOAD)
            ->get();
    }

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

        public function hasActiveOverride(int $assignmentId, int $studentId, OverrideType $type): bool
    {
        return Override::query()
            ->forAssignment($assignmentId)
            ->forStudent($studentId)
            ->ofType($type)
            ->active()
            ->exists();
    }

        public function findByStudent(int $studentId): Collection
    {
        return Override::query()
            ->forStudent($studentId)
            ->with(self::DEFAULT_EAGER_LOAD)
            ->orderByDesc('granted_at')
            ->get();
    }

        public function findByGrantor(int $grantorId): Collection
    {
        return Override::query()
            ->where('grantor_id', $grantorId)
            ->with(self::DEFAULT_EAGER_LOAD)
            ->orderByDesc('granted_at')
            ->get();
    }

        public function findAllActive(): Collection
    {
        return Override::query()
            ->active()
            ->with(self::DEFAULT_EAGER_LOAD)
            ->orderByDesc('granted_at')
            ->get();
    }

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
