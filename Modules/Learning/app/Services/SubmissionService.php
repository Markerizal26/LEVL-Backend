<?php

declare(strict_types=1);

namespace Modules\Learning\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Auth\Models\User;
use Modules\Common\Models\SystemSetting;
use Modules\Enrollments\Contracts\Repositories\EnrollmentRepositoryInterface;
use Modules\Enrollments\Models\Enrollment;
use Modules\Learning\Contracts\Repositories\OverrideRepositoryInterface;
use Modules\Learning\Contracts\Repositories\SubmissionRepositoryInterface;
use Modules\Learning\Contracts\Services\QuestionServiceInterface;
use Modules\Learning\Contracts\Services\SubmissionServiceInterface;
use Modules\Learning\Enums\OverrideType;
use Modules\Learning\Enums\SubmissionState;
use Modules\Learning\Events\NewHighScoreAchieved;
use Modules\Learning\Events\SubmissionCreated;
use Modules\Learning\Models\Assignment;
use Modules\Learning\Models\Submission;
use Modules\Schemes\Models\Lesson;

class SubmissionService implements SubmissionServiceInterface
{
    public function __construct(
        private readonly SubmissionRepositoryInterface $repository,
        private readonly EnrollmentRepositoryInterface $enrollmentRepository,
        private readonly ?QuestionServiceInterface $questionService = null,
        private readonly ?OverrideRepositoryInterface $overrideRepository = null,
    ) {}

    public function listForAssignment(Assignment $assignment, User $user, array $filters = []): Collection
    {
        return $this->repository->listForAssignment($assignment, $user, $filters);
    }

