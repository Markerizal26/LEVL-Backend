<?php

declare(strict_types=1);

namespace Modules\Common\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Common\Contracts\Services\AuditServiceInterface;
use Modules\Learning\Events\OverrideGranted;

/**
 * Listener to log instructor override grants in the audit log.
 *
 * Requirements: 24.5 - THE System SHALL log all overrides in Audit_Log
 */
class LogOverrideGranted implements ShouldQueue
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
    public function handle(OverrideGranted $event): void
    {
        $override = $event->override;

        $this->auditService->logOverrideGrant(
            $override->assignment_id,
            $override->student_id,
            $override->type->value,
            $override->reason,
            $event->instructorId
        );
    }
}
