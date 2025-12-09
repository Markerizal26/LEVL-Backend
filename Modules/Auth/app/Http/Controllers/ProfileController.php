<?php

namespace Modules\Auth\Http\Controllers;

use App\Contracts\Services\ProfileServiceInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Auth\Http\Requests\UpdateProfileRequest;

/**
 * @tags Profil Pengguna
 */
class ProfileController extends Controller
{
    public function __construct(
        private ProfileServiceInterface $profileService
    ) {}

    /**
     * Ambil Data Profil
     *
     * Mengambil data profil lengkap pengguna yang sedang login termasuk statistik dan achievements.
     *
     *
     * @summary Ambil Data Profil
     * @response 200 scenario="Success" {"success": true, "data": {"id": 1, "name": "John Doe", "email": "john@example.com", "username": "johndoe", "avatar_url": "https://example.com/avatar.jpg", "bio": "Student", "statistics": {"courses_enrolled": 5, "courses_completed": 2}}}
     * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
     *
     * @authenticated
     */    
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $profileData = $this->profileService->getProfileData($user);

        return response()->json([
            'success' => true,
            'data' => $profileData,
        ]);
    }

    /**
     * Perbarui Data Profil
     *
     * Memperbarui data profil pengguna (nama, username, bio, dll).
     *
     *
     * @summary Perbarui Data Profil
     * @response 200 scenario="Success" {"success": true, "message": "Profile updated successfully.", "data": {"id": 1, "name": "John Updated", "email": "john@example.com", "username": "johnupdated"}}
     * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
     * @response 422 scenario="Validation Error" {"success":false,"message":"Username sudah digunakan."}
     *
     * @authenticated
     */    
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            $updatedUser = $this->profileService->updateProfile($user, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully.',
                'data' => $this->profileService->getProfileData($updatedUser),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Unggah Foto Profil
     *
     * Mengunggah foto profil baru. Format yang didukung: JPEG, PNG, JPG, GIF. Maksimal 2MB.
     *
     *
     * @summary Unggah Foto Profil
     * @response 200 scenario="Success" {"success": true, "message": "Avatar uploaded successfully.", "data": {"avatar_url": "https://example.com/storage/avatars/user-1.jpg"}}
     * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
     * @response 422 scenario="Validation Error" {"success":false,"message":"The avatar must be an image."}
     *
     * @authenticated
     */    
    public function uploadAvatar(Request $request): JsonResponse
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        try {
            $user = $request->user();
            $avatarUrl = $this->profileService->uploadAvatar($user, $request->file('avatar'));

            return response()->json([
                'success' => true,
                'message' => 'Avatar uploaded successfully.',
                'data' => [
                    'avatar_url' => $avatarUrl,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Hapus Foto Profil
     *
     * Menghapus foto profil pengguna dan mengembalikan ke avatar default.
     *
     *
     * @summary Hapus Foto Profil
     * @response 200 scenario="Success" {"success":true,"message":"Avatar deleted successfully."}
     * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
     * @response 422 scenario="Error" {"success":false,"message":"Gagal menghapus avatar."}
     *
     * @authenticated
     */    
    public function deleteAvatar(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $this->profileService->deleteAvatar($user);

            return response()->json([
                'success' => true,
                'message' => 'Avatar deleted successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
