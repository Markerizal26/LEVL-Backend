<?php

declare(strict_types=1);

namespace Modules\Common\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as PaginatorInstance;
use Illuminate\Support\Collection;
use Modules\Auth\Models\User;
use Modules\Common\Contracts\Services\AuditServiceInterface;
use Modules\Common\Models\AuditLog;
use Modules\Common\Services\AssessmentAuditService;

class AuditLogQueryService
{
    public function __construct(
        private readonly AuditServiceInterface $auditService
    ) {}

    public function canAccess(?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        return $user->hasRole('Admin') || $user->hasRole('Superadmin');
    }

    public function searchAndPaginate(array $validated): LengthAwarePaginator
    {
        $filters = $this->buildFilters($validated);
        $auditLogs = $this->auditService->search($filters);
        
        return $this->paginateResults($auditLogs, $validated);
    }

    private function buildFilters(array $validated): array
    {
        $filters = [];

        if (isset($validated['action'])) {
            $filters['action'] = $validated['action'];
        }

        if (isset($validated['actions'])) {
            $filters['actions'] = $validated['actions'];
        }

        if (isset($validated['actor_id'])) {
            $filters['actor_id'] = $validated['actor_id'];
        }

        if (isset($validated['actor_type'])) {
            $filters['actor_type'] = $validated['actor_type'];
        }

        if (isset($validated['subject_id'])) {
            $filters['subject_id'] = $validated['subject_id'];
        }

        if (isset($validated['subject_type'])) {
            $filters['subject_type'] = $validated['subject_type'];
        }

        if (isset($validated['start_date'])) {
            $filters['start_date'] = $validated['start_date'];
        }

        if (isset($validated['end_date'])) {
            $filters['end_date'] = $validated['end_date'];
        }

        if (isset($validated['context_search'])) {
            $filters['context_search'] = $validated['context_search'];
        }

        if (isset($validated['assignment_id'])) {
            $filters['assignment_id'] = $validated['assignment_id'];
        }

        if (isset($validated['student_id'])) {
            $filters['student_id'] = $validated['student_id'];
        }

        return $filters;
    }

    // ... findById ...

    private function paginateResults(Collection $auditLogs, array $validated): LengthAwarePaginator
    {
        $page = $this->extractPage($validated);
        $perPage = $this->extractPerPage($validated);
        $count = $auditLogs->count();
        
        $items = $auditLogs->slice(($page - 1) * $perPage, $perPage)->values();

        return new PaginatorInstance(
            $items,
            $count,
            $perPage,
            $page,
            [
                'path' => PaginatorInstance::resolveCurrentPath(),
                'query' => $validated,
            ]
        );
    }

    // sliceLogs and buildMeta methods can be removed as Paginator handles this.

    private function extractPage(array $validated): int
    {
        if (! isset($validated['page'])) {
            return 1;
        }

        return is_numeric($validated['page']) ? intval($validated['page']) : 1;
    }

    private function extractPerPage(array $validated): int
    {
        if (! isset($validated['per_page'])) {
            return 15;
        }

        return is_numeric($validated['per_page']) ? intval($validated['per_page']) : 15;
    }
}
