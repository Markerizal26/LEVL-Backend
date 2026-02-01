<?php

declare(strict_types=1);

namespace Modules\Common\Http\Controllers;

use App\Support\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Auth\Models\User;
use Modules\Common\Contracts\Services\AuditServiceInterface;
use Modules\Common\Http\Requests\SearchAuditLogsRequest;
use Modules\Common\Http\Resources\AuditLogResource;
use Modules\Common\Models\AuditLog;
use Modules\Common\Services\AssessmentAuditService;

/**
 * Controller for audit log search and filtering.
 *
 * Handles:
 * - Audit log search and filtering (Requirement 20.7)
 *
 * Access is restricted to Admin and Superadmin roles only.
 *
 * @tags Audit Logs
 */
class AuditLogController extends Controller
{
    use ApiResponse;
    use AuthorizesRequests;

    public function __construct(
        private readonly AuditServiceInterface $auditService
    ) {}

    // =========================================================================
    // Audit Log Search (Requirement 20.7)
    // =========================================================================

    /**
     * Search and filter audit logs.
     *
     * GET /audit-logs
     *
     * Provides comprehensive search and filtering capabilities for audit logs.
     * Supports filtering by action, actor, subject, date range, and more.
     *
     * Requirement: 20.7
     *
     * @authenticated
     */
    public function index(SearchAuditLogsRequest $request): JsonResponse
    {
        /** @var User|null $user */
        $user = auth('api')->user();

        // Only Admin and Superadmin can access audit logs
        if ($user === null || (! $user->hasRole('Admin') && ! $user->hasRole('Superadmin'))) {
            return $this->forbidden(__('messages.audit_logs.no_access'));
        }

        $validated = $request->validated();

        // Build filters array from validated request data
        $filters = array_filter([
            'action' => $validated['action'] ?? null,
            'actions' => $validated['actions'] ?? null,
            'actor_id' => $validated['actor_id'] ?? null,
            'actor_type' => $validated['actor_type'] ?? null,
            'subject_id' => $validated['subject_id'] ?? null,
            'subject_type' => $validated['subject_type'] ?? null,
            'start_date' => $validated['start_date'] ?? null,
            'end_date' => $validated['end_date'] ?? null,
            'context_search' => $validated['context_search'] ?? null,
            'assignment_id' => $validated['assignment_id'] ?? null,
            'student_id' => $validated['student_id'] ?? null,
        ], fn ($value) => $value !== null);

        // Get audit logs from service
        $auditLogs = $this->auditService->search($filters);

        // Apply pagination
        $page = isset($validated['page']) && is_numeric($validated['page']) ? intval($validated['page']) : 1;
        $perPage = isset($validated['per_page']) && is_numeric($validated['per_page']) ? intval($validated['per_page']) : 15;
        $total = $auditLogs->count();
        $paginatedLogs = $auditLogs->slice(($page - 1) * $perPage, $perPage)->values();

        return $this->success([
            'audit_logs' => AuditLogResource::collection($paginatedLogs),
            'meta' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => (int) ceil($total / $perPage),
            ],
        ]);
    }

    /**
     * Get a specific audit log entry.
     *
     * GET /audit-logs/{id}
     *
     * Returns the details of a specific audit log entry.
     *
     * @authenticated
     */
    public function show(int $id): JsonResponse
    {
        /** @var User|null $user */
        $user = auth('api')->user();

        // Only Admin and Superadmin can access audit logs
        if ($user === null || (! $user->hasRole('Admin') && ! $user->hasRole('Superadmin'))) {
            return $this->forbidden(__('messages.audit_logs.no_access'));
        }

        $auditLog = AuditLog::find($id);

        if (! $auditLog) {
            return $this->notFound(__('messages.audit_logs.not_found'));
        }

        return $this->success([
            'audit_log' => AuditLogResource::make($auditLog),
        ]);
    }

    /**
     * Get available action types for filtering.
     *
     * GET /audit-logs/actions
     *
     * Returns a list of all available action types that can be used
     * for filtering audit logs.
     *
     * @authenticated
     */
    public function actions(): JsonResponse
    {
        /** @var User|null $user */
        $user = auth('api')->user();

        // Only Admin and Superadmin can access audit logs
        if ($user === null || (! $user->hasRole('Admin') && ! $user->hasRole('Superadmin'))) {
            return $this->forbidden(__('messages.audit_logs.no_access'));
        }

        // Return available action types from the AssessmentAuditService
        $actions = [
            AssessmentAuditService::ACTION_SUBMISSION_CREATED,
            AssessmentAuditService::ACTION_STATE_TRANSITION,
            AssessmentAuditService::ACTION_GRADING,
            AssessmentAuditService::ACTION_ANSWER_KEY_CHANGE,
            AssessmentAuditService::ACTION_GRADE_OVERRIDE,

            AssessmentAuditService::ACTION_OVERRIDE_GRANT,
        ];

        return $this->success([
            'actions' => $actions,
        ]);
    }
}
