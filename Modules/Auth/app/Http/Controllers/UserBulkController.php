<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Modules\Auth\Contracts\Repositories\UserBulkRepositoryInterface;
use Modules\Auth\Contracts\Services\UserBulkServiceInterface;
use Modules\Auth\Http\Requests\BulkActivateRequest;
use Modules\Auth\Http\Requests\BulkDeactivateRequest;
use Modules\Auth\Http\Requests\BulkDeleteRequest;
use Modules\Auth\Http\Requests\BulkExportRequest;

class UserBulkController extends Controller
{
  use ApiResponse;

  public function __construct(
    private UserBulkServiceInterface $bulkService,
    private UserBulkRepositoryInterface $bulkRepository
  ) {
    $this->middleware('role:Superadmin,Admin');
  }

  public function export(BulkExportRequest $request): JsonResponse
  {
    $this->bulkService->export(
      $request->user(),
      $request->validated()
    );

    return $this->success(null, __("messages.auth.bulk_export_queued"));
  }

  public function bulkActivate(BulkActivateRequest $request): JsonResponse
  {
    $count = $this->bulkService->bulkActivate(
      $request->input('user_ids'),
      $request->user()->id
    );

    return $this->success(
      ['updated' => $count],
      trans_choice("messages.users.bulk_activated", $count, ['count' => $count])
    );
  }

  public function bulkDeactivate(BulkDeactivateRequest $request): JsonResponse
  {
      $count = $this->bulkService->bulkDeactivate(
        $request->input('user_ids'),
        $request->user()->id,
        $request->user()->id
      );

      return $this->success(
        ['updated' => $count],
        trans_choice("messages.users.bulk_deactivated", $count, ['count' => $count])
      );
  }

  public function bulkDelete(BulkDeleteRequest $request): JsonResponse
  {
      $count = $this->bulkService->bulkDelete(
        $request->input('user_ids'),
        $request->user()->id
      );

      return $this->success(
        ['deleted' => $count],
        trans_choice("messages.users.bulk_deleted", $count, ['count' => $count])
      );
  }

  public function activate(BulkActivateRequest $request): JsonResponse
  {
    return $this->bulkActivate($request);
  }

  public function deactivate(BulkDeactivateRequest $request): JsonResponse
  {
    return $this->bulkDeactivate($request);
  }

  public function delete(BulkDeleteRequest $request): JsonResponse
  {
    return $this->bulkDelete($request);
  }
}
