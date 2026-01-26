<?php

declare(strict_types=1);

namespace Modules\Learning\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Auth\Models\User;
use Modules\Common\Models\SystemSetting;
use Modules\Enrollments\Contracts\Repositories\EnrollmentRepositoryInterface;
use Modules\Enrollments\Models\Enrollment;
use Modules\Grading\Contracts\Services\GradingServiceInterface;
use Modules\Learning\Contracts\Repositories\OverrideRepositoryInterface;
use Modules\Learning\Contracts\Repositories\QuestionRepositoryInterface;
use Modules\Learning\Contracts\Repositories\SubmissionRepositoryInterface;
use Modules\Learning\Contracts\Services\QuestionServiceInterface;
use Modules\Learning\Contracts\Services\SubmissionServiceInterface;
use Modules\Learning\Enums\OverrideType;
use Modules\Learning\Enums\SubmissionState;
use Modules\Learning\Events\NewHighScoreAchieved;
use Modules\Learning\Events\SubmissionCreated;
use Modules\Learning\Exceptions\SubmissionException;
use Modules\Learning\Models\Assignment;
use Modules\Learning\Models\Question;
use Modules\Learning\Models\Submission;
use Modules\Schemes\Models\Lesson;

class SubmissionService implements SubmissionServiceInterface
{
    private const TIMER_GRACE_SECONDS = 60;

    public function __construct(
        private readonly SubmissionRepositoryInterface $repository,
        private readonly EnrollmentRepositoryInterface $enrollmentRepository,
        private readonly GradingServiceInterface $gradingService,
        private readonly QuestionRepositoryInterface $questionRepository,
        private readonly ?QuestionServiceInterface $questionService = null,
        private readonly ?OverrideRepositoryInterface $overrideRepository = null,
        private readonly ?\Modules\Learning\Contracts\Services\ReviewModeServiceInterface $reviewModeService = null,
    ) {}

    public function listForAssignment(Assignment $assignment, User $user, array $filters = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return $this->listForAssignmentForIndex($assignment, $user, $filters);
    }

    public function listForAssignmentForIndex(Assignment $assignment, User $user, array $filters = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $perPage = (int) data_get($filters, 'per_page', 15);
        $perPage = max(1, $perPage);

        $query = \Spatie\QueryBuilder\QueryBuilder::for(
            Submission::class
        )
            ->allowedFilters([
                \Spatie\QueryBuilder\AllowedFilter::exact('status'),
                \Spatie\QueryBuilder\AllowedFilter::exact('user_id'),
                \Spatie\QueryBuilder\AllowedFilter::exact('is_late'),
                \Spatie\QueryBuilder\AllowedFilter::scope('score_range', 'filterByScoreRange'), // Needs scope in model
            ])
            ->allowedSorts(['submitted_at', 'created_at', 'score', 'status'])
            ->defaultSort('-submitted_at')
            ->where('assignment_id', $assignment->id)
            ->with(['user:id,name,email', 'grade:id,submission_id,score,status,released_at']);

        if ($user->hasRole('Student')) {
            $query->where('user_id', $user->id);
        }

        return $query->paginate($perPage);
    }

