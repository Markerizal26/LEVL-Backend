<?php

declare(strict_types=1);

namespace Modules\Schemes\Http\Controllers;

use App\Exceptions\DuplicateResourceException;
use App\Support\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Schemes\Contracts\Services\CourseServiceInterface;
use Modules\Schemes\Http\Requests\CourseRequest;
use Modules\Schemes\Http\Requests\PublishCourseRequest;
use Modules\Schemes\Http\Resources\CourseResource;
use Modules\Schemes\Models\Course;

class CourseController extends Controller
{
    use ApiResponse, AuthorizesRequests;

    public function __construct(
        private readonly CourseServiceInterface $service,
    ) {}

    public function index(Request $request)
    {
        $paginator = $this->service->list(
            $request->query('filter', []),
            (int) $request->query('per_page', 15)
        );
        $paginator->setCollection(CourseResource::collection($paginator->getCollection()));
        return $this->paginateResponse($paginator);
    }

    public function store(CourseRequest $request)
    {
        $data = $request->validated();
        $actor = auth('api')->user();

        try {
            $course = $this->service->create($data, $actor, $request->allFiles());
            return $this->created(new CourseResource($course), __('messages.courses.created'));
        } catch (DuplicateResourceException $e) {
            return $this->validationError($e->getErrors());
        }
    }

    public function show(Course $course)
    {
        return $this->success(new CourseResource($course->load(['tags', 'outcomes'])));
    }

    public function update(CourseRequest $request, Course $course)
    {
        $this->authorize('update', $course);

        try {
            $updated = $this->service->update($course->id, $request->validated(), $request->allFiles());
            return $this->success(new CourseResource($updated), __('messages.courses.updated'));
        } catch (DuplicateResourceException $e) {
            return $this->validationError($e->getErrors());
        }
    }

    public function destroy(Course $course)
    {
        $this->authorize('delete', $course);
        $this->service->delete($course->id);

        return $this->success([], __('messages.courses.deleted'));
    }

    public function publish(Course $course)
    {
        $this->authorize('update', $course);

        $updated = $this->service->publish($course->id);

        return $this->success(new CourseResource($updated), __('messages.courses.published'));
    }

    public function unpublish(Course $course)
    {
        $this->authorize('update', $course);

        $updated = $this->service->unpublish($course->id);

        return $this->success(new CourseResource($updated), __('messages.courses.unpublished'));
    }

    public function generateEnrollmentKey(Course $course)
    {
        $this->authorize('update', $course);
        $result = $this->service->updateEnrollmentSettings($course->id, ['enrollment_type' => 'key_based']);

        return $this->success(
            ['course' => new CourseResource($result['course']), 'enrollment_key' => $result['enrollment_key']],
            __('messages.courses.key_generated'),
        );
    }

    public function updateEnrollmentKey(PublishCourseRequest $request, Course $course)
    {
        $this->authorize('update', $course);
        $result = $this->service->updateEnrollmentSettings($course->id, $request->validated());

        return $this->success(
            ['course' => new CourseResource($result['course']), 'enrollment_key' => $result['enrollment_key']],
            __('messages.courses.enrollment_settings_updated')
        );
    }

    public function removeEnrollmentKey(Course $course)
    {
        $this->authorize('update', $course);
        $result = $this->service->updateEnrollmentSettings($course->id, ['enrollment_type' => 'auto_accept']);

        return $this->success(new CourseResource($result['course']), __('messages.courses.key_removed'));
    }
}
