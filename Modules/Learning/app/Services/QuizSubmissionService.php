<?php

declare(strict_types=1);

namespace Modules\Learning\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Learning\Contracts\Services\QuizSubmissionServiceInterface;
use Modules\Learning\Enums\QuizGradingStatus;
use Modules\Learning\Enums\QuizQuestionType;
use Modules\Learning\Enums\QuizSubmissionStatus;
use Modules\Learning\Enums\RandomizationType;
use Modules\Learning\Models\Quiz;
use Modules\Learning\Models\QuizAnswer;
use Modules\Learning\Models\QuizQuestion;
use Modules\Learning\Models\QuizSubmission;
use Modules\Learning\Repositories\QuizSubmissionRepository;
use Modules\Learning\Services\Support\QuizObjectiveGrader;
use Modules\Learning\Services\Support\QuizSubmissionIncludeAuthorizer;

class QuizSubmissionService implements QuizSubmissionServiceInterface
{
    private const TIMER_GRACE_SECONDS = 60;

    public function __construct(
        private readonly QuizSubmissionRepository $repository,
        private readonly \Modules\Schemes\Services\PrerequisiteService $prerequisiteService,
        private readonly QuizSubmissionIncludeAuthorizer $includeAuthorizer,
        private readonly QuizObjectiveGrader $objectiveGrader,
    ) {}

    public function start(Quiz $quiz, int $userId, ?int $enrollmentId = null): QuizSubmission
    {
        $accessCheck = $this->prerequisiteService->checkQuizAccess($quiz, $userId);

        if (! $accessCheck['accessible']) {
            $missingCount = count($accessCheck['missing']);
            $message = trans_choice('messages.quizzes.locked_cannot_start', $missingCount, ['count' => $missingCount]);
            throw \Illuminate\Validation\ValidationException::withMessages([
                'quiz' => [$message],
            ]);
        }

        $pendingSubmission = QuizSubmission::where('quiz_id', $quiz->id)
            ->where('user_id', $userId)
            ->whereIn('status', [QuizSubmissionStatus::Draft->value, QuizSubmissionStatus::Submitted->value])
            ->first();

        if ($pendingSubmission) {
            if ($pendingSubmission->status === QuizSubmissionStatus::Draft) {
                throw new \Illuminate\Http\Exceptions\HttpResponseException(
                    response()->json([
                        'success' => false,
                        'message' => __('messages.quiz_submissions.device_conflict'),
                        'errors' => null,
                        'data' => ['submission_id' => $pendingSubmission->id],
                    ], 409)
                );
            }

            $message = __('messages.quiz_submissions.pending_grading');
            throw new \Illuminate\Http\Exceptions\HttpResponseException(
                response()->json([
                    'success' => false,
                    'message' => $message,
                    'errors' => ['quiz' => [$message]],
                    'data' => ['latest_submission_id' => $pendingSubmission->id],
                ], 422)
            );
        }

        return DB::transaction(function () use ($quiz, $userId, $enrollmentId) {
            $attemptNumber = $this->repository->getAttemptCount($quiz->id, $userId) + 1;
            $questionSet = $this->generateQuestionSet($quiz);

            return $this->repository->create([
                'quiz_id' => $quiz->id,
                'user_id' => $userId,
                'enrollment_id' => $enrollmentId,
                'session_token' => Str::random(60),
                'status' => QuizSubmissionStatus::Draft->value,
                'grading_status' => QuizGradingStatus::Pending->value,
                'attempt_number' => $attemptNumber,
                'question_set' => $questionSet,
                'started_at' => now(),
            ]);
        });
    }

    private function generateQuestionSet(Quiz $quiz): ?array
    {
        $type = $quiz->randomization_type;

        if ($type === null || $type === RandomizationType::Static) {
            return null;
        }

        $allIds = $quiz->questions()->ordered()->pluck('id')->toArray();

        if (empty($allIds)) {
            return null;
        }

        if ($type === RandomizationType::Bank) {
            $count = (int) ($quiz->question_bank_count ?? count($allIds));
            $count = min($count, count($allIds));
            shuffle($allIds);

            return array_slice($allIds, 0, $count);
        }

        shuffle($allIds);

        return $allIds;
    }

