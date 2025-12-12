<?php

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Auth\Http\Requests\UpdatePrivacySettingsRequest;
use Modules\Auth\Services\ProfilePrivacyService;

/**
 * @tags Profil Pengguna
 */
class ProfilePrivacyController extends Controller
{
  use ApiResponse;

  public function __construct(private ProfilePrivacyService $privacyService) {}

  /**
   * Ambil Pengaturan Privasi
   *
   * Mengambil pengaturan privasi profil pengguna (visibilitas email, aktivitas, dll).
   *
   *
   * @summary Ambil Pengaturan Privasi
   *
   * @response 200 scenario="Success" {"success": true, "data": {"show_email": false, "show_activity": true, "show_achievements": true, "show_statistics": true}}
   * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
   *
   * @authenticated
   */
  public function index(Request $request): JsonResponse
  {
    $user = $request->user();
    $settings = $this->privacyService->getPrivacySettings($user);

    return $this->success($settings);
  }

  /**
   * Perbarui Pengaturan Privasi
   *
   * Memperbarui pengaturan privasi profil pengguna.
   *
   *
   * @summary Perbarui Pengaturan Privasi
   *
   * @response 200 scenario="Success" {"success": true, "message": "Privacy settings updated successfully.", "data": {"show_email": false, "show_activity": true, "show_achievements": true}}
   * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
   * @response 422 scenario="Validation Error" {"success":false,"message":"Validasi gagal."}
   *
   * @authenticated
   */
  public function update(UpdatePrivacySettingsRequest $request): JsonResponse
  {
    $user = $request->user();
    $settings = $this->privacyService->updatePrivacySettings($user, $request->validated());

    return $this->success($settings, __("messages.profile.privacy_updated"));
  }
}
