<?php

declare(strict_types=1);

namespace Modules\Grading\Services;

use Illuminate\Support\Collection;
use InvalidArgumentException;
use Modules\Grading\Contracts\Repositories\AppealRepositoryInterface;
use Modules\Grading\Contracts\Services\AppealServiceInterface;
use Modules\Grading\Enums\AppealStatus;
use Modules\Grading\Models\Appeal;
use Modules\Learning\Models\Submission;

/**
 * Service for managing late submission appeals.
 *
 * Requirements: 17.1, 17.2, 17.3, 17.4, 17.5
 */
class AppealService implements AppealServiceInterface
{
    public function __construct(
        private readonly AppealRepositoryInterface $appealRepository
    ) {}

    /**
     * Submit an appeal for a late submission.
     *
     * Requirements: 17.1, 17.2
     *
     * @param  int  $submissionId  The ID of the rejected submission
     * @param  string  $reason  The reason for the appeal
     * @param  array  $documents  Optional supporting documents
     * @return Appeal The created appeal
     *
     * @throws InvalidArgumentException if reason is empty or submission is not eligible for appeal
     */
    public function submitAppeal(int $submissionId, string $reason, array $documents = []): Appeal
    {
        // Validate reason is not empty (Requirements 17.2)
        if (empty(trim($reason))) {
            throw new InvalidArgumentException('Appeal reason is required');
        }

        // Get the submission
        $submission = Submission::with(['assignment', 'user'])->findOrFail($submissionId);

        // Check if an appeal already exists for this submission
        $existingAppeal = $this->appealRepository->findBySubmission($submissionId);
        if ($existingAppeal) {
            throw new InvalidArgumentException('An appeal already exists for this submission');
        }

        // Validate submission is eligible for appeal (Requirements 17.1)
        // A submission is eligible if it was rejected for lateness (past tolerance)
        if (! $this->isEligibleForAppeal($submission)) {
            throw new InvalidArgumentException(
                'This submission is not eligible for appeal. Appeals are only allowed for submissions rejected due to lateness.'
            );
        }

        // Create the appeal
        $appeal = $this->appealRepository->create([
            'submission_id' => $submissionId,
            'student_id' => $submission->user_id,
            'reason' => trim($reason),
            'supporting_documents' => $documents,
            'status' => AppealStatus::Pending,
            'submitted_at' => now(),
        ]);

        // Notify instructors of pending appeal (Requirements 17.3)
        $this->notifyInstructorsOfAppeal($appeal);

        return $appeal;
    }

    /**
     * Approve an appeal and grant deadline extension.
     *
     * Requirements: 17.3, 17.4
     *
     * @param  int  $appealId  The ID of the appeal to approve
     * @param  int  $instructorId  The ID of the instructor approving the appeal
     *
     * @throws InvalidArgumentException if appeal is already decided
     */
    public function approveAppeal(int $appealId, int $instructorId): void
    {
        $appeal = $this->appealRepository->findById($appealId);

        if (! $appeal) {
            throw new InvalidArgumentException('Appeal not found');
        }

        if ($appeal->status->isDecided()) {
            throw new InvalidArgumentException('Appeal has already been decided');
        }

        // Approve the appeal using the model method
        $appeal->approve($instructorId);

        // Grant deadline extension to allow resubmission (Requirements 17.4)
        $this->grantDeadlineExtension($appeal);

        // Notify student of approval
        $this->notifyStudentOfDecision($appeal);
    }

    /**
     * Deny an appeal with a reason.
     *
     * Requirements: 17.5
     *
     * @param  int  $appealId  The ID of the appeal to deny
     * @param  int  $instructorId  The ID of the instructor denying the appeal
     * @param  string  $reason  The reason for denial
     *
     * @throws InvalidArgumentException if appeal is already decided or reason is empty
     */
    public function denyAppeal(int $appealId, int $instructorId, string $reason): void
    {
        if (empty(trim($reason))) {
            throw new InvalidArgumentException('Denial reason is required');
        }

        $appeal = $this->appealRepository->findById($appealId);

        if (! $appeal) {
            throw new InvalidArgumentException('Appeal not found');
        }

        if ($appeal->status->isDecided()) {
            throw new InvalidArgumentException('Appeal has already been decided');
        }

        // Deny the appeal using the model method
        $appeal->deny($instructorId, trim($reason));

        // Notify student of denial with reason (Requirements 17.5)
        $this->notifyStudentOfDecision($appeal);
    }

    /**
     * Get pending appeals for an instructor.
     *
     * Requirements: 17.3
     *
     * @param  int  $instructorId  The ID of the instructor
     * @return Collection Collection of pending appeals
     */
    public function getPendingAppeals(int $instructorId): Collection
    {
        return $this->appealRepository->findPendingForInstructor($instructorId);
    }

    /**
     * Check if a submission is eligible for appeal.
     *
     * A submission is eligible if it was rejected for lateness (past tolerance window).
     * Requirements: 17.1
     */
    private function isEligibleForAppeal(Submission $submission): bool
    {
        $assignment = $submission->assignment;

        if (! $assignment) {
            return false;
        }

        // Check if the assignment has a deadline
        if (! $assignment->deadline_at) {
            return false;
        }

        // Check if the submission was late (past tolerance)
        // A submission is eligible for appeal if:
        // 1. It was marked as late, OR
        // 2. The assignment is past its tolerance window
        if ($submission->is_late) {
            return true;
        }

        // Check if the assignment is past tolerance window
        return $assignment->isPastTolerance();
    }

    /**
     * Grant deadline extension to allow the student to submit despite the deadline.
     *
     * Requirements: 17.4
     *
     * This creates an override record that allows the student to submit
     * even though the deadline has passed.
     */
    private function grantDeadlineExtension(Appeal $appeal): void
    {
        $submission = $appeal->submission;

        if (! $submission) {
            return;
        }

        // Mark the submission as having an approved appeal
        // This allows the student to resubmit despite the deadline
        // The submission service should check for approved appeals when validating deadlines
        $submission->update([
            'is_late' => false, // Reset late flag since appeal was approved
        ]);

        // If the submission was rejected (not yet submitted), allow it to be submitted
        // by resetting its state to allow resubmission
        // Note: The actual resubmission logic should be handled by the SubmissionService
        // which should check for approved appeals when validating deadline constraints
    }

    /**
     * Notify instructors of a pending appeal.
     *
     * Requirements: 17.3
     */
    private function notifyInstructorsOfAppeal(Appeal $appeal): void
    {
        // Get the assignment creator (instructor)
        $submission = $appeal->submission;
        if (! $submission || ! $submission->assignment) {
            return;
        }

        $instructorId = $submission->assignment->created_by;

        if ($instructorId) {
            // Dispatch notification event
            // Note: The actual notification implementation should be in the Notification module
            // For now, we dispatch an event that can be listened to
            event(new \Modules\Grading\Events\AppealSubmitted($appeal, $instructorId));
        }
    }

    /**
     * Notify student of appeal decision.
     *
     * Requirements: 17.4, 17.5
     */
    private function notifyStudentOfDecision(Appeal $appeal): void
    {
        // Dispatch notification event
        // Note: The actual notification implementation should be in the Notification module
        event(new \Modules\Grading\Events\AppealDecided($appeal));
    }
}
