<?php

declare(strict_types=1);

namespace Modules\Enrollments\Http\Controllers;

use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Enrollments\Contracts\Services\EnrollmentServiceInterface;
use Modules\Enrollments\Http\Requests\BulkEnrollmentActionRequest;
use Modules\Enrollments\Http\Resources\EnrollmentResource;
use Modules\Enrollments\Models\Enrollment;
use Modules\Schemes\Models\Course;

class EnrollmentsController extends Controller
{
    use ApiResponse;
    use \Illuminate\Foundation\Auth\Access\AuthorizesRequests;

    public function __construct(private readonly EnrollmentServiceInterface $service) {}

    public function index(Request $request)
    {
        $user = auth('api')->user();
        $perPage = max(1, (int) $request->query('per_page', 15));

        $paginator = $this->service->listEnrollmentsForIndex($user, $perPage, $request->all());

        $paginator->getCollection()->transform(fn ($item) => new \Modules\Enrollments\Http\Resources\EnrollmentIndexResource($item));

        return $this->paginateResponse($paginator, __('messages.enrollments.list_retrieved'));
    }

    public function indexByCourse(Request $request, Course $course)
    {
        $this->authorize('update', $course);

        $perPage = max(1, (int) $request->query('per_page', 15));
        $paginator = $this->service->paginateByCourse($course->id, $perPage, $request->all());

        $paginator->getCollection()->transform(fn ($item) => new EnrollmentResource($item));

        return $this->paginateResponse($paginator, __('messages.enrollments.course_list_retrieved'));
    }

    public function store(Request $request, Course $course)
    {
        $user = auth('api')->user();

        $result = $this->service->enroll($user, $course, $request->all());

        return $this->created(
            new EnrollmentResource($result['enrollment']),
            $result['message']
        );
    }

    public function cancel(Request $request, Course $course)
    {
        $user = auth('api')->user();

        $enrollment = $this->service->findEnrollmentForAction($course, $user, $request->all());
        $this->authorize('cancel', $enrollment);

        $updated = $this->service->cancel($enrollment);

        return $this->success(new EnrollmentResource($updated), __('messages.enrollments.cancelled'));
    }

    public function withdraw(Request $request, Course $course)
    {
        $user = auth('api')->user();

        $enrollment = $this->service->findEnrollmentForAction($course, $user, $request->all());
        $this->authorize('withdraw', $enrollment);

        $updated = $this->service->withdraw($enrollment);

        return $this->success(new EnrollmentResource($updated), __('messages.enrollments.withdrawn'));
    }

    public function status(Request $request, Course $course)
    {
        $user = auth('api')->user();

        $result = $this->service->getEnrollmentStatus($course, $user, $request->all());

        if (! $result['found']) {
            return $this->success(
                ['status' => 'not_enrolled', 'enrollment' => null],
                __('messages.enrollments.not_enrolled'),
            );
        }

        $this->authorize('view', $result['enrollment']);

        return $this->success(
            [
                'status' => $result['enrollment']->status,
                'enrollment' => new EnrollmentResource($result['enrollment']),
            ],
            __('messages.enrollments.status_retrieved'),
        );
    }

    public function approve(Enrollment $enrollment)
    {
        $this->authorize('approve', $enrollment);

        $updated = $this->service->approve($enrollment);

        return $this->success(new EnrollmentResource($updated), __('messages.enrollments.approved'));
    }

    public function decline(Enrollment $enrollment)
    {
        $this->authorize('decline', $enrollment);

        $updated = $this->service->decline($enrollment);

        return $this->success(new EnrollmentResource($updated), __('messages.enrollments.rejected'));
    }

    public function remove(Enrollment $enrollment)
    {
        $this->authorize('remove', $enrollment);

        $updated = $this->service->remove($enrollment);

        return $this->success(new EnrollmentResource($updated), __('messages.enrollments.expelled'));
    }

    public function bulkApprove(BulkEnrollmentActionRequest $request)
    {
        $data = $request->validated();
        $actor = auth('api')->user();
        $enrollments = $this->service->getEnrollmentsAuthorizedFor($actor, $data['enrollment_ids'], 'approve');
        $result = $this->service->bulkApprove($enrollments);

        return $this->success([
            'processed' => EnrollmentResource::collection($result['processed']),
            'failed' => $result['failed'],
        ], __('messages.enrollments.bulk_action_completed'));
    }

    public function bulkDecline(BulkEnrollmentActionRequest $request)
    {
        $data = $request->validated();
        $actor = auth('api')->user();
        $enrollments = $this->service->getEnrollmentsAuthorizedFor($actor, $data['enrollment_ids'], 'decline');
        $result = $this->service->bulkDecline($enrollments);

        return $this->success([
            'processed' => EnrollmentResource::collection($result['processed']),
            'failed' => $result['failed'],
        ], __('messages.enrollments.bulk_action_completed'));
    }

    public function bulkRemove(BulkEnrollmentActionRequest $request)
    {
        $data = $request->validated();
        $actor = auth('api')->user();
        $enrollments = $this->service->getEnrollmentsAuthorizedFor($actor, $data['enrollment_ids'], 'remove');
        $result = $this->service->bulkRemove($enrollments);

        return $this->success([
            'processed' => EnrollmentResource::collection($result['processed']),
            'failed' => $result['failed'],
        ], __('messages.enrollments.bulk_action_completed'));
    }
}
