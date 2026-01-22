<?php

declare(strict_types=1);

namespace Modules\Learning\Services;

use Modules\Learning\Contracts\Services\ReviewModeServiceInterface;
use Modules\Learning\Enums\ReviewMode;
use Modules\Learning\Enums\SubmissionState;
use Modules\Learning\Models\Submission;

class ReviewModeService implements ReviewModeServiceInterface
{
    /**
     * Check if answers are visible for a submission based on review mode.
     *
     * Requirements 14.2, 14.3, 14.4:
     * - Immediate: Show answers immediately after submission
     * - Deferred: Show answers only after instructor releases grades
     * - Hidden: Never show answers to students
     */
    public function canViewAnswers(Submission $submission, ?int $userId = null): bool
    {
        $assignment = $submission->assignment;

        if (! $assignment) {
            return false;
        }

        // Instructors can always view answers
        if ($this->isInstructor($userId, $assignment)) {
            return true;
        }

        $reviewMode = $assignment->review_mode ?? ReviewMode::Immediate;

        return match ($reviewMode) {
            ReviewMode::Immediate => $this->isSubmitted($submission),
            ReviewMode::Deferred => $this->isReleased($submission),
            ReviewMode::Hidden => false,
        };
    }

    /**
     * Check if feedback is visible for a submission based on review mode.
     *
     * Requirements 13.3, 13.4, 14.2, 14.3, 14.4:
     * - Immediate: Show feedback immediately after grading
     * - Deferred: Show feedback only after instructor releases grades
     * - Hidden: Never show detailed feedback to students
     */
    public function canViewFeedback(Submission $submission, ?int $userId = null): bool
    {
        $assignment = $submission->assignment;

        if (! $assignment) {
            return false;
        }

        // Instructors can always view feedback
        if ($this->isInstructor($userId, $assignment)) {
            return true;
        }

        $reviewMode = $assignment->review_mode ?? ReviewMode::Immediate;

        return match ($reviewMode) {
            ReviewMode::Immediate => $this->isGraded($submission),
            ReviewMode::Deferred => $this->isReleased($submission),
            ReviewMode::Hidden => false,
        };
    }

    /**
     * Check if score is visible for a submission.
     * Score is always visible regardless of review mode (Requirements 14.5).
     */
    public function canViewScore(Submission $submission, ?int $userId = null): bool
    {
        // Score is always visible once graded (Requirements 14.5)
        return $this->isGraded($submission) || $this->isReleased($submission);
    }

    /**
     * Get the visibility status for a submission.
     */
    public function getVisibilityStatus(Submission $submission, ?int $userId = null): array
    {
        $assignment = $submission->assignment;
        $reviewMode = $assignment?->review_mode ?? ReviewMode::Immediate;

        return [
            'review_mode' => $reviewMode->value,
            'can_view_answers' => $this->canViewAnswers($submission, $userId),
            'can_view_feedback' => $this->canViewFeedback($submission, $userId),
            'can_view_score' => $this->canViewScore($submission, $userId),
            'is_released' => $this->isReleased($submission),
            'submission_state' => $submission->state?->value,
        ];
    }

    /**
     * Check if the submission has been submitted.
     */
    private function isSubmitted(Submission $submission): bool
    {
        $state = $submission->state;

        if (! $state) {
            return false;
        }

        return in_array($state, [
            SubmissionState::Submitted,
            SubmissionState::AutoGraded,
            SubmissionState::PendingManualGrading,
            SubmissionState::Graded,
            SubmissionState::Released,
        ], true);
    }

    /**
     * Check if the submission has been graded.
     */
    private function isGraded(Submission $submission): bool
    {
        $state = $submission->state;

        if (! $state) {
            return false;
        }

        return in_array($state, [
            SubmissionState::AutoGraded,
            SubmissionState::Graded,
            SubmissionState::Released,
        ], true);
    }

    /**
     * Check if the submission grades have been released.
     */
    private function isReleased(Submission $submission): bool
    {
        return $submission->state === SubmissionState::Released;
    }

    /**
     * Check if the user is an instructor for the assignment.
     */
    private function isInstructor(?int $userId, $assignment): bool
    {
        if (! $userId) {
            return false;
        }

        // Check if user is the creator of the assignment
        if ($assignment->created_by === $userId) {
            return true;
        }

        // Additional instructor checks could be added here
        // For example, checking course instructors, TAs, etc.

        return false;
    }
}
