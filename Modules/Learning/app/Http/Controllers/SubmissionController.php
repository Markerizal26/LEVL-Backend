<?php

declare(strict_types=1);

namespace Modules\Learning\Http\Controllers;

use App\Support\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Learning\Contracts\Services\SubmissionServiceInterface;
use Modules\Learning\Http\Requests\GradeSubmissionRequest;
use Modules\Learning\Http\Requests\SearchSubmissionsRequest;
use Modules\Learning\Http\Requests\StartSubmissionRequest;
use Modules\Learning\Http\Requests\StoreSubmissionRequest;
use Modules\Learning\Http\Requests\SubmitAnswersRequest;
use Modules\Learning\Http\Requests\UpdateSubmissionRequest;
use Modules\Learning\Http\Resources\AnswerResource;
use Modules\Learning\Http\Resources\QuestionResource;
use Modules\Learning\Http\Resources\SubmissionConfirmationResource;
use Modules\Learning\Http\Resources\SubmissionDetailResource;
use Modules\Learning\Http\Resources\SubmissionResource;
use Modules\Learning\Models\Assignment;
use Modules\Learning\Models\Submission;

class SubmissionController extends Controller
{
    use ApiResponse;
    use AuthorizesRequests;

    public function __construct(
        private readonly SubmissionServiceInterface $service
    ) {}

    public function index(Request $request, Assignment $assignment): JsonResponse
    {
        $user = auth('api')->user();

        if ($user->hasRole('Student')) {
            $submissions = $this->service->getSubmissionsWithHighestMarked($assignment->id, $user->id);

            return $this->success(\Modules\Learning\Http\Resources\SubmissionListResource::collection($submissions));
        }

        $this->authorize('viewAny', Submission::class);
        $paginator = $this->service->listForAssignmentForIndex($assignment, $user, $request->all());
        $paginator->getCollection()->transform(fn ($item) => new \Modules\Learning\Http\Resources\SubmissionIndexResource($item));

        return $this->paginateResponse($paginator, 'messages.submissions.list_retrieved');
    }

    public function store(StoreSubmissionRequest $request, Assignment $assignment): JsonResponse
    {
        $this->authorize('createForAssignment', [Submission::class, $assignment]);

        $data = $request->validated();

        if ($request->hasFile('files')) {
            $data['files'] = $request->file('files');
        }

        $submission = $this->service->create($assignment, auth('api')->id(), $data);

        return $this->created(SubmissionResource::make($submission), __('messages.submissions.created'));
    }

    public function update(UpdateSubmissionRequest $request, Submission $submission): JsonResponse
    {
        $this->authorize('update', $submission);

        $data = $request->validated();

        if ($request->hasFile('files')) {
            $data['files'] = $request->file('files');
        }

        $updated = $this->service->update($submission, $data);

        return $this->success(SubmissionResource::make($updated), __('messages.submissions.updated'));
    }

    public function start(StartSubmissionRequest $request, Assignment $assignment): JsonResponse
    {
        $this->authorize('createForAssignment', [Submission::class, $assignment]);
        $submission = $this->service->startSubmission($assignment->id, auth('api')->id());

        return $this->created(SubmissionResource::make($submission), __('messages.submissions.started'));
    }

    public function submit(SubmitAnswersRequest $request, Submission $submission): JsonResponse
    {
        $this->authorize('submit', $submission);
        $submitted = $this->service->submitAnswers($submission->id, $request->validated('answers') ?? []);

        return $this->success(SubmissionConfirmationResource::make($submitted), __('messages.submissions.submitted'));
    }

    public function listQuestions(Request $request, Submission $submission): JsonResponse
    {
        $this->authorize('accessQuestions', $submission);
        $user = auth('api')->user();
        $page = (int) $request->get('page', 1);

        if ($user && $user->hasRole('Student')) {
            $result = $this->service->getQuestionsForStudent($submission, $page);

            return $this->success([
                'data' => new QuestionResource($result['question']),
                'meta' => $result['meta'],
            ]);
        }

        if ($request->hasAny(['page', 'per_page'])) {
            return $this->paginateResponse($this->service->getSubmissionQuestionsPaginated($submission, (int) $request->query('per_page', 1))->through(fn ($i) => new QuestionResource($i)));
        }

        return $this->success(QuestionResource::collection($this->service->getSubmissionQuestions($submission)));
    }

    public function grade(GradeSubmissionRequest $request, Submission $submission): JsonResponse
    {
        $this->authorize('grade', $submission);
        $graded = $this->service->grade($submission, $request->validated('score'), auth('api')->id(), $request->validated('feedback'));

        return $this->success(SubmissionResource::make($graded), __('messages.submissions.graded'));
    }

    public function showForAssignment(Request $request, Assignment $assignment, Submission $submission): JsonResponse
    {
        $this->authorize('view', $submission);
        if ($submission->assignment_id !== $assignment->id) {
            return $this->error(__('messages.submissions.not_found'), [], 404);
        }

        $user = auth('api')->user();
        if ($user && $user->hasRole('Student')) {
            $accessCheck = $this->service->validateStudentAssignmentAccess($assignment, $user->id);

            if (! $accessCheck['accessible']) {
                if ($accessCheck['reason'] === 'not_enrolled') {
                    return $this->error(__('messages.submissions.not_enrolled'), [], 403);
                }

                return $this->error(
                    __('messages.submissions.assignment_locked', ['count' => $accessCheck['missing_count']]),
                    ['missing_prerequisites_count' => $accessCheck['missing_count']],
                    403
                );
            }
        }

        $data = $this->service->getSubmissionDetail($submission, auth('api')->id());

        return $this->success((new SubmissionDetailResource($data['submission']))->withVisibility($data['visibility']));
    }

    public function highestSubmission(Request $request, Assignment $assignment): JsonResponse
    {
        $user = auth('api')->user();

        if ($user && $user->hasRole('Student')) {
            $accessCheck = $this->service->validateStudentAssignmentAccess($assignment, $user->id);

            if (! $accessCheck['accessible']) {
                if ($accessCheck['reason'] === 'not_enrolled') {
                    return $this->error(__('messages.submissions.not_enrolled'), [], 403);
                }

                return $this->error(
                    __('messages.submissions.assignment_locked', ['count' => $accessCheck['missing_count']]),
                    ['missing_prerequisites_count' => $accessCheck['missing_count']],
                    403
                );
            }
        }

        $submission = $this->service->getHighestScoreSubmission($assignment->id, auth('api')->id());
        if (! $submission) {
            return $this->error(__('messages.submissions.not_found'), [], 404);
        }

        return $this->success(SubmissionResource::make($submission));
    }

    public function search(SearchSubmissionsRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Submission::class);
        $result = $this->service->searchSubmissions($request->validated('query', ''), $request->validated('filters', []), ['per_page' => (int) $request->validated('per_page', 15), 'page' => (int) $request->validated('page', 1)]);
        $result['data']->transform(fn ($item) => new SubmissionResource($item));

        return $this->success(['data' => $result['data'], 'meta' => ['total' => $result['total'], 'per_page' => $result['per_page'], 'current_page' => $result['current_page'], 'last_page' => $result['last_page']]]);
    }
}
