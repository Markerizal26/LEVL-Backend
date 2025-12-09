<?php

namespace Modules\Forums\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * @tags Forum Diskusi
 */
class ForumsController extends Controller
{
    /**
     * Menampilkan daftar data
     *
     *
     * @summary Menampilkan daftar data
     *
     * @response 200 scenario="Success" {"success":true,"message":"Success","data":[{"id":1,"name":"Example Forums"}],"meta":{"current_page":1,"last_page":5,"per_page":15,"total":75},"links":{"first":"...","last":"...","prev":null,"next":"..."}}
     * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
     * @authenticated
     */
    public function index()
    {
        return view('forums::index');
    }

    /**
     * Menampilkan form untuk membuat data baru
     *
     *
     * @summary Menampilkan form untuk membuat data baru
     *
     * @response 200 scenario="Success" {"success":true,"message":"Success","data":{"id":1,"name":"Example Forums"}}
     * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
     * @authenticated
     */
    public function create()
    {
        return view('forums::create');
    }

    /**
     * Menyimpan data baru
     *
     *
     * @summary Menyimpan data baru
     *
     * @response 201 scenario="Success" {"success":true,"message":"Forums berhasil dibuat.","data":{"id":1,"name":"New Forums"}}
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
     * @response 200 scenario="Success" {"success":true,"message":"Success","data":{"id":1,"name":"Example Forums"}}
     * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
     * @response 404 scenario="Not Found" {"success":false,"message":"Forums tidak ditemukan."}
     * @authenticated
     */
    public function show($id)
    {
        return view('forums::show');
    }

    /**
     * Menampilkan form untuk edit data
     *
     *
     * @summary Menampilkan form untuk edit data
     *
     * @response 200 scenario="Success" {"success":true,"message":"Success","data":{"id":1,"name":"Example Forums"}}
     * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
     * @authenticated
     */
    public function edit($id)
    {
        return view('forums::edit');
    }

    /**
     * Memperbarui data
     *
     *
     * @summary Memperbarui data
     *
     * @response 200 scenario="Success" {"success":true,"message":"Forums berhasil diperbarui.","data":{"id":1,"name":"Updated Forums"}}
     * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
     * @response 404 scenario="Not Found" {"success":false,"message":"Forums tidak ditemukan."}
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
     * @response 200 scenario="Success" {"success":true,"message":"Forums berhasil dihapus.","data":[]}
     * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
     * @response 404 scenario="Not Found" {"success":false,"message":"Forums tidak ditemukan."}
     * @authenticated
     */
    public function destroy($id) {}
}
