<?php

declare(strict_types=1);

namespace Modules\Schemes\Http\Controllers;

use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Enrollments\Contracts\Services\EnrollmentServiceInterface;
use Modules\Schemes\Http\Requests\LessonRequest;
use Modules\Schemes\Http\Resources\LessonResource;
use Modules\Schemes\Models\Course;
use Modules\Schemes\Models\Lesson;
use Modules\Schemes\Models\Unit;
use Modules\Schemes\Services\LessonService;
use Modules\Schemes\Services\ProgressionService;

class LessonController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly LessonService $service,
        private readonly ProgressionService $progression,
        private readonly EnrollmentServiceInterface $enrollmentService,
    ) {}

    public function index(Request $request, Course $course, Unit $unit)
    {
        $this->service->validateHierarchy($course->id, $unit->id, null);
        $this->authorize('view', $unit);

        $paginator = $this->service->paginate(
            $unit->id,
            $request->query('filter', []),
            (int) $request->query('per_page', 15)
        );
        $paginator->setCollection(LessonResource::collection($paginator->getCollection()));
        return $this->paginateResponse($paginator);
    }

    public function store(LessonRequest $request, Course $course, Unit $unit)
    {
        $this->service->validateHierarchy($course->id, $unit->id, null);
        $this->authorize('update', $unit);

        $lesson = $this->service->create($unit->id, $request->validated());
        return $this->created(new LessonResource($lesson), __('messages.lessons.created'));
    }

    public function show(Course $course, Unit $unit, Lesson $lesson)
    {
        $this->service->validateHierarchy($course->id, $unit->id, $lesson->id);
        $this->authorize('view', $lesson);

        $result = $this->service->getLessonForUser($lesson, $course, auth('api')->user());
        return $this->success(new LessonResource($result));
    }

    public function update(LessonRequest $request, Course $course, Unit $unit, Lesson $lesson)
    {
        $this->service->validateHierarchy($course->id, $unit->id, $lesson->id);
        $this->authorize('update', $lesson);

        $updated = $this->service->update($lesson->id, $request->validated());
        return $this->success(new LessonResource($updated), __('messages.lessons.updated'));
    }

    public function destroy(Course $course, Unit $unit, Lesson $lesson)
    {
        $this->service->validateHierarchy($course->id, $unit->id, $lesson->id);
        $this->authorize('delete', $lesson);

        $this->service->delete($lesson->id);
        return $this->success([], __('messages.lessons.deleted'));
    }

    public function publish(Course $course, Unit $unit, Lesson $lesson)
    {
        $this->service->validateHierarchy($course->id, $unit->id, $lesson->id);
        $this->authorize('update', $lesson);

        $updated = $this->service->publish($lesson->id);
        return $this->success(new LessonResource($updated), __('messages.lessons.published'));
    }

    public function unpublish(Course $course, Unit $unit, Lesson $lesson)
    {
        $this->service->validateHierarchy($course->id, $unit->id, $lesson->id);
        $this->authorize('update', $lesson);

        $updated = $this->service->unpublish($lesson->id);
        return $this->success(new LessonResource($updated), __('messages.lessons.unpublished'));
    }
}
