<?php

declare(strict_types=1);

namespace Modules\Common\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Common\Services\MasterDataService;

class MasterDataController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly MasterDataService $service
    ) {}

    public function types(Request $request): JsonResponse
    {
        $params = [
            'search' => $request->query('search'),
            'sort' => $request->query('sort'),
            'sort_order' => $request->query('sort_order'),
            'page' => $request->query('page'),
            'per_page' => $request->query('per_page'),
            'filter' => $request->query('filter'),
        ];
        
        $paginator = $this->service->getAvailableTypes($params);
        $paginator->getCollection()->transform(fn($item) => new \Modules\Common\Http\Resources\MasterDataTypeResource($item));
        
        return $this->paginateResponse($paginator, __("messages.master_data.types_retrieved"));
    }

    public function get(string $type): JsonResponse
    {
        try {
            $data = $this->service->get($type);
            return $this->success($data, __("messages.master_data.retrieved"));
        } catch (\Exception $e) {
            return $this->error(__("messages.master_data.not_found"), 404);
        }
    }

    public function index(Request $request, string $type): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 15);
        $paginator = $this->service->paginate($type, $perPage);
        return $this->paginateResponse($paginator, __("messages.master_data.retrieved"));
    }

    public function show(string $type, int $id): JsonResponse
    {
        $item = $this->service->find($type, $id);
        
        if (!$item) {
             return $this->error(__("messages.master_data.not_found"), 404);
        }
        
        return $this->success($item, __("messages.master_data.retrieved"));
    }

    public function store(Request $request, string $type): JsonResponse
    {
        if (!$this->service->isCrudAllowed($type)) {
            return $this->forbidden(__("messages.master_data.crud_not_allowed"));
        }

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

    public function update(Request $request, string $type, int $id): JsonResponse
    {
        if (!$this->service->isCrudAllowed($type)) {
            return $this->forbidden(__("messages.master_data.crud_not_allowed"));
        }

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

    public function destroy(string $type, int $id): JsonResponse
    {
        if (!$this->service->isCrudAllowed($type)) {
            return $this->forbidden(__("messages.master_data.crud_not_allowed"));
        }

        $this->service->delete($type, $id);
        return $this->success(null, __("messages.master_data.deleted"));
    }
}