    public function create(Assignment $assignment, int $userId, array $data): Submission
    {
        return DB::transaction(function () use ($assignment, $userId, $data) {
            $lesson = $assignment->lesson;
            if (! $lesson) {
                throw ValidationException::withMessages([
                    'assignment' => 'Assignment tidak memiliki lesson yang valid.',
                ]);
            }

            $enrollment = $this->getEnrollmentForLesson($lesson, $userId);
            if (! $enrollment) {
                throw ValidationException::withMessages([
                    'enrollment' => 'Anda belum terdaftar pada course ini.',
                ]);
            }

            if (! $assignment->isAvailable()) {
                throw ValidationException::withMessages([
                    'assignment' => 'Assignment belum tersedia atau belum dipublish.',
                ]);
            }

            $existingSubmission = $this->repository->latestCommittedSubmission($assignment, $userId);

            $allowResubmit = $assignment->allow_resubmit !== null
                ? (bool) $assignment->allow_resubmit
                : SystemSetting::get('learning.allow_resubmit', true);
            $isResubmission = $existingSubmission !== null;

            if ($isResubmission && ! $allowResubmit) {
                throw ValidationException::withMessages([
                    'submission' => 'Resubmission tidak diizinkan untuk assignment ini.',
                ]);
            }

            $attemptNumber = $isResubmission
                ? ($existingSubmission->attempt_number + 1)
                : 1;

            $isLate = $assignment->isPastDeadline();

            if ($isResubmission && $existingSubmission) {
                $this->repository->delete($existingSubmission);
            }

            // Generate question set for randomization
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
        if ($submission->status === \Modules\Learning\Enums\SubmissionStatus::Graded) {
            throw ValidationException::withMessages([
                'submission' => 'Submission yang sudah dinilai tidak dapat diubah.',
            ]);
        }

        $updated = $this->repository->update($submission, [
            'answer_text' => $data['answer_text'] ?? $submission->answer_text,
        ]);

        return $updated->fresh(['assignment', 'user', 'enrollment', 'files']);
    }

    public function grade(Submission $submission, int $score, int $gradedBy, ?string $feedback = null): Submission
    {
        $assignment = $submission->assignment;
        $maxScore = $assignment->max_score;

        if ($score < 0 || $score > $maxScore) {
            throw ValidationException::withMessages([
                'score' => "Score harus antara 0 dan {$maxScore}.",
            ]);
        }

        $finalScore = $score;
        if ($submission->is_late) {
            $assignmentPenalty = $assignment->late_penalty_percent;
            $latePenaltyPercent = $assignmentPenalty !== null
                ? (int) $assignmentPenalty
                : (int) SystemSetting::get('learning.late_penalty_percent', 0);
            if ($latePenaltyPercent > 0) {
                $penalty = ($score * $latePenaltyPercent) / 100;
                $finalScore = max(0, $score - $penalty);
            }
        }

        $grade = \Modules\Grading\Models\Grade::updateOrCreate(
            [
                'source_type' => 'assignment',
                'source_id' => $assignment->id,
                'user_id' => $submission->user_id,
            ],
            [
                'graded_by' => $gradedBy,
                'score' => $finalScore,
                'max_score' => $maxScore,
                'feedback' => $feedback,
                'status' => \Modules\Grading\Enums\GradeStatus::Graded,
                'graded_at' => Carbon::now(),
            ]
        );

        $updated = $this->repository->update($submission, [
            'status' => \Modules\Learning\Enums\SubmissionStatus::Graded->value,
        ])->fresh(['assignment', 'user', 'enrollment', 'files']);
        $updated->setRelation('grade', $grade);

        return $updated;
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

    /**
     * Start a new submission (creates in_progress state).
     */
    public function startSubmission(int $assignmentId, int $studentId): Submission
    {
        $assignment = Assignment::findOrFail($assignmentId);

        // Check deadline + tolerance (with override check - Requirement 24.3)
        if (! $this->checkDeadlineWithOverride($assignment, $studentId)) {
            throw ValidationException::withMessages([
                'deadline' => 'The deadline for this assignment has passed.',
            ]);
        }

        // Check attempt limits (with override check - Requirement 24.2)
        $attemptCheck = $this->checkAttemptLimitsWithOverride($assignment, $studentId);
        if (! $attemptCheck['allowed']) {
            throw ValidationException::withMessages([
                'attempts' => $attemptCheck['message'],
            ]);
        }

        // Check cooldown period
        $cooldownCheck = $this->checkCooldownPeriod($assignment, $studentId);
        if (! $cooldownCheck['allowed']) {
            throw ValidationException::withMessages([
                'cooldown' => $cooldownCheck['message'],
            ]);
        }

        // Check re-take mode
        if (! $assignment->retake_enabled) {
            $existingSubmission = Submission::where('assignment_id', $assignmentId)
                ->where('user_id', $studentId)
                ->whereNotIn('state', [SubmissionState::InProgress->value])
                ->exists();

            if ($existingSubmission) {
                throw ValidationException::withMessages([
                    'retake' => 'Re-take is not enabled for this assignment.',
                ]);
            }
        }

        // Generate question set
        $questionSet = null;
        if ($this->questionService) {
            $seed = random_int(1, PHP_INT_MAX);
            $questions = $this->questionService->generateQuestionSet($assignmentId, $seed);
            $questionSet = $questions->pluck('id')->toArray();
        }

        $attemptNumber = Submission::where('assignment_id', $assignmentId)
            ->where('user_id', $studentId)
            ->count() + 1;

        $submission = $this->repository->create([
            'assignment_id' => $assignmentId,
            'user_id' => $studentId,
            'state' => SubmissionState::InProgress->value,
            'status' => \Modules\Learning\Enums\SubmissionStatus::Draft->value,
            'attempt_number' => $attemptNumber,
            'question_set' => $questionSet,
        ]);

        return $submission->fresh(['assignment', 'user']);
    }

    /**
     * Submit answers for a submission.
     */
    public function submitAnswers(int $submissionId, array $answers): Submission
    {
        $submission = Submission::findOrFail($submissionId);
        $assignment = $submission->assignment;
        $studentId = $submission->user_id;

        if ($submission->state !== SubmissionState::InProgress) {
            throw ValidationException::withMessages([
                'state' => 'This submission cannot be modified.',
            ]);
        }

        $isLate = $this->isSubmissionLate($assignment, $studentId);
        if (! $this->checkDeadlineWithOverride($assignment, $studentId)) {
            throw ValidationException::withMessages([
                'deadline' => 'The deadline for this assignment has passed.',
            ]);
        }

        $submission->update([
            'is_late' => $isLate,
            'submitted_at' => Carbon::now(),
        ]);

        $submission->transitionTo(SubmissionState::Submitted, $studentId);

        return $submission->fresh(['assignment', 'user', 'answers']);
    }

    /**
     * Check attempt limits for a student.
     */
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
                'message' => 'You have reached the maximum number of attempts for this assignment.',
            ];
        }

        return ['allowed' => true, 'remaining' => $remaining];
    }

