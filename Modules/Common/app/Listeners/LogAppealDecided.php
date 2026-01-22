<?php

declare(strict_types=1);

namespace Modules\Common\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Common\Contracts\Services\AuditServiceInterface;
use Modules\Grading\Events\AppealDecided;

/**
 * Listener to log appeal decisions in the audit log.
 *
 * Requirements: 20.5 - THE System SHALL log all appeals and decisions
 */
class LogAppealDecided implements ShouldQueue
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
    public function handle(AppealDecided $event): void
    {
        $appeal = $event->appeal;
        $decision = $appeal->isApproved() ? 'approved' : 'denied';
        $instructorId = $appeal->reviewer_id;

        if ($instructorId) {
            $this->auditService->logAppealDecision($appeal, $decision, $instructorId);
        }
    }
}
