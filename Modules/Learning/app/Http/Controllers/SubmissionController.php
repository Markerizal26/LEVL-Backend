<?php

declare(strict_types=1);

namespace Modules\Learning\Http\Controllers;

use App\Support\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Learning\Contracts\Services\ReviewModeServiceInterface;
use Modules\Learning\Contracts\Services\SubmissionServiceInterface;
use Modules\Learning\Http\Requests\GradeSubmissionRequest;
use Modules\Learning\Http\Requests\SearchSubmissionsRequest;
use Modules\Learning\Http\Requests\StartSubmissionRequest;
use Modules\Learning\Http\Requests\StoreSubmissionRequest;
use Modules\Learning\Http\Requests\SubmitAnswersRequest;
use Modules\Learning\Http\Requests\UpdateSubmissionRequest;
use Modules\Learning\Http\Resources\SubmissionResource;
use Modules\Learning\Models\Assignment;
use Modules\Learning\Models\Submission;

class SubmissionController extends Controller
{
    use ApiResponse;
    use AuthorizesRequests;

    public function __construct(
        private readonly SubmissionServiceInterface $service,
        private readonly ReviewModeServiceInterface $reviewModeService
    ) {}

    public function index(Request $request, Assignment $assignment): JsonResponse
    {
        $this->authorize('viewAny', Submission::class);

        $user = auth('api')->user();
        $perPage = (int) $request->query('per_page', 15);
        $filters = $request->input('filter', []);

        $submissions = $this->service->listForAssignment($assignment, $user, $filters);

        return $this->success(['submissions' => SubmissionResource::collection($submissions)]);
    }

    public function store(StoreSubmissionRequest $request, Assignment $assignment): JsonResponse
    {
        $this->authorize('create', Submission::class);

        $user = auth('api')->user();
        $validated = $request->validated();

        $submission = $this->service->create($assignment, $user->id, $validated);

        return $this->created(
            ['submission' => SubmissionResource::make($submission)],
            __('messages.submissions.created')
        );
    }

    public function show(Submission $submission): JsonResponse
    {
        $this->authorize('view', $submission);

        $user = auth('api')->user();
        $visibility = $this->reviewModeService->getVisibilityStatus($submission, $user?->id);

        return $this->success([
            'submission' => SubmissionResource::make($submission),
            'visibility' => $visibility,
        ]);
    }

    public function update(UpdateSubmissionRequest $request, Submission $submission): JsonResponse
    {
        $this->authorize('update', $submission);

        $validated = $request->validated();
        $updated = $this->service->update($submission, $validated);

        return $this->success(
            ['submission' => SubmissionResource::make($updated)],
            __('messages.submissions.updated')
        );
    }

    public function start(StartSubmissionRequest $request, Assignment $assignment): JsonResponse
    {
        $this->authorize('create', Submission::class);

        $user = auth('api')->user();
        $submission = $this->service->startSubmission($assignment->id, $user->id);

        return $this->created(
            ['submission' => SubmissionResource::make($submission)],
            __('messages.submissions.started')
        );
    }

    public function submit(SubmitAnswersRequest $request, Submission $submission): JsonResponse
    {
        $this->authorize('update', $submission);

        $validated = $request->validated();
        $submitted = $this->service->submitAnswers($submission->id, $validated['answers']);

        return $this->success(
            ['submission' => SubmissionResource::make($submitted)],
            __('messages.submissions.submitted')
        );
    }

    public function grade(GradeSubmissionRequest $request, Submission $submission): JsonResponse
    {
        $this->authorize('grade', $submission);

        $user = auth('api')->user();
        $validated = $request->validated();

        $graded = $this->service->grade(
            $submission,
            $validated['score'],
            $user->id,
            $validated['feedback'] ?? null
        );

        return $this->success(
            ['submission' => SubmissionResource::make($graded)],
            __('messages.submissions.graded')
        );
    }

    public function checkDeadline(Request $request, Assignment $assignment): JsonResponse
    {
        $user = auth('api')->user();
        $allowed = $this->service->checkDeadlineWithOverride($assignment, $user->id);

        return $this->success([
            'allowed' => $allowed,
            'deadline' => $assignment->deadline_at?->toIso8601String(),
            'tolerance_minutes' => $assignment->tolerance_minutes,
        ]);
    }

    public function checkAttempts(Request $request, Assignment $assignment): JsonResponse
    {
        $user = auth('api')->user();
        $result = $this->service->checkAttemptLimitsWithOverride($assignment, $user->id);

        return $this->success($result);
    }

    public function mySubmissions(Request $request, Assignment $assignment): JsonResponse
    {
        $user = auth('api')->user();
        $submissions = $this->service->getSubmissionsWithHighestMarked($assignment->id, $user->id);

        return $this->success(['submissions' => SubmissionResource::collection($submissions)]);
    }

    public function highestSubmission(Request $request, Assignment $assignment): JsonResponse
    {
        $user = auth('api')->user();
        $submission = $this->service->getHighestScoreSubmission($assignment->id, $user->id);

        if (!$submission) {
            return $this->error(__('messages.submissions.not_found'), [], 404);
        }

        return $this->success(['submission' => SubmissionResource::make($submission)]);
    }

    public function search(SearchSubmissionsRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Submission::class);

        $validated = $request->validated();
        $query = $validated['query'] ?? '';
        $filters = $validated['filters'] ?? [];
        $options = [
            'per_page' => $validated['per_page'] ?? 15,
            'page' => $validated['page'] ?? 1,
        ];

        $result = $this->service->searchSubmissions($query, $filters, $options);

        $result['data']->transform(fn($item) => new SubmissionResource($item));

        return $this->success([
            'submissions' => $result['data'],
            'meta' => [
                'total' => $result['total'],
                'per_page' => $result['per_page'],
                'current_page' => $result['current_page'],
                'last_page' => $result['last_page'],
            ],
        ]);
    }
}
