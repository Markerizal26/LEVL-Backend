<?php

declare(strict_types=1);

namespace Modules\Notifications\Listeners;

use Modules\Grading\Events\AppealSubmitted;
use Modules\Notifications\Contracts\Services\GradingNotificationServiceInterface;

/**
 * Listener for appeal submitted event to notify instructors.
 *
 * This listener handles notifications when a student submits an appeal.
 *
 * @see Requirements 17.3: THE System SHALL notify instructors of pending appeals
 * @see Requirements 21.4: WHEN an appeal is submitted, THE System SHALL notify instructors
 */
class NotifyOnAppealSubmitted
{
    public function __construct(
        private readonly GradingNotificationServiceInterface $notificationService
    ) {}

    /**
     * Handle the event.
     */
    public function handle(AppealSubmitted $event): void
    {
        $appeal = $event->appeal;

        $this->notificationService->notifyAppealSubmitted($appeal);
    }
}
