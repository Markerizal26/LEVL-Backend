<?php

namespace Modules\Grading\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Grading\Services\GradingService;

/**
 * @tags Penilaian
 */
class GradingController extends Controller
{
    public function __construct(private readonly GradingService $service) {}

    /**
     * Menampilkan daftar data
     *
     *
     * @summary Menampilkan daftar data
     *
     * @response 200 scenario="Success" {"success":true,"message":"Success","data":[{"id":1,"name":"Example Grading"}],"meta":{"current_page":1,"last_page":5,"per_page":15,"total":75},"links":{"first":"...","last":"...","prev":null,"next":"..."}}
     * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
     * @authenticated
     */
    public function index()
    {
        return $this->service->render('index');
    }

    /**
     * Menampilkan form untuk membuat data baru
     *
     *
     * @summary Menampilkan form untuk membuat data baru
     *
     * @response 200 scenario="Success" {"success":true,"message":"Success","data":{"id":1,"name":"Example Grading"}}
     * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
     * @authenticated
     */
    public function create()
    {
        return $this->service->render('create');
    }

    /**
     * Menyimpan data baru
     *
     *
     * @summary Menyimpan data baru
     *
     * @response 201 scenario="Success" {"success":true,"message":"Grading berhasil dibuat.","data":{"id":1,"name":"New Grading"}}
     * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
     * @response 422 scenario="Validation Error" {"success":false,"message":"Validasi gagal.","errors":{"field":["Field wajib diisi."]}}
     * @authenticated
     */
    public function store(Request $request) {}

    /**
     * Menampilkan data tertentu
     *
     *
     * @summary Menampilkan data tertentu
     *
     * @response 200 scenario="Success" {"success":true,"message":"Success","data":{"id":1,"name":"Example Grading"}}
     * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
     * @response 404 scenario="Not Found" {"success":false,"message":"Grading tidak ditemukan."}
     * @authenticated
     */
    public function show($id)
    {
        return $this->service->render('show');
    }

    /**
     * Menampilkan form untuk edit data
     *
     *
     * @summary Menampilkan form untuk edit data
     *
     * @response 200 scenario="Success" {"success":true,"message":"Success","data":{"id":1,"name":"Example Grading"}}
     * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
     * @authenticated
     */
    public function edit($id)
    {
        return $this->service->render('edit');
    }

    /**
     * Memperbarui data
     *
     *
     * @summary Memperbarui data
     *
     * @response 200 scenario="Success" {"success":true,"message":"Grading berhasil diperbarui.","data":{"id":1,"name":"Updated Grading"}}
     * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
     * @response 404 scenario="Not Found" {"success":false,"message":"Grading tidak ditemukan."}
     * @response 422 scenario="Validation Error" {"success":false,"message":"Validasi gagal.","errors":{"field":["Field wajib diisi."]}}
     * @authenticated
     */
    public function update(Request $request, $id) {}

    /**
     * Menghapus data
     *
     *
     * @summary Menghapus data
     *
     * @response 200 scenario="Success" {"success":true,"message":"Grading berhasil dihapus.","data":[]}
     * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
     * @response 404 scenario="Not Found" {"success":false,"message":"Grading tidak ditemukan."}
     * @authenticated
     */
    public function destroy($id) {}
}
