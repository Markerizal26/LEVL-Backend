<?php

declare(strict_types=1);

namespace Modules\Common\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Common\Contracts\Services\AuditServiceInterface;
use Modules\Learning\Events\SubmissionStateChanged;

/**
 * Listener to log submission state transitions in the audit log.
 *
 * Requirements: 20.1, 9.8 - THE System SHALL log all submission state transitions
 */
class LogSubmissionStateChanged implements ShouldQueue
{
    /**
     * The name of the queue the job should be sent to.
     */
    public string $queue = 'audit';

    public function __construct(
        private readonly AuditServiceInterface $auditService
    ) {}

    /**
     * Handle the event.
     */
    public function handle(SubmissionStateChanged $event): void
    {
        $oldState = $event->oldState !== null ? $event->oldState->value : 'none';
        $newState = $event->newState->value;
        $actorId = $event->actorId ?? (int) $event->submission->user_id;

        $this->auditService->logStateTransition(
            $event->submission,
            $oldState,
            $newState,
            $actorId
        );
    }
}
