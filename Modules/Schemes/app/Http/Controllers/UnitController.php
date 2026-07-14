<?php

declare(strict_types=1);

namespace Modules\Schemes\Http\Controllers;

use App\Support\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Schemes\Http\Requests\UnitRequest;
use Modules\Schemes\Http\Resources\UnitResource;
use Modules\Schemes\Jobs\DeleteUnitJob;
use Modules\Schemes\Models\Course;
use Modules\Schemes\Models\Unit;
use Modules\Schemes\Services\UnitService;

class UnitController extends Controller
{
    use ApiResponse, AuthorizesRequests, \Modules\Schemes\Traits\ValidatesEnrollment;

    public function __construct(private readonly UnitService $service) {}

    public function index(Request $request, Course $course)
    {
        
        if ($course->status !== \Modules\Schemes\Enums\CourseStatus::Published) {
            $user = auth('api')->user();
            if (! $user) {
                throw new \Illuminate\Auth\Access\AuthorizationException(__('messages.forbidden'));
            }
            \Illuminate\Support\Facades\Gate::forUser($user)->authorize('viewUnits', $course);
        }

        $paginator = $this->service->paginate(
            $course->id,
            $request->query('filter', []),
            (int) $request->query('per_page', 15)
        );

        
        $enrollment = $this->getActiveEnrollment($course);

        
        $paginator->getCollection()->transform(fn ($unit) => new UnitResource($unit, $enrollment));

        return $this->paginateResponse($paginator, 'messages.units.list_retrieved');
    }

    public function store(UnitRequest $request, Course $course)
    {
        $this->authorize('manageContent', $course);
        $unit = $this->service->create($course->id, $request->validated());

        return $this->created(new UnitResource($unit), __('messages.units.created'));
    }

    public function show(Course $course, Unit $unit)
    {
        $this->service->validateHierarchy($course->id, $unit->id);
        $this->authorize('view', $unit);

        $unitWithIncludes = $this->service->findWithIncludes($unit->id);

        return $this->success(new UnitResource($unitWithIncludes));
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

        DeleteUnitJob::dispatch($unit->id, auth('api')->id());

        return $this->success(
            [
                'queued' => true,
                'unit_id' => $unit->id,
            ],
            'messages.units.delete_queued',
            [],
            202
        );
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
        $this->authorize('manageContent', $course);
        $this->service->reorder($course->id, $request->all());

        return $this->success([], __('messages.units.reordered'));
    }

    public function contents(Course $course, Unit $unit)
    {
        $this->service->validateHierarchy($course->id, $unit->id);

        
        if ($error = $this->requireEnrollment($course)) {
            return $error;
        }

        $user = auth('api')->user();
        $contents = $this->service->getContents($unit, $user);

        return $this->success($contents);
    }

    public function storeContent(\Modules\Schemes\Http\Requests\StoreContentRequest $request, Course $course, Unit $unit)
    {
        $this->service->validateHierarchy($course->id, $unit->id);
        $this->authorize('update', $unit);

        $contentService = app(\Modules\Schemes\Services\ContentService::class);
        $createdContent = $contentService->createContent($unit, $request->validated(), (int) auth('api')->id());

        return $this->created($createdContent, __('messages.content.created'));
    }

    public function indexAll(Request $request)
    {
        $user = auth('api')->user();

        $paginator = $this->service->paginateAll(
            [],
            (int) $request->query('per_page', 15),
            $user
        );

        $paginator->getCollection()->transform(fn ($unit) => new UnitResource($unit));

        return $this->paginateResponse($paginator, 'messages.units.list_retrieved');
    }

    public function showGlobal(Unit $unit)
    {
        $this->authorize('view', $unit);

        $unitWithIncludes = $this->service->findWithIncludes($unit->id);

        return $this->success(new UnitResource($unitWithIncludes));
    }

    public function getContentOrder(Course $course, Unit $unit)
    {
        $this->service->validateHierarchy($course->id, $unit->id);
        $this->authorize('view', $unit);

        $content = $this->service->getContentOrder($unit);

        return $this->success($content, __('messages.units.content_order_retrieved'));
    }

    public function reorderContent(\Modules\Schemes\Http\Requests\ReorderUnitContentRequest $request, Course $course, Unit $unit)
    {
        $this->service->validateHierarchy($course->id, $unit->id);
        $this->authorize('update', $unit);

        $content = $this->service->reorderContent($unit, $request->validated('content'));

        return $this->success($content, __('messages.units.content_reordered'));
    }

    public function storeGlobal(\Modules\Schemes\Http\Requests\GlobalUnitRequest $request)
    {
        $validated = $request->validated();
        $courseSlug = $validated['course_slug'];

        $course = Course::where('slug', $courseSlug)->firstOrFail();
        $this->authorize('manageContent', $course);

        $unit = $this->service->create($course->id, $validated);

        
        return $this->created([
            'unit' => [
                'id' => $unit->id,
                'course_slug' => $course->slug,
                'course_name' => $course->title,
                'code' => $unit->code,
                'slug' => $unit->slug,
                'title' => $unit->title,
                'description' => $unit->description,
                'order' => $unit->order,
                'status' => $unit->status,
                'created_at' => $unit->created_at?->toIso8601String(),
                'updated_at' => $unit->updated_at?->toIso8601String(),
            ],
        ], __('messages.units.created'));
    }

    public function generateSlug(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
        ]);

        $slug = $this->service->generateUniqueSlug($request->input('title'));

        return $this->success(
            $slug,
            __('messages.units.slug_generated')
        );
    }

    public function getContentMetadata(Request $request, string $contentId)
    {
        $metadataService = app(\Modules\Schemes\Services\ContentMetadataService::class);
        $type = $request->query('type');
        $isSlug = ! is_numeric($contentId);

        try {
            if ($isSlug) {
                $metadata = $metadataService->getContentMetadataBySlug(
                    $contentId,
                    ($type && in_array($type, ['lesson', 'assignment', 'quiz'])) ? $type : null
                );
            } elseif ($type && in_array($type, ['lesson', 'assignment', 'quiz'])) {
                $metadata = $metadataService->getContentMetadata((int) $contentId, $type);
            } else {
                $metadata = $metadataService->getContentMetadataByIdOnly((int) $contentId);
            }
            
            return $this->success($metadata, __('messages.content.metadata_retrieved'));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->error(__('messages.content.not_found'), [], 404);
        }
    }
}