    public function create(Assignment $assignment, int $userId, array $data): Submission
    {
        return DB::transaction(function () use ($assignment, $userId, $data) {
            $lesson = $assignment->lesson;
            if (! $lesson) {
                throw SubmissionException::notAllowed(__('messages.submissions.assignment_no_lesson'));
            }

            $enrollment = $this->getEnrollmentForLesson($lesson, $userId);
            if (! $enrollment) {
                throw SubmissionException::notAllowed(__('messages.submissions.not_enrolled'));
            }

            if (! $assignment->isAvailable()) {
                throw SubmissionException::notAllowed(__('messages.submissions.assignment_unavailable'));
            }

            $existingSubmission = $this->repository->latestCommittedSubmission($assignment, $userId);

            $allowResubmit = $assignment->allow_resubmit !== null
                ? (bool) $assignment->allow_resubmit
                : SystemSetting::get('learning.allow_resubmit', true);
            $isResubmission = $existingSubmission !== null;

            if ($isResubmission && ! $allowResubmit) {
                throw SubmissionException::notAllowed(__('messages.submissions.resubmission_not_allowed'));
            }

            $attemptNumber = $isResubmission
                ? ($existingSubmission->attempt_number + 1)
                : 1;

            $isLate = $assignment->isPastDeadline();

            if ($isResubmission && $existingSubmission) {
                $this->repository->delete($existingSubmission);
            }

            $questionSet = null;
            if ($this->questionService) {
                $seed = random_int(1, PHP_INT_MAX);
                $questions = $this->questionService->generateQuestionSet($assignment->id, $seed);
                $questionSet = $questions->pluck('id')->toArray();
            }

            $submission = $this->repository->create([
                'assignment_id' => $assignment->id,
                'user_id' => $userId,
                'enrollment_id' => $enrollment->id,
                'answer_text' => $data['answer_text'] ?? null,
                'status' => $isLate
                    ? \Modules\Learning\Enums\SubmissionStatus::Late->value
                    : \Modules\Learning\Enums\SubmissionStatus::Submitted->value,
                'attempt_number' => $attemptNumber,
                'is_late' => $isLate,
                'is_resubmission' => $isResubmission,
                'previous_submission_id' => null,
                'submitted_at' => Carbon::now(),
                'question_set' => $questionSet,
            ]);

            $this->incrementLessonProgressAttempt($enrollment->id, $lesson->id);

            SubmissionCreated::dispatch($submission);

            return $submission->fresh(['assignment', 'user', 'enrollment', 'files', 'grade']);
        });
    }

    public function update(Submission $submission, array $data): Submission
    {
        return DB::transaction(function () use ($submission, $data) {
            if ($submission->status === \Modules\Learning\Enums\SubmissionStatus::Graded) {
                throw SubmissionException::alreadyGraded();
            }

            $updated = $this->repository->update($submission, [
                'answer_text' => $data['answer_text'] ?? $submission->answer_text,
            ]);

            return $updated->fresh(['assignment', 'user', 'enrollment', 'files']);
        });
    }

    public function getSubmission(int $submissionId, array $filters = []): Submission
    {
        // Use Spatie Query Builder for eager loading and field selection
        // We can pass the base query or class
        $submission = \Spatie\QueryBuilder\QueryBuilder::for(Submission::class)
            ->where('id', $submissionId)
            ->allowedIncludes([
                'assignment',
                'answers',
                'answers.question',
                'user',
                'enrollment',
                'grade',
                'files'
            ])
            ->firstOrFail();

        return $submission;
    }

    public function grade(Submission $submission, int $score, int $gradedBy, ?string $feedback = null): Submission
    {
        return DB::transaction(function () use ($submission, $score, $gradedBy, $feedback) {
            $assignment = $submission->assignment;
            $maxScore = $assignment->max_score;

            if ($score < 0 || $score > $maxScore) {
                throw SubmissionException::invalidScore(__('messages.submissions.score_out_of_range', ['max' => $maxScore]));
            }

            $grade = $this->gradingService->manualGrade(
                $submission->id,
                ['score' => $score],
                $feedback
            );

            $updated = $this->repository->update($submission, [
                'status' => \Modules\Learning\Enums\SubmissionStatus::Graded->value,
            ])->fresh(['assignment', 'user', 'enrollment', 'files']);
            $updated->setRelation('grade', $grade);

            return $updated;
        });
    }

    private function getEnrollmentForLesson(Lesson $lesson, int $userId): ?Enrollment
    {
        $lesson->loadMissing('unit.course');

        if (! $lesson->unit || ! $lesson->unit->course) {
            return null;
        }

        return $this->enrollmentRepository->findActiveByUserAndCourse($userId, $lesson->unit->course->id);
    }

    private function incrementLessonProgressAttempt(int $enrollmentId, int $lessonId): void
    {
        $this->enrollmentRepository->incrementLessonProgress($enrollmentId, $lessonId);
    }