    public function saveAnswer(QuizSubmission $submission, int $questionId, array $data): QuizAnswer
    {

        if ($submission->status !== QuizSubmissionStatus::Draft) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'submission' => [__('messages.quiz_submissions.not_draft')],
            ]);
        }

        if ($this->isTimeLimitExceeded($submission)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'submission' => [__('messages.quiz_submissions.time_limit_exceeded')],
            ]);
        }

        $accessCheck = $this->prerequisiteService->checkQuizAccess($submission->quiz, $submission->user_id);
        if (! $accessCheck['accessible']) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'quiz' => [__('messages.quizzes.locked_cannot_answer')],
            ]);
        }

        $question = QuizQuestion::findOrFail($questionId);
        $this->validateAnswerForQuestionType($question, $data);

        return DB::transaction(function () use ($submission, $questionId, $question, $data) {
            $existing = QuizAnswer::where('quiz_submission_id', $submission->id)
                ->where('quiz_question_id', $questionId)
                ->first();

            $answerData = [
                'quiz_submission_id' => $submission->id,
                'quiz_question_id' => $questionId,
                'content' => $question->type === QuizQuestionType::Essay ? ($data['content'] ?? null) : null,
                'selected_options' => $question->type !== QuizQuestionType::Essay ? ($data['selected_options'] ?? null) : null,
                'is_auto_graded' => 0,
                'score' => null,
            ];

            if ($existing) {
                $existing->fill($answerData)->save();

                return $existing;
            }

            return QuizAnswer::create($answerData);
        });
    }

    public function submit(QuizSubmission $submission, int $actorId): QuizSubmission
    {
        return DB::transaction(function () use ($submission) {
            $lockedSubmission = QuizSubmission::lockForUpdate()->find($submission->id);

            if (! $lockedSubmission || $lockedSubmission->status !== QuizSubmissionStatus::Draft) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'submission' => [__('messages.quiz_submissions.not_draft')],
                ]);
            }

            if ($this->isTimeLimitExceeded($lockedSubmission)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'submission' => [__('messages.quiz_submissions.time_limit_exceeded')],
                ]);
            }

            $accessCheck = $this->prerequisiteService->checkQuizAccess($lockedSubmission->quiz, $lockedSubmission->user_id);
            if (! $accessCheck['accessible']) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'quiz' => [__('messages.quizzes.locked_cannot_submit')],
                ]);
            }

            $questions = $this->listQuestions($lockedSubmission, $lockedSubmission->user_id);
            $answeredCount = QuizAnswer::where('quiz_submission_id', $lockedSubmission->id)->count();

            if ($answeredCount < $questions->count()) {
                $unansweredCount = $questions->count() - $answeredCount;
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'answers' => [trans_choice('messages.quiz_submissions.unanswered_questions', $unansweredCount, ['count' => $unansweredCount])],
                ]);
            }

            $timeSpent = 0;
            if ($lockedSubmission->started_at) {
                $timeSpent = max(0, (int) now()->diffInSeconds($lockedSubmission->started_at, false));
            }

            $this->repository->updateSubmission($lockedSubmission, [
                'status' => QuizSubmissionStatus::Submitted->value,
                'submitted_at' => now(),
                'time_spent_seconds' => $timeSpent,
                'session_token' => null,
            ]);

            $gradedSubmission = $this->autoGrade($lockedSubmission);

            event(new \Modules\Learning\Events\QuizSubmitted($gradedSubmission));

            if ($gradedSubmission->grading_status === QuizGradingStatus::Graded) {
                event(new \Modules\Learning\Events\QuizCompleted($gradedSubmission));
            }

            return $gradedSubmission;
        });
    }

    public function getMySubmissions(int $quizId, int $userId): Collection
    {
        return $this->repository->findForStudent($quizId, $userId);
    }

    public function getMySubmissionsWithIncludes(int $quizId, int $userId, array $includes): Collection
    {
        if (empty($includes)) {
            return $this->getMySubmissions($quizId, $userId);
        }

        $user = \Modules\Auth\Models\User::find($userId);
        $submissions = $this->repository->findForStudent($quizId, $userId);

        $submissions->each(function ($submission) use ($user, $includes) {
            $allowedIncludes = $this->includeAuthorizer->getAllowedIncludesForQueryBuilder($user, $submission);
            $includesToLoad = array_intersect($includes, $allowedIncludes);

            if (! empty($includesToLoad)) {
                $submission->load($includesToLoad);
            }
        });

        return $submissions;
    }

    public function getHighestSubmission(int $quizId, int $userId): ?QuizSubmission
    {
        return QuizSubmission::where('quiz_id', $quizId)
            ->where('user_id', $userId)
            ->whereNotNull('final_score')
            ->orderByDesc('final_score')
            ->first();
    }

    public function listForQuiz(int $quizId, array $filters = []): LengthAwarePaginator
    {
        return $this->repository->findByQuiz($quizId, $filters);
    }

    public function listQuestions(QuizSubmission $submission, int $userId): Collection
    {
        $quiz = $submission->quiz;

        if ($submission->question_set) {
            return QuizQuestion::whereIn('id', $submission->question_set)
                ->orderByRaw('ARRAY_POSITION(ARRAY['.implode(',', $submission->question_set).']::bigint[], id)')
                ->get();
        }

        return $quiz->questions()->ordered()->get();
    }

    private function autoGrade(QuizSubmission $submission): QuizSubmission
    {
        $submission->load(['quiz.questions', 'answers']);

        $quiz = $submission->quiz;
        $hasEssay = $quiz->hasEssayQuestions();
        $hasObjective = ! $quiz->hasOnlyEssayQuestions();

        if (! $hasObjective && $hasEssay) {
            return $this->applyGradingStatus($submission, QuizGradingStatus::WaitingForGrading, null);
        }

        $totalObjectiveWeight = 0.0;
        $earnedObjectiveScore = 0.0;

        foreach ($quiz->questions as $question) {
            if (! $question->canAutoGrade()) {
                continue;
            }

            $answer = $submission->answers
                ->where('quiz_question_id', $question->id)
                ->first();

            $score = $this->objectiveGrader->grade($question, $answer);
            $totalObjectiveWeight += (float) $question->weight;
            $earnedObjectiveScore += $score;

            if ($answer) {
                $answer->fill(['score' => $score, 'is_auto_graded' => 1])->save();
            } else {
                QuizAnswer::create([
                    'quiz_submission_id' => $submission->id,
                    'quiz_question_id' => $question->id,
                    'score' => 0,
                    'is_auto_graded' => 1,
                ]);
            }
        }

        $maxScore = (float) $quiz->max_score;
        $objectiveScore = $totalObjectiveWeight > 0
            ? round(($earnedObjectiveScore / $totalObjectiveWeight) * $maxScore, 2)
            : 0;

        if ($hasEssay) {
            return $this->applyGradingStatus($submission, QuizGradingStatus::PartiallyGraded, $objectiveScore);
        }

        return $this->applyGradingStatus($submission, QuizGradingStatus::Graded, $objectiveScore);
    }

    private function applyGradingStatus(QuizSubmission $submission, QuizGradingStatus $gradingStatus, ?float $objectiveScore): QuizSubmission
    {
        $finalScore = $gradingStatus === QuizGradingStatus::Graded ? $objectiveScore : null;

        $submissionStatus = QuizSubmissionStatus::Submitted->value;
        if ($gradingStatus === QuizGradingStatus::Graded) {
            $submissionStatus = $submission->quiz->auto_grading
                ? QuizSubmissionStatus::Released->value
                : QuizSubmissionStatus::Graded->value;
        }

        $this->repository->updateSubmission($submission, [
            'grading_status' => $gradingStatus->value,
            'score' => $objectiveScore,
            'final_score' => $finalScore,
            'status' => $submissionStatus,
        ]);

        return $submission->fresh();
    }

    public function getSubmissionWithIncludes(QuizSubmission $submission, array $includes, int $userId): QuizSubmission
    {
        if (empty($includes)) {
            return $submission;
        }

        $user = \Modules\Auth\Models\User::find($userId);
        $allowedIncludes = $this->includeAuthorizer->getAllowedIncludesForQueryBuilder($user, $submission);

        return \Spatie\QueryBuilder\QueryBuilder::for(QuizSubmission::class)
            ->where('id', $submission->id)
            ->allowedIncludes($allowedIncludes)
            ->firstOrFail();
    }

    public function getQuestionsForStudent(QuizSubmission $submission, int $page): array
    {
        $questions = $this->listQuestions($submission, $submission->user_id);
        $total = $questions->count();

        if ($page < 1 || $page > $total) {
            throw new \InvalidArgumentException(__('messages.quiz_submissions.invalid_page'));
        }

        $question = $questions->get($page - 1);

        $answer = \Modules\Learning\Models\QuizAnswer::where('quiz_submission_id', $submission->id)
            ->where('quiz_question_id', $question->id)
            ->first();

        return [
            'question' => $question,
            'answer' => $answer,
            'meta' => [
                'pagination' => [
                    'current_page' => $page,
                    'total' => $total,
                    'has_next' => $page < $total,
                    'has_prev' => $page > 1,
                ],
            ],
        ];
    }

    public function getOverview(QuizSubmission $submission): array
    {
        $submission->loadMissing('quiz');
        $quiz = $submission->quiz;

        $questions = $this->listQuestions($submission, $submission->user_id);

        $answers = QuizAnswer::where('quiz_submission_id', $submission->id)
            ->get()
            ->keyBy('quiz_question_id');

        $timeLimitMinutes = $quiz->time_limit_minutes;
        $isTimeLimited = $timeLimitMinutes !== null;
        $timeRemainingSeconds = null;

        if ($isTimeLimited && $submission->started_at) {
            $expiresAt = $submission->started_at->copy()->addMinutes($timeLimitMinutes);
            $timeRemainingSeconds = max(0, (int) now()->diffInSeconds($expiresAt, false));
        }

        if ($isTimeLimited && $timeRemainingSeconds === 0 && $submission->status === QuizSubmissionStatus::Draft) {
            $submittedSubmission = $this->forceSubmitOnTimeout($submission);

            return [
                'auto_submitted' => true,
                'submission_id' => $submittedSubmission->id,
                'status' => $submittedSubmission->status?->value,
                'grading_status' => $submittedSubmission->grading_status?->value,
                'grading_status_label' => $submittedSubmission->grading_status?->label(),
                'score' => $submittedSubmission->score,
                'final_score' => $submittedSubmission->final_score,
                'started_at' => $submittedSubmission->started_at?->toISOString(),
                'submitted_at' => $submittedSubmission->submitted_at?->toISOString(),
                'time_limit_minutes' => $timeLimitMinutes,
                'time_remaining_seconds' => 0,
                'is_time_limited' => true,
                'time_spent_seconds' => $submittedSubmission->time_spent_seconds,
                'message' => __('messages.quiz_submissions.auto_submitted_timeout'),
            ];
        }

        $summary = [];
        $questionDetails = [];

        foreach ($questions->values() as $index => $question) {
            $order = $index + 1;
            $answer = $answers->get($question->id);
            $isAnswered = $answer !== null;

            $summary[] = [
                'order' => $order,
                'question_id' => $question->id,
                'is_answered' => $isAnswered,
            ];

            $questionData = [
                'id' => $question->id,
                'order' => $order,
                'type' => $question->type?->value,
                'type_label' => $question->type?->label(),
                'content' => $question->content,
                'options' => $question->options,
                'weight' => $question->weight,
                'max_score' => $question->max_score,
                'is_answered' => $isAnswered,
                'answer' => $isAnswered ? [
                    'id' => $answer->id,
                    'content' => $answer->content,
                    'selected_options' => $answer->selected_options,
                ] : null,
            ];

            $questionDetails[] = $questionData;
        }

        $answeredCount = count(array_filter($summary, fn ($s) => $s['is_answered']));
        $totalQuestions = count($summary);

        return [
            'submission_id' => $submission->id,
            'status' => $submission->status?->value,
            'started_at' => $submission->started_at?->toISOString(),
            'time_limit_minutes' => $timeLimitMinutes,
            'time_remaining_seconds' => $timeRemainingSeconds,
            'is_time_limited' => $isTimeLimited,
            'total_questions' => $totalQuestions,
            'answered_count' => $answeredCount,
            'unanswered_count' => $totalQuestions - $answeredCount,
            'summary' => $summary,
            'questions' => $questionDetails,
        ];
    }

    public function forceSubmitOnTimeout(QuizSubmission $submission): QuizSubmission
    {
        if ($submission->status !== QuizSubmissionStatus::Draft) {
            return $submission;
        }

        return DB::transaction(function () use ($submission) {
            $timeSpent = 0;
            if ($submission->started_at) {
                $quiz = $submission->quiz;
                $timeSpent = $quiz->time_limit_minutes ? $quiz->time_limit_minutes * 60 : max(0, (int) now()->diffInSeconds($submission->started_at, false));
            }

            $this->repository->updateSubmission($submission, [
                'status' => QuizSubmissionStatus::Submitted->value,
                'submitted_at' => now(),
                'time_spent_seconds' => $timeSpent,
                'session_token' => null,
            ]);

            $gradedSubmission = $this->autoGrade($submission);

            event(new \Modules\Learning\Events\QuizSubmitted($gradedSubmission));

            if ($gradedSubmission->grading_status === QuizGradingStatus::Graded) {
                event(new \Modules\Learning\Events\QuizCompleted($gradedSubmission));
            }

            return $gradedSubmission;
        });
    }

    public function checkExistingDraft(int $quizId, int $userId): ?QuizSubmission
    {
        return QuizSubmission::where('quiz_id', $quizId)
            ->where('user_id', $userId)
            ->where('status', QuizSubmissionStatus::Draft->value)
            ->first();
    }

    public function takeover(QuizSubmission $submission): QuizSubmission
    {
        if ($submission->status !== QuizSubmissionStatus::Draft) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'submission' => [__('messages.quiz_submissions.not_draft')],
            ]);
        }

        $this->repository->updateSubmission($submission, [
            'session_token' => Str::random(60),
        ]);

        return $submission->fresh();
    }

    private function validateAnswerForQuestionType(QuizQuestion $question, array $data): void
    {
        $type = $question->type;
        $selectedOptions = $data['selected_options'] ?? null;
        $content = $data['content'] ?? null;

        match ($type) {
            QuizQuestionType::MultipleChoice => $this->validateMultipleChoiceAnswer($question, $selectedOptions, $content),
            QuizQuestionType::TrueFalse => $this->validateTrueFalseAnswer($selectedOptions, $content),
            QuizQuestionType::Checkbox => $this->validateCheckboxAnswer($question, $selectedOptions, $content),
            QuizQuestionType::Essay => $this->validateEssayAnswer($selectedOptions, $content),
        };
    }

    private function validateMultipleChoiceAnswer(QuizQuestion $question, ?array $selectedOptions, ?string $content): void
    {
        if ($content !== null) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'content' => [__('messages.quiz_answers.content_not_allowed')],
            ]);
        }

        if (empty($selectedOptions)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'selected_options' => [__('messages.quiz_answers.selected_options_required')],
            ]);
        }

        if (count($selectedOptions) !== 1) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'selected_options' => [__('messages.quiz_answers.single_option_only')],
            ]);
        }

        $optionsCount = is_array($question->options) ? count($question->options) : 0;
        $index = (int) $selectedOptions[0];

        if (! is_numeric($selectedOptions[0]) || $index < 0 || $index >= $optionsCount) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'selected_options' => [__('messages.quiz_answers.invalid_option_index', ['max' => $optionsCount - 1])],
            ]);
        }
    }

    private function validateTrueFalseAnswer(?array $selectedOptions, ?string $content): void
    {
        if ($content !== null) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'content' => [__('messages.quiz_answers.content_not_allowed')],
            ]);
        }

        if (empty($selectedOptions)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'selected_options' => [__('messages.quiz_answers.selected_options_required')],
            ]);
        }

        if (count($selectedOptions) !== 1) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'selected_options' => [__('messages.quiz_answers.single_option_only')],
            ]);
        }

        if (! in_array($selectedOptions[0], ['0', '1'], true)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'selected_options' => [__('messages.quiz_answers.true_false_invalid')],
            ]);
        }
    }

    private function validateCheckboxAnswer(QuizQuestion $question, ?array $selectedOptions, ?string $content): void
    {
        if ($content !== null) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'content' => [__('messages.quiz_answers.content_not_allowed')],
            ]);
        }

        if (empty($selectedOptions)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'selected_options' => [__('messages.quiz_answers.selected_options_required')],
            ]);
        }

        $optionsCount = is_array($question->options) ? count($question->options) : 0;

        if (count($selectedOptions) !== count(array_unique($selectedOptions))) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'selected_options' => [__('messages.quiz_answers.duplicate_options')],
            ]);
        }

        foreach ($selectedOptions as $option) {
            $index = (int) $option;

            if (! is_numeric($option) || $index < 0 || $index >= $optionsCount) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'selected_options' => [__('messages.quiz_answers.invalid_option_index', ['max' => $optionsCount - 1])],
                ]);
            }
        }
    }

    private function validateEssayAnswer(?array $selectedOptions, ?string $content): void
    {
        if (! empty($selectedOptions)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'selected_options' => [__('messages.quiz_answers.options_not_allowed_for_essay')],
            ]);
        }

        if ($content === null || trim($content) === '') {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'content' => [__('messages.quiz_answers.content_required')],
            ]);
        }
    }

    private function isTimeLimitExceeded(QuizSubmission $submission): bool
    {
        $quiz = $submission->quiz;

        if ($quiz->time_limit_minutes === null) {
            return false;
        }

        if ($submission->started_at === null) {
            return false;
        }

        $expiresAt = $submission->started_at
            ->copy()
            ->addMinutes($quiz->time_limit_minutes)
            ->addSeconds(self::TIMER_GRACE_SECONDS);

        return now()->gt($expiresAt);
    }
}
