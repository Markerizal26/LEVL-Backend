<?php

declare(strict_types=1);

namespace Modules\Schemes\Http\Controllers;

use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Schemes\Http\Requests\UnitRequest;
use Modules\Schemes\Http\Resources\UnitResource;
use Modules\Schemes\Models\Course;
use Modules\Schemes\Models\Unit;
use Modules\Schemes\Services\UnitService;

class UnitController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly UnitService $service) {}

    public function index(Request $request, Course $course)
    {
        $this->authorize('view', $course);
        $paginator = $this->service->paginate(
            $course->id,
            $request->query('filter', []),
            (int) $request->query('per_page', 15)
        );

        $paginator->setCollection(UnitResource::collection($paginator->getCollection()));
        return $this->paginateResponse($paginator);
    }

    public function store(UnitRequest $request, Course $course)
    {
        $this->authorize('update', $course);
        $unit = $this->service->create($course->id, $request->validated());

        return $this->created(new UnitResource($unit), __('messages.units.created'));
    }

    public function show(Course $course, Unit $unit)
    {
        $this->service->validateHierarchy($course->id, $unit->id);
        $this->authorize('view', $unit);

        return $this->success(new UnitResource($unit->load('lessons')));
    }

    public function update(UnitRequest $request, Course $course, Unit $unit)
    {
        $this->service->validateHierarchy($course->id, $unit->id);
        $this->authorize('update', $unit);

        $updated = $this->service->update($unit->id, $request->validated());
        return $this->success(new UnitResource($updated), __('messages.units.updated'));
    }

    public function destroy(Course $course, Unit $unit)
    {
        $this->service->validateHierarchy($course->id, $unit->id);
        $this->authorize('delete', $unit);

        $this->service->delete($unit->id);
        return $this->success([], __('messages.units.deleted'));
    }

    public function publish(Course $course, Unit $unit)
    {
        $this->service->validateHierarchy($course->id, $unit->id);
        $this->authorize('update', $unit);

        $updated = $this->service->publish($unit->id);
        return $this->success(new UnitResource($updated), __('messages.units.published'));
    }

    public function unpublish(Course $course, Unit $unit)
    {
        $this->service->validateHierarchy($course->id, $unit->id);
        $this->authorize('update', $unit);

        $updated = $this->service->unpublish($unit->id);
        return $this->success(new UnitResource($updated), __('messages.units.unpublished'));
    }

    public function reorder(Request $request, Course $course)
    {
        $this->authorize('update', $course);
        $this->service->reorder($course->id, $request->all());

        return $this->success([], __('messages.units.reordered'));
    }
}