        public function startSubmission(int $assignmentId, int $studentId): Submission
    {
        $assignment = Assignment::findOrFail($assignmentId);

        if (! $this->checkDeadlineWithOverride($assignment, $studentId)) {
            throw SubmissionException::deadlinePassed();
        }

        $attemptCheck = $this->checkAttemptLimitsWithOverride($assignment, $studentId);
        if (! $attemptCheck['allowed']) {
            throw SubmissionException::maxAttemptsReached(
                $attemptCheck['message'] ?? __('messages.submissions.max_attempts_reached')
            );
        }

        $cooldownCheck = $this->checkCooldownPeriod($assignment, $studentId);
        if (! $cooldownCheck['allowed']) {
            throw SubmissionException::notAllowed(
                $attemptCheck['message'] ?? __('messages.submissions.cooldown_active', ['time' => $cooldownCheck['next_attempt_at']->toDateTimeString()])
            );
        }



        $questionSet = null;
        if ($this->questionService) {
            $seed = random_int(1, PHP_INT_MAX);
            $questions = $this->questionService->generateQuestionSet($assignmentId, $seed);
            $questionSet = $questions->pluck('id')->toArray();
        }

        $attemptNumber = Submission::where('assignment_id', $assignmentId)
            ->where('user_id', $studentId)
            ->count() + 1;

        return DB::transaction(function () use ($assignmentId, $studentId, $attemptNumber, $questionSet) {
            $submission = $this->repository->create([
                'assignment_id' => $assignmentId,
                'user_id' => $studentId,
                'state' => SubmissionState::InProgress->value,
                'status' => \Modules\Learning\Enums\SubmissionStatus::Draft->value,
                'attempt_number' => $attemptNumber,
                'question_set' => $questionSet,
            ]);

            return $submission->fresh(['assignment', 'user']);
        });
    }

    public function saveAnswer(Submission $submission, int $questionId, mixed $answer): \Modules\Learning\Models\Answer
    {
        return DB::transaction(function () use ($submission, $questionId, $answer) {
            $assignment = $submission->assignment;
            $studentId = $submission->user_id;

            if ($submission->state !== SubmissionState::InProgress) {
                throw SubmissionException::notAllowed(__('messages.submissions.cannot_modify'));
            }

            if (!$this->checkDeadlineWithOverride($assignment, $studentId)) {
                throw SubmissionException::deadlinePassed();
            }

            if ($assignment->time_limit_minutes !== null) {
                $limitEnds = $submission->created_at?->copy()
                    ->addMinutes($assignment->time_limit_minutes)
                    ->addSeconds(self::TIMER_GRACE_SECONDS);
                if ($limitEnds && now()->gt($limitEnds)) {
                    throw SubmissionException::timerExpired();
                }
            }

            if ($submission->question_set && ! in_array($questionId, $submission->question_set)) {
                throw new \Illuminate\Database\Eloquent\ModelNotFoundException(__('messages.submissions.question_not_in_set'));
            } elseif (! $submission->question_set) {
                $exists = Question::where('id', $questionId)
                    ->where('assignment_id', $assignment->id)
                    ->exists();
                if (! $exists) {
                    throw new \Illuminate\Database\Eloquent\ModelNotFoundException(__('messages.submissions.question_not_in_assignment'));
                }
            }
            
            $question = Question::find($questionId);

            $data = [
                'submission_id' => $submission->id,
                'question_id' => $questionId,
            ];

            if ($question->type->requiresOptions()) {
                $data['selected_options'] = (array) $answer;
            } elseif ($question->type === \Modules\Learning\Enums\QuestionType::FileUpload) {
                if (is_array($answer) && ! empty($answer) && is_string(reset($answer))) {
                    $data['file_paths'] = $answer;
                }
            } else {
                 $data['content'] = (string) $answer;
            }
            
            $createdAnswer = \Modules\Learning\Models\Answer::updateOrCreate(
                ['submission_id' => $submission->id, 'question_id' => $questionId],
                $data
            );

            if ($question->type === \Modules\Learning\Enums\QuestionType::FileUpload && $answer) {
                $createdAnswer->clearMediaCollection('answers');
                $files = is_array($answer) ? $answer : [$answer];
                $paths = [];
                
                foreach ($files as $file) {
                    if ($file instanceof UploadedFile) {
                        $media = $createdAnswer->addMedia($file)
                            ->toMediaCollection('answers', config('filesystems.default', 'do'));
                        $paths[] = $media->getUrl();
                    }
                }
                
                $createdAnswer->update(['file_paths' => $paths]);
            }

            return $createdAnswer->withoutRelations();
        });
    }

