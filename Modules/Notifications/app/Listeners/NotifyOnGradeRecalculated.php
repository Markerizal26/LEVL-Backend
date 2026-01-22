<?php

declare(strict_types=1);

namespace Modules\Notifications\Listeners;

use Modules\Grading\Events\GradeRecalculated;
use Modules\Notifications\Contracts\Services\GradingNotificationServiceInterface;

/**
 * Listener for grade recalculated event to notify students.
 *
 * This listener handles notifications when grades are recalculated due to answer key changes.
 *
 * @see Requirements 15.5: THE System SHALL notify affected students of grade changes
 */
class NotifyOnGradeRecalculated
{
    public function __construct(
        private readonly GradingNotificationServiceInterface $notificationService
    ) {}

    /**
     * Handle the event.
     */
    public function handle(GradeRecalculated $event): void
    {
        $submission = $event->submission;
        $oldScore = $event->oldScore;
        $newScore = $event->newScore;

        $this->notificationService->notifyGradeRecalculated($submission, $oldScore, $newScore);
    }
}
