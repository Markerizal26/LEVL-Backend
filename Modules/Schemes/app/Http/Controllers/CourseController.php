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
use Modules\Schemes\Jobs\DeleteCourseJob;
use Modules\Schemes\Models\Course;
use Modules\Schemes\Services\UnitService;

class CourseController extends Controller
{
    use ApiResponse, AuthorizesRequests;

    public function __construct(
        private readonly CourseServiceInterface $service,
        private readonly UnitService $unitService,
    ) {
    }

    public function index(Request $request)
    {

        if ($request->query('all') === 'true' || $request->query('all') === true) {
            $courses = $this->service->listAll($request->all());
            $courses = $courses->map(fn($course) => new \Modules\Schemes\Http\Resources\CourseIndexResource($course));

            return $this->success($courses, __('messages.courses.list_retrieved'));
        }

        $paginator = $this->service->listForIndex(
            $request->all(),
            (int) $request->query('per_page', 15)
        );
        $paginator->getCollection()->transform(fn($course) => new \Modules\Schemes\Http\Resources\CourseIndexResource($course));

        return $this->paginateResponse($paginator, 'messages.courses.list_retrieved');
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

    public function show(Request $request, Course $course)
    {
        $user = auth('api')->user();
        $userId = $user?->id;

        if (!$this->service->canAccessCourse($userId, $course)) {
            return $this->forbidden(__('messages.courses.not_found'));
        }

        $includes = $request->query('include', '');
        $requestedIncludes = array_filter(explode(',', $includes));


        $allowedIncludes = $this->service->filterIncludesByEnrollment($userId, $course, $requestedIncludes);


        $hasElementsInclude = in_array('elements', $allowedIncludes);


        $filteredIncludes = array_diff($allowedIncludes, ['elements']);


        if ($hasElementsInclude && !in_array('units', $filteredIncludes)) {
            $filteredIncludes[] = 'units';
        }


        $courseWithIncludes = $this->service->findBySlugWithFilteredIncludes($course->slug, $filteredIncludes);

        if (!$courseWithIncludes) {
            return $this->notFound(__('messages.courses.not_found'));
        }

        $elementsData = null;
        if ($hasElementsInclude) {
            $elementsData = $this->loadElementsForUnits($courseWithIncludes, $user);
        }

        return $this->success(new CourseResource($courseWithIncludes, $elementsData));
    }

    private function loadElementsForUnits(Course $course, $user): array
    {
        if (!$course->relationLoaded('units')) {
            $course->load(['units' => fn($q) => $q->where('status', 'published')->orderBy('order')]);
        }

        $elementsData = [];
        foreach ($course->units as $unit) {
            $elementsData[$unit->id] = $this->unitService->getContents($unit, $user);
        }

        return $elementsData;
    }

    public function update(CourseRequest $request, Course $course)
    {
        $courseWithInstructors = $this->service->findWithInstructors($course->id);
        $this->authorize('update', $courseWithInstructors);

        try {
            $updated = $this->service->update($course->id, $request->validated(), $request->allFiles());

            return $this->success(new CourseResource($updated), __('messages.courses.updated'));
        } catch (DuplicateResourceException $e) {
            return $this->validationError($e->getErrors());
        }
    }

    public function destroy(Course $course)
    {
        $courseWithInstructors = $this->service->findWithInstructors($course->id);

        if (! $courseWithInstructors) {
            return $this->notFound(__('messages.courses.not_found'));
        }

        $this->authorize('delete', $courseWithInstructors);
        DeleteCourseJob::dispatch($course->id, auth('api')->id());

        return $this->success(
            [
                'queued' => true,
                'course_id' => $course->id,
            ],
            'messages.courses.delete_queued',
            [],
            202
        );
    }

    public function publish(Course $course)
    {
        $courseWithInstructors = $this->service->findWithInstructors($course->id);
        $this->authorize('update', $courseWithInstructors);

        $updated = $this->service->publish($course->id);

        return $this->success(new CourseResource($updated), __('messages.courses.published'));
    }

    public function unpublish(Course $course)
    {
        $courseWithInstructors = $this->service->findWithInstructors($course->id);
        $this->authorize('update', $courseWithInstructors);

        $updated = $this->service->unpublish($course->id);

        return $this->success(new CourseResource($updated), __('messages.courses.unpublished'));
    }

    public function archive(Course $course)
    {
        $courseWithInstructors = $this->service->findWithInstructors($course->id);
        $this->authorize('update', $courseWithInstructors);

        $updated = $this->service->archive($course->id);

        return $this->success(new CourseResource($updated), __('messages.courses.archived'));
    }

    public function unarchive(Course $course)
    {
        $courseWithInstructors = $this->service->findWithInstructors($course->id);
        $this->authorize('update', $courseWithInstructors);

        $updated = $this->service->unarchive($course->id);

        return $this->success(new CourseResource($updated), __('messages.courses.unarchived'));
    }

    public function generateEnrollmentKey(Course $course)
    {
        $courseWithInstructors = $this->service->findWithInstructors($course->id);
        $this->authorize('update', $courseWithInstructors);
        $result = $this->service->updateEnrollmentSettings($course->id, ['enrollment_type' => 'key_based']);

        return $this->success(
            ['course' => new CourseResource($result['course']), 'enrollment_key' => $result['enrollment_key']],
            __('messages.courses.key_generated'),
        );
    }

    public function updateEnrollmentKey(PublishCourseRequest $request, Course $course)
    {
        $courseWithInstructors = $this->service->findWithInstructors($course->id);
        $this->authorize('update', $courseWithInstructors);
        $result = $this->service->updateEnrollmentSettings($course->id, $request->validated());

        return $this->success(
            ['course' => new CourseResource($result['course']), 'enrollment_key' => $result['enrollment_key']],
            __('messages.courses.enrollment_settings_updated')
        );
    }

    public function removeEnrollmentKey(Course $course)
    {
        $courseWithInstructors = $this->service->findWithInstructors($course->id);
        $this->authorize('update', $courseWithInstructors);
        $result = $this->service->updateEnrollmentSettings($course->id, ['enrollment_type' => 'auto_accept']);

        return $this->success(new CourseResource($result['course']), __('messages.courses.key_removed'));
    }

    public function myEnrolledCourses(Request $request)
    {
        $userId = auth('api')->id();
        $perPage = (int) $request->query('per_page', 15);

        $paginator = $this->service->listEnrolledCourses($userId, $request->all(), $perPage);
        $paginator->getCollection()->transform(fn($course) => new \Modules\Schemes\Http\Resources\CourseIndexResource($course));

        return $this->paginateResponse($paginator, 'messages.courses.list_retrieved');
    }

    public function generateSlug(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
        ]);

        $slug = $this->service->generateUniqueSlug($request->input('title'));

        return $this->success(
            $slug,
            __('messages.courses.slug_generated')
        );
    }
}
