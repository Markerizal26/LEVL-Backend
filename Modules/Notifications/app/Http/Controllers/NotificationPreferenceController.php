<?php

namespace Modules\Notifications\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Notifications\Contracts\Services\NotificationPreferenceServiceInterface;
use Modules\Notifications\Models\NotificationPreference;

/**
 * @tags Notifikasi
 */
class NotificationPreferenceController extends Controller
{
  use ApiResponse;

  protected NotificationPreferenceServiceInterface $preferenceService;

  public function __construct(NotificationPreferenceServiceInterface $preferenceService)
  {
    $this->preferenceService = $preferenceService;
  }

  /**
   * Mengambil preferensi notifikasi user
   *
   * Mengambil semua preferensi notifikasi user beserta metadata kategori, channel, dan frekuensi yang tersedia.
   *
   *
   * @summary Mengambil preferensi notifikasi user
   *
   * @response 200 scenario="Success" {"success": true, "data": [{"category": "course", "channel": "email", "enabled": true, "frequency": "instant"}], "meta": {"categories": ["course", "assignment", "forum", "system"], "channels": ["email", "push", "in_app"], "frequencies": ["instant", "daily", "weekly"]}}
   *
   * @authenticated
   */
  public function index(Request $request): JsonResponse
  {
    $preferences = $this->preferenceService->getPreferences(auth()->user());

    return $this->success(
      data: $preferences,
      meta: [
        "categories" => NotificationPreference::getCategories(),
        "channels" => NotificationPreference::getChannels(),
        "frequencies" => NotificationPreference::getFrequencies(),
      ],
    );
  }

  /**
   * Memperbarui preferensi notifikasi user
   *
   * Memperbarui preferensi notifikasi user. Setiap preferensi harus menyertakan category, channel, enabled, dan frequency.
   *
   *
   * @summary Memperbarui preferensi notifikasi user
   *
   * @response 200 scenario="Success" {"success": true, "message": "Preferensi notifikasi berhasil diperbarui", "data": [{"category": "course", "channel": "email", "enabled": true, "frequency": "instant"}]}
   * @response 422 scenario="Validation Error" {"success": false, "message": "Validation error", "errors": {"preferences.0.category": ["The selected preferences.0.category is invalid."]}}
   * @response 500 scenario="Server Error" {"success":false,"message":"Gagal memperbarui preferensi notifikasi"}
   *
   * @authenticated
   */
  public function update(Request $request): JsonResponse
  {
    $validated = $request->validate([
      "preferences" => "required|array",
      "preferences.*.category" =>
        "required|string|in:" . implode(",", NotificationPreference::getCategories()),
      "preferences.*.channel" =>
        "required|string|in:" . implode(",", NotificationPreference::getChannels()),
      "preferences.*.enabled" => "required|boolean",
      "preferences.*.frequency" =>
        "required|string|in:" . implode(",", NotificationPreference::getFrequencies()),
    ]);

    $success = $this->preferenceService->updatePreferences(
      auth()->user(),
      $validated["preferences"],
    );

    if ($success) {
      $preferences = $this->preferenceService->getPreferences(auth()->user());

      return $this->success(
        data: $preferences,
        message: "Preferensi notifikasi berhasil diperbarui",
      );
    }

    return $this->error(
      message: __("messages.notifications.failed_update_preferences"),
      status: 500,
    );
  }

  /**
   * Reset preferensi notifikasi ke default
   *
   * Mengembalikan semua preferensi notifikasi user ke pengaturan default sistem.
   *
   *
   * @summary Reset preferensi notifikasi ke default
   *
   * @response 200 scenario="Success" {"success": true, "message": "Preferensi notifikasi berhasil direset ke default", "data": [{"category": "course", "channel": "email", "enabled": true, "frequency": "instant"}]}
   * @response 500 scenario="Server Error" {"success":false,"message":"Gagal mereset preferensi notifikasi"}
   *
   * @authenticated
   */
  public function reset(Request $request): JsonResponse
  {
    $success = $this->preferenceService->resetToDefaults(auth()->user());

    if ($success) {
      $preferences = $this->preferenceService->getPreferences(auth()->user());

      return $this->success(
        data: $preferences,
        message: "Preferensi notifikasi berhasil direset ke default",
      );
    }

    return $this->error(
      message: __("messages.notifications.failed_reset_preferences"),
      status: 500,
    );
  }
}
