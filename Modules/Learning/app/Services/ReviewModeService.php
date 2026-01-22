<?php

declare(strict_types=1);

namespace Modules\Learning\Services;

use Modules\Learning\Contracts\Services\ReviewModeServiceInterface;
use Modules\Learning\Enums\ReviewMode;
use Modules\Learning\Enums\SubmissionState;
use Modules\Learning\Models\Submission;

class ReviewModeService implements ReviewModeServiceInterface
{
        public function canViewAnswers(Submission $submission, ?int $userId = null): bool
    {
        $assignment = $submission->assignment;

        if (! $assignment) {
            return false;
        }

        
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

        public function canViewFeedback(Submission $submission, ?int $userId = null): bool
    {
        $assignment = $submission->assignment;

        if (! $assignment) {
            return false;
        }

        
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

        public function canViewScore(Submission $submission, ?int $userId = null): bool
    {
        
        return $this->isGraded($submission) || $this->isReleased($submission);
    }

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

        private function isReleased(Submission $submission): bool
    {
        return $submission->state === SubmissionState::Released;
    }

        private function isInstructor(?int $userId, $assignment): bool
    {
        if (! $userId) {
            return false;
        }

        
        if ($assignment->created_by === $userId) {
            return true;
        }

        
        

        return false;
    }
}
