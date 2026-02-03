<?php

declare(strict_types=1);

namespace Modules\Common\Http\Controllers;

use App\Support\ApiResponse;
use App\Support\Traits\HandlesFiltering;
use App\Support\Traits\ProvidesMetadata;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Common\Http\Requests\LevelConfigStoreRequest;
use Modules\Common\Http\Requests\LevelConfigUpdateRequest;
use Modules\Common\Http\Resources\LevelConfigResource;
use Modules\Common\Models\LevelConfig;
use Modules\Common\Services\LevelConfigService;

class LevelConfigsController extends Controller
{
    use ApiResponse;
    use AuthorizesRequests;
    use HandlesFiltering;
    use ProvidesMetadata;

    public function __construct(private readonly LevelConfigService $service) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', LevelConfig::class);
        $params = $this->extractFilterParams($request);
        $perPage = $params['per_page'] ?? 15;
        $paginator = $this->service->paginate($perPage, $params);
        $paginator->getCollection()->transform(fn ($item) => new LevelConfigResource($item));

        return $this->paginateResponse($paginator);
    }

    public function store(LevelConfigStoreRequest $request): JsonResponse
    {
        $this->authorize('create', LevelConfig::class);
        $levelConfig = $this->service->create($request->validated());

        return $this->created(new LevelConfigResource($levelConfig), __('messages.level_configs.created'));
    }

    public function show(int $levelConfig): JsonResponse
    {
        $model = $this->service->find($levelConfig);

        if (! $model) {
            return $this->error(__('messages.level_configs.not_found'), 404);
        }

        $this->authorize('view', $model);

        return $this->success(new LevelConfigResource($model));
    }

    public function update(LevelConfigUpdateRequest $request, int $levelConfig): JsonResponse
    {
        $model = $this->service->find($levelConfig);

        if (! $model) {
            return $this->error(__('messages.level_configs.not_found'), 404);
        }

        $this->authorize('update', $model);

        $updated = $this->service->update($levelConfig, $request->validated());

        return $this->success(new LevelConfigResource($updated), __('messages.level_configs.updated'));
    }

    public function destroy(int $levelConfig): JsonResponse
    {
        $model = $this->service->find($levelConfig);

        if (! $model) {
            return $this->error(__('messages.level_configs.not_found'), 404);
        }

        $this->authorize('delete', $model);

        $deleted = $this->service->delete($levelConfig);

        if (! $deleted) {
            return $this->error(__('messages.level_configs.not_found'), 404);
        }

        return $this->success([], __('messages.level_configs.deleted'));
    }
}
