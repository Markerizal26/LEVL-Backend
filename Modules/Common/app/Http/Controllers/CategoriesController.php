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
use Modules\Common\Http\Requests\CategoryStoreRequest;
use Modules\Common\Http\Requests\CategoryUpdateRequest;
use Modules\Common\Http\Resources\CategoryResource;
use Modules\Common\Models\Category;
use Modules\Common\Services\CategoryService;

class CategoriesController extends Controller
{
    use ApiResponse;
    use AuthorizesRequests;
    use HandlesFiltering;
    use ProvidesMetadata;

    public function __construct(private readonly CategoryService $service) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Category::class);
        $params = $this->extractFilterParams($request);
        $perPage = $params['per_page'] ?? 15;
        $paginator = $this->service->paginate($perPage);
        $paginator->getCollection()->transform(fn ($item) => new CategoryResource($item));
        $metadata = $this->buildCategoryMetadata();

        return $this->paginateResponse($paginator, additionalMeta: $metadata);
    }

    public function store(CategoryStoreRequest $request): JsonResponse
    {
        $this->authorize('create', Category::class);
        $category = $this->service->create($request->validated());

        return $this->created(new CategoryResource($category), __('messages.categories.created'));
    }

    public function show(int|string $category): JsonResponse
    {
        $model = $this->service->find($category);

        if (! $model) {
            return $this->error(__('messages.categories.not_found'), 404);
        }

        $this->authorize('view', $model);

        return $this->success(new CategoryResource($model));
    }

    public function update(CategoryUpdateRequest $request, int $category): JsonResponse
    {
        $model = $this->service->find($category);

        if (! $model) {
            return $this->error(__('messages.categories.not_found'), 404);
        }

        $this->authorize('update', $model);
        $updated = $this->service->update($category, $request->validated());

        return $this->success(new CategoryResource($updated), __('messages.categories.updated'));
    }

    public function destroy(int $category): JsonResponse
    {
        $model = $this->service->find($category);

        if (! $model) {
            return $this->error(__('messages.categories.not_found'), 404);
        }

        $this->authorize('delete', $model);
        $deleted = $this->service->delete($category);

        return $this->success([], __('messages.categories.deleted'));
    }

    private function buildCategoryMetadata(): array
    {
        return $this->buildMetadata(
            allowedSorts: ['name', 'value', 'created_at', 'updated_at'],
            filters: [
                'status' => [
                    'label' => __('categories.filters.status'),
                    'type' => 'select',
                    'options' => array_map(
                        fn ($case) => ['value' => $case->value, 'label' => $case->label()],
                        \Modules\Common\Enums\CategoryStatus::cases()
                    ),
                ],
            ]
        );
    }
}
            $this->authorize('view', $model);



        $this->authorize('view', $model);
