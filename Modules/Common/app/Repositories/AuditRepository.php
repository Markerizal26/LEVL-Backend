<?php

declare(strict_types=1);

namespace Modules\Common\Repositories;

use Illuminate\Support\Collection;
use Modules\Common\Contracts\Repositories\AuditRepositoryInterface;
use Modules\Common\Models\AuditLog;

/**
 * Repository for audit log operations.
 *
 * This repository provides access to the immutable audit log storage.
 * Only create and search operations are supported to maintain audit trail integrity.
 *
 * Requirements: 20.6, 20.7
 */
class AuditRepository implements AuditRepositoryInterface
{
    /**
     * Create a new audit log entry.
     *
     * @param  array  $data  The audit log data
     * @return AuditLog The created audit log entry
     */
    public function create(array $data): AuditLog
    {
        return AuditLog::create($data);
    }

    /**
     * Search audit logs with filters.
     *
     * Requirements: 20.7
     *
     * @param  array  $filters  Search filters
     * @return Collection<int, AuditLog> Collection of matching audit logs
     */
    public function search(array $filters): Collection
    {
        $query = AuditLog::query();

        // Filter by action
        if (isset($filters['action']) && ! empty($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        // Filter by multiple actions
        if (isset($filters['actions']) && is_array($filters['actions']) && ! empty($filters['actions'])) {
            $query->whereIn('action', $filters['actions']);
        }

        // Filter by actor
        if (isset($filters['actor_id']) && ! empty($filters['actor_id'])) {
            $query->where('actor_id', $filters['actor_id']);
        }

        if (isset($filters['actor_type']) && ! empty($filters['actor_type'])) {
            $query->where('actor_type', $filters['actor_type']);
        }

        // Filter by subject
        if (isset($filters['subject_id']) && ! empty($filters['subject_id'])) {
            $query->where('subject_id', $filters['subject_id']);
        }

        if (isset($filters['subject_type']) && ! empty($filters['subject_type'])) {
            $query->where('subject_type', $filters['subject_type']);
        }

        // Filter by date range
        if (isset($filters['start_date']) && ! empty($filters['start_date'])) {
            $query->where('created_at', '>=', $filters['start_date']);
        }

        if (isset($filters['end_date']) && ! empty($filters['end_date'])) {
            $query->where('created_at', '<=', $filters['end_date']);
        }

        // Search in context JSON field
        if (isset($filters['context_search']) && ! empty($filters['context_search'])) {
            $query->where('context', 'like', '%'.$filters['context_search'].'%');
        }

        // Filter by assignment_id in context
        if (isset($filters['assignment_id']) && ! empty($filters['assignment_id'])) {
            $query->whereJsonContains('context->assignment_id', (int) $filters['assignment_id']);
        }

        // Filter by student_id in context
        if (isset($filters['student_id']) && ! empty($filters['student_id'])) {
            $query->whereJsonContains('context->student_id', (int) $filters['student_id']);
        }

        // Order by created_at descending (most recent first)
        $query->orderBy('created_at', 'desc');

        // Apply limit if specified
        if (isset($filters['limit']) && is_numeric($filters['limit'])) {
            $query->limit((int) $filters['limit']);
        }

        return $query->get();
    }

    /**
     * Find audit logs for a specific subject.
     *
     * @param  string  $subjectType  The subject type (model class)
     * @param  int  $subjectId  The subject ID
     * @return Collection<int, AuditLog> Collection of audit logs for the subject
     */
    public function findBySubject(string $subjectType, int $subjectId): Collection
    {
        return AuditLog::query()
            ->where('subject_type', $subjectType)
            ->where('subject_id', $subjectId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Find audit logs by actor.
     *
     * @param  string  $actorType  The actor type (model class)
     * @param  int  $actorId  The actor ID
     * @return Collection<int, AuditLog> Collection of audit logs by the actor
     */
    public function findByActor(string $actorType, int $actorId): Collection
    {
        return AuditLog::query()
            ->where('actor_type', $actorType)
            ->where('actor_id', $actorId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Find audit logs by action type.
     *
     * @param  string  $action  The action type
     * @return Collection<int, AuditLog> Collection of audit logs for the action
     */
    public function findByAction(string $action): Collection
    {
        return AuditLog::query()
            ->where('action', $action)
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
