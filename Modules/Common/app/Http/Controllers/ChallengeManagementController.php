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
use Modules\Common\Http\Requests\ChallengeStoreRequest;
use Modules\Common\Http\Requests\ChallengeUpdateRequest;
use Modules\Common\Http\Resources\CommonChallengeResource;
use Modules\Common\Services\ChallengeManagementService;
use Modules\Gamification\Models\Challenge;

class ChallengeManagementController extends Controller
{
    use ApiResponse;
    use AuthorizesRequests;
    use HandlesFiltering;
    use ProvidesMetadata;

    public function __construct(private readonly ChallengeManagementService $service) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Challenge::class);
        $params = $this->extractFilterParams($request);
        $perPage = $params['per_page'] ?? 15;
        $paginator = $this->service->paginate($perPage, $params);
        $paginator->getCollection()->transform(fn ($item) => new CommonChallengeResource($item));

        return $this->paginateResponse($paginator);
    }

    public function store(ChallengeStoreRequest $request): JsonResponse
    {
        $this->authorize('create', Challenge::class);
        $challenge = $this->service->create($request->validated());

        return $this->created(new CommonChallengeResource($challenge), __('messages.achievements.created'));
    }

    public function show(int $challenge): JsonResponse
    {
        $model = $this->service->find($challenge);

        if (! $model) {
            return $this->error(__('messages.achievements.not_found'), 404);
        }

        $this->authorize('view', $model);

        return $this->success(new CommonChallengeResource($model));
    }

    public function update(ChallengeUpdateRequest $request, int $challenge): JsonResponse
    {
        $model = $this->service->find($challenge);

        if (! $model) {
            return $this->error(__('messages.achievements.not_found'), 404);
        }

        $this->authorize('update', $model);

        $updated = $this->service->update($challenge, $request->validated());

        return $this->success(new CommonChallengeResource($updated), __('messages.achievements.updated'));
    }

    public function destroy(int $challenge): JsonResponse
    {
        $model = $this->service->find($challenge);

        if (! $model) {
            return $this->error(__('messages.achievements.not_found'), 404);
        }

        $this->authorize('delete', $model);

        $deleted = $this->service->delete($challenge);

        if (! $deleted) {
            return $this->error(__('messages.achievements.not_found'), 404);
        }

        return $this->success([], __('messages.achievements.deleted'));
    }
}
