<?php

namespace Modules\Notifications\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Notifications\Contracts\NotificationPreferenceServiceInterface;
use Modules\Notifications\Models\NotificationPreference;

/**
 * @tags Notifikasi
 */
class NotificationPreferenceController extends Controller
{
    protected NotificationPreferenceServiceInterface $preferenceService;

    public function __construct(NotificationPreferenceServiceInterface $preferenceService)
    {
        $this->preferenceService = $preferenceService;
    }

    /**
     * Get user's notification preferences.
     *
     * @summary Mengambil preferensi notifikasi user
     *
     * @description Mengambil semua preferensi notifikasi user beserta metadata kategori, channel, dan frekuensi yang tersedia.
     *
     * @response 200 {"success": true, "data": [{"category": "course", "channel": "email", "enabled": true, "frequency": "instant"}], "meta": {"categories": ["course", "assignment", "forum", "system"], "channels": ["email", "push", "in_app"], "frequencies": ["instant", "daily", "weekly"]}}
     */
    public function index(Request $request): JsonResponse
    {
        $preferences = $this->preferenceService->getPreferences(auth()->user());

        return response()->json([
            'success' => true,
            'data' => $preferences,
            'meta' => [
                'categories' => NotificationPreference::getCategories(),
                'channels' => NotificationPreference::getChannels(),
                'frequencies' => NotificationPreference::getFrequencies(),
            ],
        ]);
    }

    /**
     * Update user's notification preferences.
     *
     * @summary Memperbarui preferensi notifikasi user
     *
     * @description Memperbarui preferensi notifikasi user. Setiap preferensi harus menyertakan category, channel, enabled, dan frequency.
     *
     * @response 200 {"success": true, "message": "Notification preferences updated successfully", "data": [{"category": "course", "channel": "email", "enabled": true, "frequency": "instant"}]}
     * @response 422 {"success": false, "message": "Validation error", "errors": {"preferences.0.category": ["The selected preferences.0.category is invalid."]}}
     * @response 500 {"success": false, "message": "Failed to update notification preferences"}
     */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'preferences' => 'required|array',
            'preferences.*.category' => 'required|string|in:'.implode(',', NotificationPreference::getCategories()),
            'preferences.*.channel' => 'required|string|in:'.implode(',', NotificationPreference::getChannels()),
            'preferences.*.enabled' => 'required|boolean',
            'preferences.*.frequency' => 'required|string|in:'.implode(',', NotificationPreference::getFrequencies()),
        ]);

        $success = $this->preferenceService->updatePreferences(
            auth()->user(),
            $validated['preferences']
        );

        if ($success) {
            $preferences = $this->preferenceService->getPreferences(auth()->user());

            return response()->json([
                'success' => true,
                'message' => 'Notification preferences updated successfully',
                'data' => $preferences,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to update notification preferences',
        ], 500);
    }

    /**
     * Reset user's notification preferences to defaults.
     *
     * @summary Reset preferensi notifikasi ke default
     *
     * @description Mengembalikan semua preferensi notifikasi user ke pengaturan default sistem.
     *
     * @response 200 {"success": true, "message": "Notification preferences reset to defaults successfully", "data": [{"category": "course", "channel": "email", "enabled": true, "frequency": "instant"}]}
     * @response 500 {"success": false, "message": "Failed to reset notification preferences"}
     */
    public function reset(Request $request): JsonResponse
    {
        $success = $this->preferenceService->resetToDefaults(auth()->user());

        if ($success) {
            $preferences = $this->preferenceService->getPreferences(auth()->user());

            return response()->json([
                'success' => true,
                'message' => 'Notification preferences reset to defaults successfully',
                'data' => $preferences,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to reset notification preferences',
        ], 500);
    }
}
