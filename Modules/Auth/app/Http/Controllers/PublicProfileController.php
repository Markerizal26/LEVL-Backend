<?php

namespace Modules\Auth\Http\Controllers;

use App\Contracts\Services\ProfileServiceInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Auth\Models\User;

/**
 * @tags Profil Pengguna
 */
class PublicProfileController extends Controller
{
    public function __construct(
        private ProfileServiceInterface $profileService
    ) {}

    /**
     * Lihat Profil Publik
     *
     *
     * @summary Lihat Profil Publik
     *
     * @response 200 scenario="Success" {"success":true,"message":"Success","data":{"id":1,"name":"Example PublicProfile"}}
     * @response 404 scenario="Not Found" {"success":false,"message":"PublicProfile tidak ditemukan."}
     * @unauthenticated
     */
    public function show(Request $request, int $userId): JsonResponse
    {
        try {
            $user = User::findOrFail($userId);
            $viewer = $request->user();

            $profileData = $this->profileService->getPublicProfile($user, $viewer);

            return response()->json([
                'success' => true,
                'data' => $profileData,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 403);
        }
    }
}