    /**
     * Check cooldown period between attempts.
     */
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
                'message' => "You must wait until {$nextAttemptAt->toDateTimeString()} before starting a new attempt.",
            ];
        }

        return ['allowed' => true, 'next_attempt_at' => null];
    }

    /**
     * Get the highest scoring submission for a student.
     * Requirements: 8.4, 22.1, 22.2
     */
    public function getHighestScoreSubmission(int $assignmentId, int $studentId): ?Submission
    {
        return $this->repository->findHighestScore($studentId, $assignmentId);
    }

    /**
     * Check if a submission is a new high score and dispatch event if so.
     * Requirements: 22.4, 22.5
     */
    public function checkAndDispatchNewHighScore(Submission $submission): void
    {
        if ($submission->score === null) {
            return;
        }

        // Get all other submissions for this student/assignment
        $otherSubmissions = Submission::query()
            ->where('assignment_id', $submission->assignment_id)
            ->where('user_id', $submission->user_id)
            ->where('id', '!=', $submission->id)
            ->whereNotNull('score')
            ->whereNotIn('state', [SubmissionState::InProgress->value])
            ->get();

        // Find the previous highest score
        $previousHighScore = $otherSubmissions->max('score');

        // If this submission's score is higher than all previous scores, dispatch event
        if ($previousHighScore === null || $submission->score > $previousHighScore) {
            NewHighScoreAchieved::dispatch(
                $submission,
                $previousHighScore,
                $submission->score
            );
        }
    }

    /**
     * Update submission score and check for new high score.
     * Requirements: 22.4, 22.5
     */
    public function updateSubmissionScore(Submission $submission, float $score): Submission
    {
        $submission->update(['score' => $score]);

        // Check if this is a new high score and trigger course grade recalculation
        $this->checkAndDispatchNewHighScore($submission);

        return $submission->fresh();
    }

    /**
     * Get all submissions for a student on an assignment with highest marked.
     * Requirements: 22.3
     */
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

    /**
     * Check deadline with override support.
     * Returns true if submission is allowed, false if deadline has passed.
     *
     * Requirement 24.3: Allow instructors to extend the deadline
     */
    public function checkDeadlineWithOverride(Assignment $assignment, int $studentId): bool
    {
        // First check if there's a deadline override for this student
        $extendedDeadline = $this->getExtendedDeadline($assignment->id, $studentId);

        if ($extendedDeadline !== null) {
            // Use the extended deadline instead of the original
            return now()->lte($extendedDeadline);
        }

        // No override, use standard deadline + tolerance check
        return ! $assignment->isPastTolerance();
    }

    /**
     * Check if a submission would be considered late (for marking purposes).
     * Takes into account deadline overrides.
     */
    public function isSubmissionLate(Assignment $assignment, int $studentId): bool
    {
        // Check if there's a deadline override
        $extendedDeadline = $this->getExtendedDeadline($assignment->id, $studentId);

        if ($extendedDeadline !== null) {
            // With an override, submission is late if it's after the extended deadline
            return now()->gt($extendedDeadline);
        }

        // No override, use standard deadline check
        return $assignment->isPastDeadline();
    }

    /**
     * Get the extended deadline for a student if they have a deadline override.
     *
     * Requirement 24.3: Allow instructors to extend the deadline
     */
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

    /**
     * Check attempt limits with override support.
     * Returns array with 'allowed' boolean and additional info.
     *
     * Requirement 24.2: Allow instructors to grant additional attempts
     */
    public function checkAttemptLimitsWithOverride(Assignment $assignment, int $studentId): array
    {
        if ($assignment->max_attempts === null) {
            return ['allowed' => true, 'remaining' => null];
        }

        $attemptCount = Submission::where('assignment_id', $assignment->id)
            ->where('user_id', $studentId)
            ->whereNotIn('state', [SubmissionState::InProgress->value])
            ->count();

        // Get additional attempts from override (Requirement 24.2)
        $additionalAttempts = $this->getAdditionalAttempts($assignment->id, $studentId);
        $effectiveMaxAttempts = $assignment->max_attempts + $additionalAttempts;

        $remaining = $effectiveMaxAttempts - $attemptCount;

        if ($remaining <= 0) {
            return [
                'allowed' => false,
                'remaining' => 0,
                'message' => 'You have reached the maximum number of attempts for this assignment.',
            ];
        }

        return [
            'allowed' => true,
            'remaining' => $remaining,
            'has_override' => $additionalAttempts > 0,
            'additional_attempts' => $additionalAttempts,
        ];
    }

    /**
     * Get additional attempts granted via override.
     *
     * Requirement 24.2: Allow instructors to grant additional attempts
     */
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

    /**
     * Check if a student has any active override for an assignment.
     */
    public function hasActiveOverride(int $assignmentId, int $studentId, OverrideType $type): bool
    {
        if ($this->overrideRepository === null) {
            return false;
        }

        return $this->overrideRepository->hasActiveOverride($assignmentId, $studentId, $type);
    }

    /**
     * Search submissions with filters.
     *
     * Requirements: 27.1, 27.2, 27.3, 27.4, 27.5, 27.6
     *
     * @param  string  $query  Search query for student name or email
     * @param  array<string, mixed>  $filters  Optional filters
     * @param  array<string, mixed>  $options  Optional pagination/sorting options
     * @return array{data: Collection, total: int, per_page: int, current_page: int, last_page: int}
     */
    public function searchSubmissions(string $query, array $filters = [], array $options = []): array
    {
        return $this->repository->search($query, $filters, $options);
    }

    /**
     * Delete a submission.
     */
    public function delete(Submission $submission): bool
    {
        return $this->repository->delete($submission);
    }

    /**
     * List submissions by assignment (legacy method).
     *
     * @deprecated Use listForAssignment instead
     */
    public function listByAssignment(Assignment $assignment, array $filters = [])
    {
        return $this->repository->listForAssignment($assignment, $filters);
    }
}