        public function getSubmissionQuestions(Submission $submission): \Illuminate\Support\Collection
    {
        $questionIds = $submission->question_set;

        if (empty($questionIds)) {
            $questions = $this->questionRepository->findByAssignment($submission->assignment_id);
        } else {
            $questions = Question::whereIn('id', $questionIds)
                ->with(['assignment', 'media'])
                ->get();

            $questions = $questions->sortBy(function ($question) use ($questionIds) {
                return array_search($question->id, $questionIds);
            })->values();
        }

        $submission->load('answers');
        $answers = $submission->answers->keyBy('question_id');

        $questions->each(function ($question) use ($answers) {
            $question->current_answer = $answers->get($question->id);
        });

        return $questions;
    }

    public function getSubmissionQuestionsPaginated(Submission $submission, int $perPage = 1): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $questionIds = $submission->question_set;

        if (empty($questionIds)) {
            $query = Question::where('assignment_id', $submission->assignment_id)
                ->with(['assignment', 'media'])
                ->orderBy('order');
        } else {
            $query = Question::whereIn('id', $questionIds)
                ->with(['assignment', 'media'])
                ->orderByRaw('array_position(ARRAY[' . implode(',', $questionIds) . '], id)');
        }

        $paginator = $query->paginate($perPage);

        $submission->load('answers');
        $answers = $submission->answers->keyBy('question_id');

        $paginator->getCollection()->each(function ($question) use ($answers) {
            $question->current_answer = $answers->get($question->id);
        });

