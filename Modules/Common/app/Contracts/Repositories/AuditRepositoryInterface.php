<?php

declare(strict_types=1);

namespace Modules\Common\Contracts\Repositories;

use Illuminate\Support\Collection;
use Modules\Common\Models\AuditLog;

/**
 * Interface for audit log repository operations.
 *
 * This repository provides access to the immutable audit log storage.
 * Note: Only create and search operations are supported - updates and deletes
 * are not allowed to maintain audit trail integrity.
 *
 * Requirements: 20.6, 20.7
 */
interface AuditRepositoryInterface
{
    /**
     * Create a new audit log entry.
     *
     * @param  array  $data  The audit log data
     * @return AuditLog The created audit log entry
     */
    public function create(array $data): AuditLog;

    /**
     * Search audit logs with filters.
     *
     * Requirements: 20.7
     *
     * @param  array  $filters  Search filters:
     *                          - action: string - Filter by single action type
     *                          - actions: array - Filter by multiple action types
     *                          - actor_id: int - Filter by actor ID
     *                          - actor_type: string - Filter by actor type
     *                          - subject_id: int - Filter by subject ID
     *                          - subject_type: string - Filter by subject type
     *                          - start_date: Carbon|string - Filter by start date
     *                          - end_date: Carbon|string - Filter by end date
     *                          - context_search: string - Search in context JSON field
     *                          - assignment_id: int - Filter by assignment_id in context
     *                          - student_id: int - Filter by student_id in context
     *                          - limit: int - Limit number of results
     * @return Collection<int, AuditLog> Collection of matching audit logs
     */
    public function search(array $filters): Collection;

    /**
     * Find audit logs for a specific subject.
     *
     * @param  string  $subjectType  The subject type (model class)
     * @param  int  $subjectId  The subject ID
     * @return Collection<int, AuditLog> Collection of audit logs for the subject
     */
    public function findBySubject(string $subjectType, int $subjectId): Collection;

    /**
     * Find audit logs by actor.
     *
     * @param  string  $actorType  The actor type (model class)
     * @param  int  $actorId  The actor ID
     * @return Collection<int, AuditLog> Collection of audit logs by the actor
     */
    public function findByActor(string $actorType, int $actorId): Collection;

    /**
     * Find audit logs by action type.
     *
     * @param  string  $action  The action type
     * @return Collection<int, AuditLog> Collection of audit logs for the action
     */
    public function findByAction(string $action): Collection;
}
