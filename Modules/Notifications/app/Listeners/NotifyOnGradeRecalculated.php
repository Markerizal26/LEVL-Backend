<?php

declare(strict_types=1);

namespace Modules\Notifications\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Modules\Grading\Events\GradeRecalculated;
use Modules\Notifications\Contracts\Services\GradingNotificationServiceInterface;

class NotifyOnGradeRecalculated implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'notifications';

    public int $tries = 3;

    public int $maxExceptions = 2;

    public array $backoff = [5, 30, 120];

    public bool $afterCommit = true;

    public function __construct(
        private readonly GradingNotificationServiceInterface $notificationService
    ) {
    }

    public function handle(GradeRecalculated $event): void
    {
        $submission = $event->submission;
        $oldScore = $event->oldScore;
        $newScore = $event->newScore;

        $this->notificationService->notifyGradeRecalculated(
            $submission,
            $oldScore,
            $newScore
        );
    }
}
