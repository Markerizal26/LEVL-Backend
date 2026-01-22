<?php

declare(strict_types=1);

namespace Modules\Notifications\Listeners;

use Modules\Grading\Events\GradesReleased;
use Modules\Notifications\Contracts\Services\GradingNotificationServiceInterface;

/**
 * Listener for grades released event to notify students.
 *
 * This listener handles notifications when grades are released in deferred mode.
 *
 * @see Requirements 14.6: WHEN instructor releases grades in deferred mode, THE System SHALL notify students
 * @see Requirements 21.2: WHEN grades are released in deferred mode, THE System SHALL notify affected students
 */
class NotifyOnGradesReleased
{
    public function __construct(
        private readonly GradingNotificationServiceInterface $notificationService
    ) {}

    /**
     * Handle the event.
     */
    public function handle(GradesReleased $event): void
    {
        $submissions = $event->submissions;

        if ($submissions->isEmpty()) {
            return;
        }

        $this->notificationService->notifyGradesReleased($submissions);
    }
}
