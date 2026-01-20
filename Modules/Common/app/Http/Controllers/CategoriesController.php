<?php

namespace Modules\Common\Http\Controllers;

use App\Support\ApiResponse;
use App\Support\Traits\HandlesFiltering;
use App\Support\Traits\ProvidesMetadata;
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
  use HandlesFiltering;
  use ProvidesMetadata;

  public function __construct(private readonly CategoryService $service) {}

  /**
     * Daftar Kategori
     *
     *
     * @summary Daftar Kategori
     *
     * @authenticated

     *
     * @queryParam page integer Halaman yang ingin ditampilkan. Example: 1
     * @queryParam per_page integer Jumlah item per halaman (default: 15, max: 100). Example: 15     */
  public function index(Request $request)
  {
    $params = $this->extractFilterParams($request);
    $perPage = $params["per_page"] ?? 15;

    $paginator = $this->service->paginate($perPage);
    
    // Transform collection to Resource
    $paginator->getCollection()->transform(fn($item) => new \Modules\Common\Http\Resources\CategoryResource($item));

    $metadata = $this->buildMetadata(
        allowedSorts: ['name', 'value', 'created_at', 'updated_at'],
        filters: [
            'status' => [
                'label' => __('categories.filters.status'),
                'type' => 'select',
                'options' => array_map(
                    fn($case) => ['value' => $case->value, 'label' => $case->label()],
                    \Modules\Common\Enums\CategoryStatus::cases()
                ),
            ],
        ]
    );

    return $this->paginateResponse($paginator, additionalMeta: $metadata);
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
   *
   * @authenticated
   */
  public function store(CategoryStoreRequest $request)
  {
    $category = $this->service->create($request->validated());

    return $this->created(new \Modules\Common\Http\Resources\CategoryResource($category), __("messages.categories.created"));
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
   *
   * @authenticated
   */
  public function show(int|string $category)
  {
    $model = $this->service->find($category);
    if (!$model) {
      return $this->error(__("messages.categories.not_found"), 404);
    }

    return $this->success(new \Modules\Common\Http\Resources\CategoryResource($model));
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
   *
   * @authenticated
   */
  public function update(CategoryUpdateRequest $request, int $category)
  {
    $updated = $this->service->update($category, $request->validated());
    if (!$updated) {
      return $this->error(__("messages.categories.not_found"), 404);
    }

    return $this->success(new \Modules\Common\Http\Resources\CategoryResource($updated), __("messages.categories.updated"));
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
   *
   * @authenticated
   */
  public function destroy(int $category)
  {
    $deleted = $this->service->delete($category);
    if (!$deleted) {
      return $this->error(__("messages.categories.not_found"), 404);
    }

    return $this->success([], __("messages.categories.deleted"));
  }
}
