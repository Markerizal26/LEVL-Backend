<?php

namespace Modules\Auth\Http\Controllers;

use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Auth\Models\User;
use Modules\Auth\Services\UserBulkService;

/**
 * @tags Manajemen Pengguna
 */
class UserBulkController extends Controller
{
  use ApiResponse;

  public function __construct(private UserBulkService $service) {}

  public function export(Request $request)
  {
    /** @var User $user */
    $user = Auth::user();

    if (!$user->hasAnyRole(["Superadmin", "Admin"])) {
      return $this->error(__("messages.users.no_export_access"), 403);
    }

    $validated = $request->validate([
      "user_ids" => "required|array|min:1|max:1000",
      "user_ids.*" => "required|integer|exists:users,id",
    ]);

    $this->service->exportToEmail($validated["user_ids"], $user->email);

    return $this->success(null, __("messages.users.bulk_export_queued"));
  }

  public function activate(Request $request)
  {
    /** @var User $user */
    $user = Auth::user();

    if (!$user->hasAnyRole(["Superadmin", "Admin"])) {
      return $this->error(__("messages.users.no_activate_access"), 403);
    }

    $validated = $request->validate([
      "user_ids" => "required|array|min:1|max:100",
      "user_ids.*" => "required|integer|exists:users,id",
    ]);

    $updated = $this->service->bulkActivate($validated["user_ids"], $user->id);

    return $this->success(
      ["updated" => $updated],
      __("messages.users.bulk_activated", ["count" => $updated]),
    );
  }

  public function deactivate(Request $request)
  {
    /** @var User $user */
    $user = Auth::user();

    if (!$user->hasAnyRole(["Superadmin", "Admin"])) {
      return $this->error(__("messages.users.no_deactivate_access"), 403);
    }

    $validated = $request->validate([
      "user_ids" => "required|array|min:1|max:100",
      "user_ids.*" => "required|integer|exists:users,id",
    ]);

    try {
      $updated = $this->service->bulkDeactivate($validated["user_ids"], $user->id, $user->id);

      return $this->success(
        ["updated" => $updated],
        __("messages.users.bulk_deactivated", ["count" => $updated]),
      );
    } catch (\InvalidArgumentException $e) {
      return $this->error(__("messages.users.cannot_deactivate_self"), 422);
    }
  }

  public function delete(Request $request)
  {
    /** @var User $user */
    $user = Auth::user();

    if (!$user->hasRole("Superadmin")) {
      return $this->error(__("messages.users.no_delete_access"), 403);
    }

    $validated = $request->validate([
      "user_ids" => "required|array|min:1|max:100",
      "user_ids.*" => "required|integer|exists:users,id",
    ]);

    try {
      $deleted = $this->service->bulkDelete($validated["user_ids"], $user->id);

      return $this->success(
        ["deleted" => $deleted],
        __("messages.users.bulk_deleted", ["count" => $deleted]),
      );
    } catch (\InvalidArgumentException $e) {
      return $this->error(__("messages.users.cannot_delete_self"), 422);
    }
  }
}
