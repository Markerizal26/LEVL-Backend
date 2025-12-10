<?php

namespace Modules\Gamification\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Gamification\Models\Challenge;
use Modules\Gamification\Services\ChallengeService;

/**
 * @tags Gamifikasi
 */
class ChallengeController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly ChallengeService $challengeService
    ) {}

    /**
     * Mengambil daftar challenge aktif
     *
     * Mengambil daftar challenge yang sedang aktif. Jika user sudah login, akan menyertakan progress user untuk setiap challenge.
     *
     * **Filter yang tersedia:**
     * - `filter[type]` (string): Filter berdasarkan tipe challenge. Nilai: daily, weekly, special
     *
     * **Sorting:** Gunakan parameter `sort` dengan prefix `-` untuk descending. Nilai: type, points_reward, created_at
     *
     * @summary Daftar Challenge Aktif
     *
     * @queryParam filter[type] string Filter berdasarkan tipe challenge. Nilai: daily, weekly, special. Example: daily
     * @queryParam sort string Field untuk sorting. Prefix dengan '-' untuk descending. Example: -points_reward
     * @queryParam page integer Nomor halaman. Default: 1. Example: 1
     * @queryParam per_page integer Jumlah item per halaman. Default: 15. Example: 15
     *
     * @response 200 scenario="Success" {"success": true, "message": "Berhasil", "data": [{"id": 1, "name": "Login Harian", "description": "Login setiap hari untuk mendapatkan poin", "type": "daily", "points_reward": 10, "criteria_target": 1, "badge": null, "user_progress": {"current": 0, "target": 1, "percentage": 0, "status": "in_progress", "expires_at": "2025-01-16T00:00:00Z"}}], "meta": {"pagination": {"current_page": 1, "per_page": 15, "total": 5}}}
     *
     * @authenticated
     */
    public function index(Request $request): JsonResponse
    {
        $challenges = Challenge::active()
            ->with('badge')
            ->orderBy('type')
            ->orderBy('points_reward', 'desc')
            ->paginate($request->input('per_page', 15));

        $userId = $request->user()?->id;

        // Add user progress if authenticated
        if ($userId) {
            $userChallenges = $this->challengeService->getUserChallenges($userId)
                ->keyBy('challenge_id');

            $challenges->getCollection()->transform(function ($challenge) use ($userChallenges) {
                $assignment = $userChallenges->get($challenge->id);
                $challenge->user_progress = $assignment ? [
                    'current' => $assignment->current_progress,
                    'target' => $challenge->criteria_target,
                    'percentage' => $assignment->getProgressPercentage(),
                    'status' => $assignment->status->value,
                    'expires_at' => $assignment->expires_at,
                ] : null;

                return $challenge;
            });
        }

        return $this->paginateResponse($challenges);
    }

    /**
     * Mengambil detail challenge
     *
     * Mengambil detail challenge beserta badge reward dan progress user jika sudah login.
     *
     *
     * @summary Mengambil detail challenge
     *
     * @response 200 scenario="Success" {"success": true, "data": {"challenge": {"id": 1, "name": "Login Harian", "description": "Login setiap hari", "type": "daily", "points_reward": 10, "criteria_target": 1, "badge": {"id": 1, "name": "Daily Warrior"}, "user_progress": {"current": 1, "target": 1, "percentage": 100, "status": "completed", "expires_at": "2024-01-16T00:00:00Z", "is_claimable": true}}}}
     * @response 404 scenario="Not Found" {"success":false,"message":"Challenge tidak ditemukan."}
     *
     * @authenticated
     */
    public function show(int $challengeId, Request $request): JsonResponse
    {
        $challenge = Challenge::with('badge')->find($challengeId);

        if (! $challenge) {
            return $this->notFound('Challenge tidak ditemukan.');
        }

        $userId = $request->user()?->id;

        if ($userId) {
            $userChallenges = $this->challengeService->getUserChallenges($userId)
                ->keyBy('challenge_id');

            $assignment = $userChallenges->get($challenge->id);
            $challenge->user_progress = $assignment ? [
                'current' => $assignment->current_progress,
                'target' => $challenge->criteria_target,
                'percentage' => $assignment->getProgressPercentage(),
                'status' => $assignment->status->value,
                'expires_at' => $assignment->expires_at,
                'is_claimable' => $assignment->isClaimable(),
            ] : null;
        }

        return $this->success(['challenge' => $challenge]);
    }

    /**
     * Mengambil challenge yang di-assign ke user
     *
     * Mengambil semua challenge yang sedang aktif untuk user yang login, beserta progress dan status masing-masing.
     *
     *
     * @summary Mengambil challenge yang di-assign ke user
     *
     * @response 200 scenario="Success" {"success": true, "data": {"challenges": [{"id": 1, "challenge": {"id": 1, "name": "Login Harian"}, "progress": {"current": 1, "target": 1, "percentage": 100}, "status": "completed", "status_label": "Selesai", "assigned_date": "2024-01-15", "expires_at": "2024-01-16T00:00:00Z", "is_claimable": true}]}}
     *
     * @authenticated
     */
    public function myChallenges(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $challenges = $this->challengeService->getUserChallenges($userId);

        $data = $challenges->map(function ($assignment) {
            return [
                'id' => $assignment->id,
                'challenge' => $assignment->challenge,
                'progress' => [
                    'current' => $assignment->current_progress,
                    'target' => $assignment->challenge?->criteria_target ?? 1,
                    'percentage' => $assignment->getProgressPercentage(),
                ],
                'status' => $assignment->status->value,
                'status_label' => $assignment->status->label(),
                'assigned_date' => $assignment->assigned_date,
                'expires_at' => $assignment->expires_at,
                'is_claimable' => $assignment->isClaimable(),
            ];
        });

        return $this->success(['challenges' => $data]);
    }

    /**
     * Mengambil riwayat challenge yang sudah selesai
     *
     * Mengambil riwayat challenge yang sudah diselesaikan user beserta XP yang diperoleh.
     *
     *
     * @summary Mengambil riwayat challenge yang sudah selesai
     *
     * @queryParam limit integer Jumlah item yang diambil. Default: 15. Example: 15
     *
     * @response 200 scenario="Success" {"success": true, "data": {"completions": [{"id": 1, "challenge": {"id": 1, "name": "Login Harian"}, "completed_date": "2024-01-15", "xp_earned": 10, "completion_data": null}]}}
     *
     * @authenticated
     */
    public function completed(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $limit = $request->input('limit', 15);

        $completions = $this->challengeService->getCompletedChallenges($userId, $limit);

        $data = $completions->map(function ($completion) {
            return [
                'id' => $completion->id,
                'challenge' => $completion->challenge,
                'completed_date' => $completion->completed_date,
                'xp_earned' => $completion->xp_earned,
                'completion_data' => $completion->completion_data,
            ];
        });

        return $this->success(['completions' => $data]);
    }

    /**
     * Klaim reward challenge yang sudah selesai
     *
     * Mengklaim reward (XP dan badge jika ada) untuk challenge yang sudah selesai. Challenge harus dalam status completed dan belum diklaim.
     *
     *
     * @summary Klaim reward challenge yang sudah selesai
     *
     * @response 200 scenario="Success" {"success": true, "data": {"message": "Reward berhasil diklaim!", "rewards": {"xp": 50, "badge": {"id": 1, "name": "Daily Warrior"}}}}
     * @response 400 scenario="Bad Request" {"success":false,"message":"Challenge belum selesai atau sudah diklaim."}
     *
     * @authenticated
     */
    public function claim(int $challengeId, Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        try {
            $rewards = $this->challengeService->claimReward($userId, $challengeId);

            return $this->success([
                'message' => 'Reward berhasil diklaim!',
                'rewards' => $rewards,
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }
}