        return $paginator;
    }

    public function submitAnswers(int $submissionId, array $answers): Submission
    {
        $submission = DB::transaction(function () use ($submissionId, $answers) {
            $submission = Submission::findOrFail($submissionId);
            $assignment = $submission->assignment;
            $studentId = $submission->user_id;

            if ($submission->state !== SubmissionState::InProgress) {
                throw SubmissionException::notAllowed(__('messages.submissions.cannot_modify'));
            }

            // Save provided answers if any
            if (! empty($answers)) {
                foreach ($answers as $answerData) {
                    if (empty($answerData['question_id'])) {
                        continue;
                    }

                    $val = null;
                    if (isset($answerData['selected_options'])) {
                        $val = $answerData['selected_options'];
                    } elseif (isset($answerData['file_paths'])) {
                        $val = $answerData['file_paths'];
                    } elseif (isset($answerData['content'])) {
                        $val = $answerData['content'];
                    }

                    if ($val !== null) {
                        try {
                            $this->saveAnswer($submission, (int) $answerData['question_id'], $val);
                        } catch (\Exception $e) {
                           throw $e;
                        }
                    }
                }
            }

            $questionIds = $submission->question_set;
            if (empty($questionIds)) {
                $questionIds = $this->questionRepository->findByAssignment($assignment->id)->pluck('id')->toArray();
            }

            $answeredQuestionIds = $submission->answers()->pluck('question_id')->toArray();

            $unansweredQuestions = array_diff($questionIds, $answeredQuestionIds);

            if (! empty($unansweredQuestions)) {
                 throw SubmissionException::notAllowed(__('messages.submissions.incomplete_answers'));
            }

            $isLate = $this->isSubmissionLate($assignment, $studentId);
            if (! $this->checkDeadlineWithOverride($assignment, $studentId)) {
                throw SubmissionException::deadlinePassed();
            }

            if ($assignment->time_limit_minutes !== null) {
                $limitEnds = $submission->created_at?->copy()
                    ->addMinutes($assignment->time_limit_minutes)
                    ->addSeconds(self::TIMER_GRACE_SECONDS);
                if ($limitEnds && now()->gt($limitEnds)) {
                    throw SubmissionException::timerExpired();
                }
            }

            $submission->update([
                'is_late' => $isLate,
                'submitted_at' => Carbon::now(),
            ]);

            $submission->transitionTo(SubmissionState::Submitted, $studentId);

            return $submission->fresh(['assignment', 'user', 'answers']);
        });

        // Auto-grade the submission after the transaction completes
        if ($submission->state === SubmissionState::Submitted || $submission->state === SubmissionState::PendingManualGrading) {
            try {
                $this->gradingService->autoGrade($submission->id);
                $submission->refresh();
            } catch (\Exception $e) {
                report($e);
            }
        }

        return $submission->fresh(['assignment', 'user', 'answers']);
    }

        public function checkAttemptLimits(Assignment $assignment, int $studentId): array
    {
        if ($assignment->max_attempts === null) {
            return ['allowed' => true, 'remaining' => null];
        }

        $attemptCount = Submission::where('assignment_id', $assignment->id)
            ->where('user_id', $studentId)
            ->whereNotIn('state', [SubmissionState::InProgress->value])
            ->count();

        $remaining = $assignment->max_attempts - $attemptCount;

        if ($remaining <= 0) {
            return [
                'allowed' => false,
                'remaining' => 0,
                'message' => __('messages.submissions.max_attempts_reached'),
            ];
        }

        return ['allowed' => true, 'remaining' => $remaining];
    }

        public function checkCooldownPeriod(Assignment $assignment, int $studentId): array
    {
        if ($assignment->cooldown_minutes <= 0) {
            return ['allowed' => true, 'next_attempt_at' => null];
        }

        $lastSubmission = Submission::where('assignment_id', $assignment->id)
            ->where('user_id', $studentId)
            ->whereNotNull('submitted_at')
            ->orderByDesc('submitted_at')
            ->first();

        if (! $lastSubmission) {
            return ['allowed' => true, 'next_attempt_at' => null];
        }

        $nextAttemptAt = $lastSubmission->submitted_at->copy()
            ->addMinutes($assignment->cooldown_minutes);

        if (now()->lt($nextAttemptAt)) {
            return [
                'allowed' => false,
                'next_attempt_at' => $nextAttemptAt,
                'message' => __('messages.submissions.cooldown_active', ['time' => $nextAttemptAt->toDateTimeString()]),
            ];
        }

        return ['allowed' => true, 'next_attempt_at' => null];
    }

        public function getHighestScoreSubmission(int $assignmentId, int $studentId): ?Submission
    {
        return $this->repository->findHighestScore($studentId, $assignmentId);
    }

        public function checkAndDispatchNewHighScore(Submission $submission): void
    {
        if ($submission->score === null) {
            return;
        }

        $otherSubmissions = Submission::query()
            ->where('assignment_id', $submission->assignment_id)
            ->where('user_id', $submission->user_id)
            ->where('id', '!=', $submission->id)
            ->whereNotNull('score')
            ->whereNotIn('state', [SubmissionState::InProgress->value])
            ->get();

        $previousHighScore = $otherSubmissions->max('score');

        if ($previousHighScore === null || $submission->score > $previousHighScore) {
            NewHighScoreAchieved::dispatch(
                $submission,
                $previousHighScore,
                $submission->score
            );
        }
    }

        public function updateSubmissionScore(Submission $submission, float $score): Submission
    {
        $submission->update(['score' => $score]);

        $this->checkAndDispatchNewHighScore($submission);

        return $submission->fresh();
    }

        public function getSubmissionsWithHighestMarked(int $assignmentId, int $studentId): Collection
    {
        $submissions = $this->repository->findByStudentAndAssignment($studentId, $assignmentId);

        if ($submissions->isEmpty()) {
            return $submissions;
        }

        $highestScore = $submissions->max('score');

        return $submissions->map(function ($submission) use ($highestScore) {
            $submission->is_highest = $submission->score !== null && $submission->score === $highestScore;

            return $submission;
        });
    }

        public function checkDeadlineWithOverride(Assignment $assignment, int $studentId): bool
    {
        $extendedDeadline = $this->getExtendedDeadline($assignment->id, $studentId);

        if ($extendedDeadline !== null) {
            return now()->lte($extendedDeadline);
        }

        return ! $assignment->isPastTolerance();
    }

        public function isSubmissionLate(Assignment $assignment, int $studentId): bool
    {
        $extendedDeadline = $this->getExtendedDeadline($assignment->id, $studentId);

        if ($extendedDeadline !== null) {
            return now()->gt($extendedDeadline);
        }

        return $assignment->isPastDeadline();
    }

        private function getExtendedDeadline(int $assignmentId, int $studentId): ?Carbon
    {
        if ($this->overrideRepository === null) {
            return null;
        }

        $override = $this->overrideRepository->findActiveOverride(
            $assignmentId,
            $studentId,
            OverrideType::Deadline
        );

        return $override?->getExtendedDeadline();
    }

        public function checkAttemptLimitsWithOverride(Assignment $assignment, int $studentId): array
    {
        if (! $assignment->retake_enabled) {
            $hasSubmitted = Submission::where('assignment_id', $assignment->id)
                ->where('user_id', $studentId)
                ->whereNotIn('state', [SubmissionState::InProgress->value])
                ->exists();

            if ($hasSubmitted) {
                return [
                    'allowed' => false,
                    'remaining' => 0,
                    'message' => __('messages.submissions.retake_not_enabled'),
                ];
            }
        }

        if ($assignment->max_attempts === null) {
            return ['allowed' => true, 'remaining' => null];
        }

        $attemptCount = Submission::where('assignment_id', $assignment->id)
            ->where('user_id', $studentId)
            ->whereNotIn('state', [SubmissionState::InProgress->value])
            ->count();

        $additionalAttempts = $this->getAdditionalAttempts($assignment->id, $studentId);
        $effectiveMaxAttempts = $assignment->max_attempts + $additionalAttempts;

        $remaining = $effectiveMaxAttempts - $attemptCount;

        if ($remaining <= 0) {
            return [
                'allowed' => false,
                'remaining' => 0,
                'message' => __('messages.submissions.max_attempts_reached'),
            ];
        }

        return [
            'allowed' => true,
            'remaining' => $remaining,
            'has_override' => $additionalAttempts > 0,
            'additional_attempts' => $additionalAttempts,
        ];
    }

        private function getAdditionalAttempts(int $assignmentId, int $studentId): int
    {
        if ($this->overrideRepository === null) {
            return 0;
        }

        $override = $this->overrideRepository->findActiveOverride(
            $assignmentId,
            $studentId,
            OverrideType::Attempts
        );

        return $override?->getAdditionalAttempts() ?? 0;
    }

        public function hasActiveOverride(int $assignmentId, int $studentId, OverrideType $type): bool
    {
        if ($this->overrideRepository === null) {
            return false;
        }

        return $this->overrideRepository->hasActiveOverride($assignmentId, $studentId, $type);
    }

        public function searchSubmissions(string $query, array $filters = [], array $options = []): array
    {
        return $this->repository->search($query, $filters, $options);
    }

        public function delete(Submission $submission): bool
    {
        return DB::transaction(fn() => $this->repository->delete($submission));
    }

        public function listByAssignment(Assignment $assignment, array $filters = [])
    {
        return $this->repository->listForAssignment($assignment, null, $filters);
    }

    public function getSubmissionDetail(Submission $submission, ?int $userId): array
    {
        $submission = $this->getSubmission($submission->id, []);
        $submission->loadMissing(['assignment', 'answers.question']);

        $visibility = $this->reviewModeService?->getVisibilityStatus($submission, $userId) ?? [];

        return [
            'submission' => $submission,
            'visibility' => $visibility,
        ];
    }

    public function getDeadlineStatus(Assignment $assignment, int $userId): array
    {
         return [
            'allowed' => $this->checkDeadlineWithOverride($assignment, $userId),
            'deadline' => $assignment->deadline_at?->toIso8601String(),
            'tolerance_minutes' => $assignment->tolerance_minutes,
        ];
    }
}
