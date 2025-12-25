<?php

namespace Modules\Auth\Http\Controllers;

use App\Contracts\Services\ProfileServiceInterface;
use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Auth\Contracts\Repositories\ProfileAuditLogRepositoryInterface;
use Modules\Auth\Models\User;

/**
 * @tags Manajemen Pengguna
 */
class AdminProfileController extends Controller
{
    use ApiResponse;
    public function __construct(
        private ProfileServiceInterface $profileService,
        private ProfileAuditLogRepositoryInterface $auditLogRepository
    ) {
        $this->middleware('role:Admin');
    }

    /**
     * Lihat Profil Pengguna (Admin)
     *
     *
     * @summary Lihat Profil Pengguna (Admin)
     *
     * @response 200 scenario="Success" {"success":true,"message":"Success","data":{"id":1,"name":"Example AdminProfile"}}
     * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
     * @response 404 scenario="Not Found" {"success":false,"message":"AdminProfile tidak ditemukan."}
     *
     * @authenticated
     */
    public function show(Request $request, int $userId): JsonResponse
    {
        $user = User::findOrFail($userId);
        $profileData = $this->profileService->getProfileData($user, $request->user());

        return $this->success($profileData, __('messages.success'));
    }

    /**
     * Perbarui Profil Pengguna (Admin)
     *
     *
     * @summary Perbarui Profil Pengguna (Admin)
     *
     * @response 200 scenario="Success" {"success":true,"message":"AdminProfile berhasil diperbarui.","data":{"id":1,"name":"Updated AdminProfile"}}
     * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
     * @response 404 scenario="Not Found" {"success":false,"message":"AdminProfile tidak ditemukan."}
     * @response 422 scenario="Validation Error" {"success":false,"message":"Validasi gagal.","errors":{"field":["Field wajib diisi."]}}
     *
     * @authenticated
     */
    public function update(Request $request, int $userId): JsonResponse
    {
        $request->validate([
            'name' => 'sometimes|string|max:100',
            'email' => "sometimes|email|unique:users,email,{$userId}",
            'phone' => 'sometimes|nullable|string|max:20',
            'bio' => 'sometimes|nullable|string|max:1000',
            'account_status' => 'sometimes|in:active,suspended,deleted',
        ]);

        $user = User::findOrFail($userId);
        $admin = $request->user();

        $oldData = $user->only(['name', 'email', 'phone', 'bio', 'account_status']);

        $updatedUser = $this->profileService->updateProfile($user, $request->all());

        // Log admin action
        $this->auditLogRepository->create([
            'user_id' => $user->id,
            'admin_id' => $admin->id,
            'action' => 'profile_updated',
            'changes' => [
                'old' => $oldData,
                'new' => $updatedUser->only(['name', 'email', 'phone', 'bio', 'account_status']),
            ],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return $this->success(
            $this->profileService->getProfileData($updatedUser, $admin),
            __('messages.profile.updated_success')
        );
    }

    /**
     * Tangguhkan Akun Pengguna
     *
     *
     * @summary Tangguhkan Akun Pengguna
     *
     * @response 200 scenario="Success" {"success":true,"message":"Success","data":{"id":1,"name":"Example AdminProfile"}}
     * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
     *
     * @authenticated
     */
    public function suspend(Request $request, int $userId): JsonResponse
    {
        $user = User::findOrFail($userId);
        $admin = $request->user();

        $user->account_status = 'suspended';
        $user->save();

        // Log admin action
        $this->auditLogRepository->create([
            'user_id' => $user->id,
            'admin_id' => $admin->id,
            'action' => 'account_suspended',
            'changes' => ['status' => 'suspended'],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return $this->success([], __('messages.profile.suspended_success'));
    }

    /**
     * Aktifkan Akun Pengguna
     *
     *
     * @summary Aktifkan Akun Pengguna
     *
     * @response 200 scenario="Success" {"success":true,"message":"Success","data":{"id":1,"name":"Example AdminProfile"}}
     * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
     *
     * @authenticated
     */
    public function activate(Request $request, int $userId): JsonResponse
    {
        $user = User::findOrFail($userId);
        $admin = $request->user();

        $user->account_status = 'active';
        $user->save();

        // Log admin action
        $this->auditLogRepository->create([
            'user_id' => $user->id,
            'admin_id' => $admin->id,
            'action' => 'account_activated',
            'changes' => ['status' => 'active'],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return $this->success([], __('messages.profile.activated_success'));
    }

    /**
     * Riwayat Audit Pengguna
     *
     *
     * @summary Riwayat Audit Pengguna
     *
     * @authenticated

     *
     * @queryParam page integer Halaman yang ingin ditampilkan. Example: 1
     * @queryParam per_page integer Jumlah item per halaman (default: 15, max: 100). Example: 15     */
    public function auditLogs(Request $request, int $userId): JsonResponse
    {
        $logs = $this->auditLogRepository->findByUserId($userId, 20);

        return $this->success($logs, __('messages.success'));
    }
}
