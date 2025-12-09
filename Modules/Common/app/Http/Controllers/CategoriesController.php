<?php

namespace Modules\Common\Http\Controllers;

use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Common\Http\Requests\CategoryStoreRequest;
use Modules\Common\Http\Requests\CategoryUpdateRequest;
use Modules\Common\Services\CategoryService;

/**
 * @tags Data Master
 */
class CategoriesController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly CategoryService $service) {}

    /**
     * Daftar Kategori
     *
     *
     * @summary Daftar Kategori
     * @authenticated

     *
     * @queryParam page integer Halaman yang ingin ditampilkan. Example: 1
     * @queryParam per_page integer Jumlah item per halaman (default: 15, max: 100). Example: 15     */
    public function index(Request $request)
    {
        $perPage = max(1, (int) $request->get('per_page', 15));
        $paginator = $this->service->paginate($perPage);

        return $this->paginateResponse($paginator);
    }

    /**
     * Buat Kategori Baru
     *
     *
     * @summary Buat Kategori Baru
     *
     * @response 201 scenario="Success" {"success":true,"message":"Categories berhasil dibuat.","data":{"id":1,"name":"New Categories"}}
     * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
     * @response 422 scenario="Validation Error" {"success":false,"message":"Validasi gagal.","errors":{"field":["Field wajib diisi."]}}
     * @authenticated
     */
    public function store(CategoryStoreRequest $request)
    {
        $category = $this->service->create($request->validated());

        return $this->created(['category' => $category], 'Kategori dibuat');
    }

    /**
     * Detail Kategori
     *
     *
     * @summary Detail Kategori
     *
     * @response 200 scenario="Success" {"success":true,"message":"Success","data":{"id":1,"name":"Example Categories"}}
     * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
     * @response 404 scenario="Not Found" {"success":false,"message":"Categories tidak ditemukan."}
     * @authenticated
     */
    public function show(int $category)
    {
        $model = $this->service->find($category);
        if (! $model) {
            return $this->error('Kategori tidak ditemukan', 404);
        }

        return $this->success(['category' => $model]);
    }

    /**
     * Perbarui Kategori
     *
     *
     * @summary Perbarui Kategori
     *
     * @response 200 scenario="Success" {"success":true,"message":"Categories berhasil diperbarui.","data":{"id":1,"name":"Updated Categories"}}
     * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
     * @response 404 scenario="Not Found" {"success":false,"message":"Categories tidak ditemukan."}
     * @response 422 scenario="Validation Error" {"success":false,"message":"Validasi gagal.","errors":{"field":["Field wajib diisi."]}}
     * @authenticated
     */
    public function update(CategoryUpdateRequest $request, int $category)
    {
        $updated = $this->service->update($category, $request->validated());
        if (! $updated) {
            return $this->error('Kategori tidak ditemukan', 404);
        }

        return $this->success(['category' => $updated], 'Kategori diperbarui');
    }

    /**
     * Hapus Kategori
     *
     *
     * @summary Hapus Kategori
     *
     * @response 200 scenario="Success" {"success":true,"message":"Categories berhasil dihapus.","data":[]}
     * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
     * @response 404 scenario="Not Found" {"success":false,"message":"Categories tidak ditemukan."}
     * @authenticated
     */
    public function destroy(int $category)
    {
        $deleted = $this->service->delete($category);
        if (! $deleted) {
            return $this->error('Kategori tidak ditemukan', 404);
        }

        return $this->success([], 'Kategori dihapus');
    }
}
