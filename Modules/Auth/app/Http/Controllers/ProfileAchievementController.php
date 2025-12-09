<?php

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Auth\Models\PinnedBadge;

/**
 * @tags Profil Pengguna
 */
class ProfileAchievementController extends Controller
{
    /**
     * Daftar Badge dan Pencapaian
     *
     *
     * @summary Daftar Badge dan Pencapaian
     *
     * @response 200 scenario="Success" {"success":true,"message":"Success","data":[{"id":1,"name":"Example ProfileAchievement"}],"meta":{"current_page":1,"last_page":5,"per_page":15,"total":75},"links":{"first":"...","last":"...","prev":null,"next":"..."}}
     * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
     * @authenticated
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $badges = $user->badges()->with('badge')->get();
        $pinnedBadges = $user->pinnedBadges()->with('badge')->orderBy('order')->get();

        return response()->json([
            'success' => true,
            'data' => [
                'badges' => $badges,
                'pinned_badges' => $pinnedBadges,
            ],
        ]);
    }

    /**
     * Sematkan Badge ke Profil
     *
     *
     * @summary Sematkan Badge ke Profil
     *
     * @response 200 scenario="Success" {"success":true,"message":"Success","data":{"id":1,"name":"Example ProfileAchievement"}}
     * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
     * @authenticated
     */
    public function pinBadge(Request $request, int $badgeId): JsonResponse
    {
        $request->validate([
            'order' => 'sometimes|integer|min:0',
        ]);

        try {
            $user = $request->user();

            // Check if user has this badge
            $hasBadge = $user->badges()->where('badge_id', $badgeId)->exists();
            if (! $hasBadge) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have this badge.',
                ], 404);
            }

            // Check if already pinned
            $existing = PinnedBadge::where('user_id', $user->id)
                ->where('badge_id', $badgeId)
                ->first();

            if ($existing) {
                return response()->json([
                    'success' => false,
                    'message' => 'Badge is already pinned.',
                ], 422);
            }

            // Pin the badge
            $pinnedBadge = PinnedBadge::create([
                'user_id' => $user->id,
                'badge_id' => $badgeId,
                'order' => $request->input('order', 0),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Badge pinned successfully.',
                'data' => $pinnedBadge->load('badge'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Lepas Badge dari Profil
     *
     *
     * @summary Lepas Badge dari Profil
     *
     * @response 200 scenario="Success" {"success":true,"message":"Success","data":{"id":1,"name":"Example ProfileAchievement"}}
     * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
     * @authenticated
     */
    public function unpinBadge(Request $request, int $badgeId): JsonResponse
    {
        try {
            $user = $request->user();

            $pinnedBadge = PinnedBadge::where('user_id', $user->id)
                ->where('badge_id', $badgeId)
                ->first();

            if (! $pinnedBadge) {
                return response()->json([
                    'success' => false,
                    'message' => 'Badge is not pinned.',
                ], 404);
            }

            $pinnedBadge->delete();

            return response()->json([
                'success' => true,
                'message' => 'Badge unpinned successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
