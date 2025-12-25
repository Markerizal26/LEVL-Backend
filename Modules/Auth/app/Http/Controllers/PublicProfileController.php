<?php

namespace Modules\Auth\Http\Controllers;

use App\Contracts\Services\ProfileServiceInterface;
use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Auth\Models\User;

/**
 * @tags Profil Pengguna
 */
class PublicProfileController extends Controller
{
    use ApiResponse;

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
     *
     * @unauthenticated
     */
    public function show(Request $request, int $userId): JsonResponse
    {
        $user = User::find($userId);

        if (! $user) {
            return $this->notFound(__('messages.user.not_found'));
        }

        $viewer = $request->user();

        $profileData = $this->profileService->getPublicProfile($user, $viewer);

        return $this->success($profileData);
    }
}

