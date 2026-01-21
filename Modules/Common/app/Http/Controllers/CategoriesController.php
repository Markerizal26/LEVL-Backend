<?php

declare(strict_types=1);

namespace Modules\Common\Http\Controllers;

use App\Support\ApiResponse;
use App\Support\Traits\HandlesFiltering;
use App\Support\Traits\ProvidesMetadata;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Common\Http\Requests\CategoryStoreRequest;
use Modules\Common\Http\Requests\CategoryUpdateRequest;
use Modules\Common\Services\CategoryService;

class CategoriesController extends Controller
{
  use ApiResponse;
  use HandlesFiltering;
  use ProvidesMetadata;

  public function __construct(private readonly CategoryService $service) {}

  public function index(Request $request)
  {
    $params = $this->extractFilterParams($request);
    $perPage = $params["per_page"] ?? 15;

    $paginator = $this->service->paginate($perPage);
    
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


  public function store(CategoryStoreRequest $request)
  {
    $category = $this->service->create($request->validated());

    return $this->created(new \Modules\Common\Http\Resources\CategoryResource($category), __("messages.categories.created"));
  }

  public function show(int|string $category)
  {
    $model = $this->service->find($category);
    if (!$model) {
      return $this->error(__("messages.categories.not_found"), 404);
    }

    return $this->success(new \Modules\Common\Http\Resources\CategoryResource($model));
  }

  public function update(CategoryUpdateRequest $request, int $category)
  {
    $updated = $this->service->update($category, $request->validated());
    if (!$updated) {
      return $this->error(__("messages.categories.not_found"), 404);
    }

    return $this->success(new \Modules\Common\Http\Resources\CategoryResource($updated), __("messages.categories.updated"));
  }

  public function destroy(int $category)
  {
    $deleted = $this->service->delete($category);
    if (!$deleted) {
      return $this->error(__("messages.categories.not_found"), 404);
    }

    return $this->success([], __("messages.categories.deleted"));
  }
}
