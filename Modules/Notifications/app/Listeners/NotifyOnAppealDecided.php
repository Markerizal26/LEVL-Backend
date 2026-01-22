<?php

declare(strict_types=1);

namespace Modules\Notifications\Listeners;

use Modules\Grading\Events\AppealDecided;
use Modules\Notifications\Contracts\Services\GradingNotificationServiceInterface;

/**
 * Listener for appeal decided event to notify students.
 *
 * This listener handles notifications when an instructor decides on an appeal.
 *
 * @see Requirements 17.5: WHEN an instructor denies an appeal, THE System SHALL notify the student with reason
 * @see Requirements 21.5: WHEN an appeal is decided, THE System SHALL notify the student
 */
class NotifyOnAppealDecided
{
    public function __construct(
        private readonly GradingNotificationServiceInterface $notificationService
    ) {}

    /**
     * Handle the event.
     */
    public function handle(AppealDecided $event): void
    {
        $appeal = $event->appeal;

        $this->notificationService->notifyAppealDecision($appeal);
    }
}
