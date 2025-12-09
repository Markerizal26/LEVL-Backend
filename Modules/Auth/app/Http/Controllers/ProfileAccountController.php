<?php

namespace Modules\Auth\Http\Controllers;

use App\Contracts\Services\ProfileServiceInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Auth\Http\Requests\DeleteAccountRequest;

/**
 * @tags Profil Pengguna
 */
class ProfileAccountController extends Controller
{
    public function __construct(
        private ProfileServiceInterface $profileService
    ) {}

    /**
     * Hapus Akun Sendiri
     *
     *
     * @summary Hapus Akun Sendiri
     *
     * @response 200 scenario="Success" {"success":true,"message":"ProfileAccount berhasil dihapus.","data":[]}
     * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
     * @response 404 scenario="Not Found" {"success":false,"message":"ProfileAccount tidak ditemukan."}
     * @authenticated
     */
    public function destroy(DeleteAccountRequest $request): JsonResponse
    {
        try {
            $user = $request->user();

            $this->profileService->deleteAccount($user, $request->input('password'));

            return response()->json([
                'success' => true,
                'message' => 'Account deleted successfully. You have 30 days to restore it.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Pulihkan Akun yang Dihapus
     *
     *
     * @summary Pulihkan Akun yang Dihapus
     *
     * @response 201 scenario="Success" {"success":true,"message":"ProfileAccount berhasil dibuat.","data":{"id":1,"name":"New ProfileAccount"}}
     * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
     * @response 422 scenario="Validation Error" {"success":false,"message":"Validasi gagal.","errors":{"field":["Field wajib diisi."]}}
     * @authenticated
     */
    public function restore(DeleteAccountRequest $request): JsonResponse
    {
        try {
            $user = $request->user();

            $this->profileService->restoreAccount($user);

            return response()->json([
                'success' => true,
                'message' => 'Account restored successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
