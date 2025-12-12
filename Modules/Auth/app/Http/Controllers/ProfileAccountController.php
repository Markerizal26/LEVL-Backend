<?php

namespace Modules\Auth\Http\Controllers;

use App\Contracts\Services\ProfileServiceInterface;
use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Modules\Auth\Http\Requests\DeleteAccountRequest;

/**
 * @tags Profil Pengguna
 */
class ProfileAccountController extends Controller
{
  use ApiResponse;

  public function __construct(private ProfileServiceInterface $profileService) {}

  /**
   * Hapus Akun Sendiri
   *
   *
   * @summary Hapus Akun Sendiri
   *
   * @response 200 scenario="Success" {"success":true,"message":"ProfileAccount berhasil dihapus.","data":[]}
   * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
   * @response 404 scenario="Not Found" {"success":false,"message":"ProfileAccount tidak ditemukan."}
   *
   * @authenticated
   */
  public function destroy(DeleteAccountRequest $request): JsonResponse
  {
    $user = $request->user();

    $this->profileService->deleteAccount($user, $request->input("password"));

    return $this->success(
      null,
      "Akun berhasil dihapus. Anda memiliki 30 hari untuk memulihkannya.",
    );
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
   *
   * @authenticated
   */
  public function restore(DeleteAccountRequest $request): JsonResponse
  {
    $user = $request->user();

    $this->profileService->restoreAccount($user);

    return $this->success(null, __("messages.account.restore_success"));
  }
}
