<?php

declare(strict_types=1);

namespace Modules\Notifications\Listeners;

use Modules\Learning\Enums\SubmissionState;
use Modules\Learning\Events\SubmissionStateChanged;
use Modules\Notifications\Contracts\Services\GradingNotificationServiceInterface;

/**
 * Listener for submission state changes to trigger appropriate notifications.
 *
 * This listener handles notifications for:
 * - Submissions that require manual grading (Requirements 21.3)
 * - Submissions that have been graded (Requirements 21.1)
 *
 * @see Requirements 21.1: WHEN a submission is graded, THE System SHALL notify the student
 * @see Requirements 21.3: WHEN a submission requires manual grading, THE System SHALL notify assigned instructors
 */
class NotifyOnSubmissionStateChanged
{
    public function __construct(
        private readonly GradingNotificationServiceInterface $notificationService
    ) {}

    /**
     * Handle the event.
     */
    public function handle(SubmissionStateChanged $event): void
    {
        $submission = $event->submission;
        $newState = $event->newState;

        // Notify instructors when submission requires manual grading (Requirements 21.3)
        if ($newState === SubmissionState::PendingManualGrading) {
            $this->notificationService->notifyManualGradingRequired($submission);

            return;
        }

        // Notify student when submission is graded (Requirements 21.1)
        // This happens when transitioning to Graded state (after manual grading)
        // or AutoGraded state (after auto-grading with no manual questions)
        if ($newState === SubmissionState::Graded || $newState === SubmissionState::AutoGraded) {
            $this->notificationService->notifySubmissionGraded($submission);

            return;
        }
    }
}
