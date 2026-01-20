<?php

namespace Modules\Common\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Common\Services\MasterDataService;

/**
 * @tags Data Master
 */
class MasterDataController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly MasterDataService $service
    ) {}

    /**
     * Daftar Tipe Master Data
     *
     * Mengambil daftar semua tipe master data yang tersedia di sistem.
     *
     * @summary Daftar Tipe Master Data
     * @response 200 scenario="Success" {"success":true,"message":"Daftar tipe master data","data":[{"type":"user-status","label":"Status Pengguna"}]}
     * @unauthenticated
     */
    public function types(): JsonResponse
    {
        $types = $this->service->getAvailableTypes();
        return $this->success($types, __("messages.master_data.types_retrieved"));
    }

    /**
     * Get Master Data by Type
     *
     * Mengambil data master berdasarkan tipe yang diminta.
     *
     * @summary Get Master Data by Type
     * @param string $type
     * @return JsonResponse
     */
    public function get(string $type): JsonResponse
    {
        try {
            $data = $this->service->get($type);
            return $this->success($data, __("messages.master_data.retrieved"));
        } catch (\Exception $e) {
            return $this->error(__("messages.master_data.not_found"), 404);
        }
    }

    /**
     * List Items (Pagination)
     *
     * @summary List Items
     * @param Request $request
     * @param string $type
     * @return JsonResponse
     */
    public function index(Request $request, string $type): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 15);
        $paginator = $this->service->paginate($type, $perPage);
        return $this->paginateResponse($paginator, __("messages.master_data.retrieved"));
    }

    /**
     * Show Item
     * 
     * @summary Show Item
     */
    public function show(string $type, int $id): JsonResponse
    {
        $item = $this->service->find($type, $id);
        
        if (!$item) {
             return $this->error(__("messages.master_data.not_found"), 404);
        }
        
        return $this->success($item, __("messages.master_data.retrieved"));
    }

    /**
     * Create Item (Superadmin)
     * 
     * @summary Create Item
     */
    public function store(Request $request, string $type): JsonResponse
    {
        $data = $request->validate([
            'value' => 'required|string|max:255',
            'label' => 'required|string|max:255',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'metadata' => 'nullable|array',
        ]);
        
        $item = $this->service->create($type, $data);
        return $this->success($item, __("messages.master_data.created"));
    }

    /**
     * Update Item (Superadmin)
     * 
     * @summary Update Item
     */
    public function update(Request $request, string $type, int $id): JsonResponse
    {
        $data = $request->validate([
            'value' => 'sometimes|string|max:255',
            'label' => 'sometimes|string|max:255',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'metadata' => 'nullable|array',
        ]);
        
        $item = $this->service->update($type, $id, $data);
        return $this->success($item, __("messages.master_data.updated"));
    }

    /**
     * Delete Item (Superadmin)
     * 
     * @summary Delete Item
     */
    public function destroy(string $type, int $id): JsonResponse
    {
        $this->service->delete($type, $id);
        return $this->success(null, __("messages.master_data.deleted"));
    }
}
