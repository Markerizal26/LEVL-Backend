<?php

declare(strict_types=1);

namespace Modules\Common\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Auth\Models\User;
use Modules\Common\Models\AuditLog;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class AuditLogQueryService
{
    public function canAccess(?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        return $user->hasRole('Admin') || $user->hasRole('Superadmin');
    }

    public function searchAndPaginate(array $validated): LengthAwarePaginator
    {
        $perPage = (int) ($validated['per_page'] ?? 15);
        $perPage = max(1, min($perPage, 100));
        
        $query = AuditLog::query();
        $search = $validated['search'] ?? null;

        if ($search) {
            $query->search($search);
        }

        return QueryBuilder::for($query)
            ->with('actor')
            ->allowedFilters([
                AllowedFilter::exact('action'),
                AllowedFilter::scope('actions', 'action_in'),
                AllowedFilter::exact('actor_id'),
                AllowedFilter::exact('actor_type'),
                AllowedFilter::exact('subject_id'),
                AllowedFilter::exact('subject_type'),
                AllowedFilter::scope('created_between'),
                AllowedFilter::scope('context_contains'),
                AllowedFilter::scope('assignment_id'),
                AllowedFilter::scope('student_id'),
            ])
            ->allowedSorts(['created_at', 'id', 'action', 'actor_id'])
            ->defaultSort('-created_at')
            ->paginate($perPage)
            ->appends(request()->query());
    }

    public function findById(int $id): ?AuditLog
    {
        return AuditLog::with('actor')->find($id);
    }

    public function getAvailableActions(): array
    {
        return [
            AssessmentAuditService::ACTION_SUBMISSION_CREATED,
            AssessmentAuditService::ACTION_STATE_TRANSITION,
            AssessmentAuditService::ACTION_GRADING,
            AssessmentAuditService::ACTION_ANSWER_KEY_CHANGE,
            AssessmentAuditService::ACTION_GRADE_OVERRIDE,
            AssessmentAuditService::ACTION_OVERRIDE_GRANT,
        ];
    }
}
