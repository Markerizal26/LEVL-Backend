<?php

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Auth\Models\PinnedBadge;

/**
 * @tags Profil Pengguna
 */
class ProfileAchievementController extends Controller
{
  use ApiResponse;

  /**
   * Daftar Badge dan Pencapaian
   *
   *
   * @summary Daftar Badge dan Pencapaian
   *
   * @response 200 scenario="Success" {"success":true,"message":"Success","data":[{"id":1,"name":"Example ProfileAchievement"}],"meta":{"current_page":1,"last_page":5,"per_page":15,"total":75},"links":{"first":"...","last":"...","prev":null,"next":"..."}}
   * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
   *
   * @authenticated
   */
  public function index(Request $request): JsonResponse
  {
    $user = $request->user();

    $badges = $user->badges()->with("badge")->get();
    $pinnedBadges = $user->pinnedBadges()->with("badge")->orderBy("order")->get();

    return $this->success([
      "badges" => $badges,
      "pinned_badges" => $pinnedBadges,
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
   *
   * @authenticated
   */
  public function pinBadge(Request $request, int $badgeId): JsonResponse
  {
    $request->validate([
      "order" => "sometimes|integer|min:0",
    ]);

    $user = $request->user();

    // Check if user has this badge
    $hasBadge = $user->badges()->where("badge_id", $badgeId)->exists();
    if (!$hasBadge) {
      return $this->notFound("You do not have this badge.");
    }

    // Check if already pinned
    $existing = PinnedBadge::where("user_id", $user->id)->where("badge_id", $badgeId)->first();

    if ($existing) {
      return $this->error(__("messages.profile.badge_already_pinned"), 422);
    }

    // Pin the badge
    $pinnedBadge = PinnedBadge::create([
      "user_id" => $user->id,
      "badge_id" => $badgeId,
      "order" => $request->input("order", 0),
    ]);

    return $this->success($pinnedBadge->load("badge"), __("messages.profile.badge_pinned"));
  }

  /**
   * Lepas Badge dari Profil
   *
   *
   * @summary Lepas Badge dari Profil
   *
   * @response 200 scenario="Success" {"success":true,"message":"Success","data":{"id":1,"name":"Example ProfileAchievement"}}
   * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
   *
   * @authenticated
   */
  public function unpinBadge(Request $request, int $badgeId): JsonResponse
  {
    $user = $request->user();

    $pinnedBadge = PinnedBadge::where("user_id", $user->id)->where("badge_id", $badgeId)->first();

    if (!$pinnedBadge) {
      return $this->notFound("Badge is not pinned.");
    }

    $pinnedBadge->delete();

    return $this->success(null, __("messages.profile.badge_unpinned"));
  }
}
