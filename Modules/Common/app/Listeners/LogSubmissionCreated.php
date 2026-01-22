<?php

declare(strict_types=1);

namespace Modules\Common\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Common\Contracts\Services\AuditServiceInterface;
use Modules\Learning\Events\SubmissionCreated;

/**
 * Listener to log submission creation in the audit log.
 *
 * Requirements: 20.1 - THE System SHALL log all submission creations
 */
class LogSubmissionCreated implements ShouldQueue
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
    public function handle(SubmissionCreated $event): void
    {
        $this->auditService->logSubmissionCreated($event->submission);
    }
}
