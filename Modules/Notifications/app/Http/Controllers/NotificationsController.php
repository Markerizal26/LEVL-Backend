<?php

namespace Modules\Notifications\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Modules\Notifications\Services\NotificationsService;

/**
 * @tags Notifikasi
 */
class NotificationsController extends Controller
{
  use ApiResponse;

  public function __construct(private readonly NotificationsService $service) {}

  /**
   * Menampilkan daftar data
   *
   *
   * @summary Menampilkan daftar data
   *
   * @response 200 scenario="Success" {"success":true,"message":"Success","data":[{"id":1,"name":"Example Notifications"}],"meta":{"current_page":1,"last_page":5,"per_page":15,"total":75},"links":{"first":"...","last":"...","prev":null,"next":"..."}}
   * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
   * @authenticated
   */
  public function index()
  {
    return $this->service->render("index");
  }

  /**
   * Menampilkan form untuk membuat data baru
   *
   *
   * @summary Menampilkan form untuk membuat data baru
   *
   * @response 200 scenario="Success" {"success":true,"message":"Success","data":{"id":1,"name":"Example Notifications"}}
   * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
   * @authenticated
   */
  public function create()
  {
    return $this->service->render("create");
  }

  /**
   * Menyimpan data baru
   *
   *
   * @summary Menyimpan data baru
   *
   * @response 201 scenario="Success" {"success":true,"message":"Notifications berhasil dibuat.","data":{"id":1,"name":"New Notifications"}}
   * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
   * @response 422 scenario="Validation Error" {"success":false,"message":"Validasi gagal.","errors":{"field":["Field wajib diisi."]}}
   * @response 501 scenario="Not Implemented" {"success":false,"message":"Fitur belum tersedia."}
   * @authenticated
   */
  public function store(Request $request)
  {
    return $this->error(__("messages.feature_unavailable"), 501);
  }

  /**
   * Menampilkan data tertentu
   *
   *
   * @summary Menampilkan data tertentu
   *
   * @response 200 scenario="Success" {"success":true,"message":"Success","data":{"id":1,"name":"Example Notifications"}}
   * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
   * @response 404 scenario="Not Found" {"success":false,"message":"Notifications tidak ditemukan."}
   * @authenticated
   */
  public function show($id)
  {
    return $this->service->render("show");
  }

  /**
   * Menampilkan form untuk edit data
   *
   *
   * @summary Menampilkan form untuk edit data
   *
   * @response 200 scenario="Success" {"success":true,"message":"Success","data":{"id":1,"name":"Example Notifications"}}
   * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
   * @authenticated
   */
  public function edit($id)
  {
    return $this->service->render("edit");
  }

  /**
   * Memperbarui data
   *
   *
   * @summary Memperbarui data
   *
   * @response 200 scenario="Success" {"success":true,"message":"Notifications berhasil diperbarui.","data":{"id":1,"name":"Updated Notifications"}}
   * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
   * @response 404 scenario="Not Found" {"success":false,"message":"Notifications tidak ditemukan."}
   * @response 422 scenario="Validation Error" {"success":false,"message":"Validasi gagal.","errors":{"field":["Field wajib diisi."]}}
   * @response 501 scenario="Not Implemented" {"success":false,"message":"Fitur belum tersedia."}
   * @authenticated
   */
  public function update(Request $request, string $id)
  {
    return $this->error(__("messages.feature_unavailable"), 501);
  }

  /**
   * Menghapus data
   *
   *
   * @summary Menghapus data
   *
   * @response 200 scenario="Success" {"success":true,"message":"Notifications berhasil dihapus.","data":[]}
   * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
   * @response 404 scenario="Not Found" {"success":false,"message":"Notifications tidak ditemukan."}
   * @response 501 scenario="Not Implemented" {"success":false,"message":"Fitur belum tersedia."}
   * @authenticated
   */
  public function destroy(string $id)
  {
    return $this->error(__("messages.feature_unavailable"), 501);
  }
}
