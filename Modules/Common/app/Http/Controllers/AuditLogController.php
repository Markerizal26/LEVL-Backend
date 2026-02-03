<?php

declare(strict_types=1);

namespace Modules\Common\Http\Controllers;

use App\Support\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Common\Http\Requests\SearchAuditLogsRequest;
use Modules\Common\Http\Resources\AuditLogResource;
use Modules\Common\Services\AuditLogQueryService;

class AuditLogController extends Controller
{
    use ApiResponse;
    use AuthorizesRequests;

    public function __construct(
        private readonly AuditLogQueryService $queryService
    ) {}

    public function index(SearchAuditLogsRequest $request): JsonResponse
    {
        $user = auth('api')->user();

        if (! $this->queryService->canAccess($user)) {
            return $this->forbidden(__('messages.audit_logs.no_access'));
        }

        $paginator = $this->queryService->searchAndPaginate($request->validated());
        $paginator->getCollection()->transform(fn ($item) => new AuditLogResource($item));

        return $this->paginateResponse($paginator, __('messages.audit_logs.retrieved'));
    }

    public function show(int $id): JsonResponse
    {
        $user = auth('api')->user();

        if (! $this->queryService->canAccess($user)) {
            return $this->forbidden(__('messages.audit_logs.no_access'));
        }

        $auditLog = $this->queryService->findById($id);

        if (! $auditLog) {
            return $this->notFound(__('messages.audit_logs.not_found'));
        }

        return $this->success(['audit_log' => AuditLogResource::make($auditLog)]);
    }

    public function actions(): JsonResponse
    {
        $user = auth('api')->user();

        if (! $this->queryService->canAccess($user)) {
            return $this->forbidden(__('messages.audit_logs.no_access'));
        }

        $actions = $this->queryService->getAvailableActions();

        return $this->success(['actions' => $actions]);
    }
}

