<?php

declare(strict_types=1);

namespace Modules\Learning\Http\Controllers;

use App\Support\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Learning\Contracts\Services\QuizSubmissionServiceInterface;
use Modules\Learning\Http\Requests\SaveQuizAnswerRequest;
use Modules\Learning\Http\Resources\QuizSubmissionResource;
use Modules\Learning\Models\Quiz;
use Modules\Learning\Models\QuizSubmission;
use Modules\Schemes\Traits\ValidatesEnrollment;

class QuizSubmissionController extends Controller
{
    use ApiResponse;
    use AuthorizesRequests;
    use ValidatesEnrollment;

    public function __construct(
        private readonly QuizSubmissionServiceInterface $submissionService,
        private readonly \Modules\Learning\Services\Support\QuizSubmissionIncludeAuthorizer $includeAuthorizer
    ) {
        $this->middleware(\Modules\Learning\Http\Middleware\ValidateQuizSessionToken::class)
            ->only(['show', 'listQuestions', 'saveAnswer', 'submit', 'overview']);
    }

    public function index(Quiz $quiz): JsonResponse
    {
        $user = auth('api')->user();

        if ($user->hasRole('Student')) {
            $includes = request()->query('include', '');
            $includesArray = $includes ? explode(',', $includes) : [];

            
            $allowedIncludes = $this->includeAuthorizer->getAllowedIncludesForQueryBuilder($user, new QuizSubmission(['quiz_id' => $quiz->id, 'user_id' => $user->id]));

            $query = QuizSubmission::where('quiz_id', $quiz->id)
                ->where('user_id', $user->id)
                ->orderByDesc('created_at');

            $submissions = \Spatie\QueryBuilder\QueryBuilder::for($query)
                ->allowedIncludes($allowedIncludes)
                ->get();

            return $this->success(QuizSubmissionResource::collection($submissions));
        }

        $this->authorize('viewSubmissions', $quiz);
        $paginator = $this->submissionService->listForQuiz($quiz->id, request()->all());
        $paginator->getCollection()->transform(fn ($item) => new QuizSubmissionResource($item));

        return $this->paginateResponse($paginator, 'messages.quiz_submissions.list_retrieved');
    }

    public function start(Quiz $quiz): JsonResponse
    {
        $this->authorize('takeQuiz', $quiz);
        $user = auth('api')->user();

        $enrollmentId = null;
        if ($user->hasRole('Student')) {
            $course = $quiz->getCourse();
            if ($course) {
                $enrollment = $this->getActiveEnrollment($course);
                $enrollmentId = $enrollment?->id;
            }
        }

        $submission = $this->submissionService->start($quiz, $user->id, $enrollmentId);

        return $this->created(QuizSubmissionResource::make($submission), __('messages.quiz_submissions.started'));
    }

    public function highestSubmission(Quiz $quiz): JsonResponse
    {
        $submission = $this->submissionService->getHighestSubmission($quiz->id, auth('api')->id());

        return $this->success(
            $submission ? QuizSubmissionResource::make($submission) : null
        );
    }

    public function show(Quiz $quiz, QuizSubmission $submission): JsonResponse
    {
        $this->authorize('view', $submission);
        $user = auth('api')->user();
        $includes = request()->query('include', '');
        $includesArray = $includes ? explode(',', $includes) : [];

        $submissionWithIncludes = $this->submissionService->getSubmissionWithIncludes($submission, $includesArray, $user->id);

        return $this->success(QuizSubmissionResource::make($submissionWithIncludes));
    }

    public function listQuestions(QuizSubmission $submission): JsonResponse
    {
        $this->authorize('view', $submission);
        $user = auth('api')->user();
        $page = (int) request()->get('page', 1);

        if ($user && $user->hasRole('Student')) {
            $result = $this->submissionService->getQuestionsForStudent($submission, $page);

            $response = [
                'data' => new \Modules\Learning\Http\Resources\QuizQuestionResource($result['question']),
                'meta' => $result['meta'],
            ];

            
            if (isset($result['answer']) && $result['answer']) {
                $response['answer'] = [
                    'id' => $result['answer']->id,
                    'content' => $result['answer']->content,
                    'selected_options' => $result['answer']->selected_options,
                ];
            }

            return $this->success($response);
        }

        $questions = $this->submissionService->listQuestions($submission, $submission->user_id);

        return $this->success(\Modules\Learning\Http\Resources\QuizQuestionResource::collection($questions));
    }


    public function saveAnswer(SaveQuizAnswerRequest $request, QuizSubmission $submission): JsonResponse
    {
        $this->authorize('update', $submission);
        $questionId = $request->validated('quiz_question_id');
        $answer = $this->submissionService->saveAnswer($submission, $questionId, $request->validated());

        return $this->success($answer, __('messages.quiz_submissions.answer_saved'));
    }

    public function submit(QuizSubmission $submission): JsonResponse
    {
        $this->authorize('update', $submission);
        $submitted = $this->submissionService->submit($submission, auth('api')->id());

        return $this->success(QuizSubmissionResource::make($submitted), __('messages.quiz_submissions.submitted'));
    }

    public function overview(QuizSubmission $submission): JsonResponse
    {
        $this->authorize('view', $submission);
        $overview = $this->submissionService->getOverview($submission);

        return $this->success($overview);
    }

    public function takeover(QuizSubmission $submission): JsonResponse
    {
        $this->authorize('takeover', $submission);
        $updated = $this->submissionService->takeover($submission);

        return $this->success(
            QuizSubmissionResource::make($updated),
            __('messages.quiz_submissions.takeover_success')
        );
    }
}
