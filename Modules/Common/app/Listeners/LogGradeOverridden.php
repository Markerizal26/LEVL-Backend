<?php

declare(strict_types=1);

namespace Modules\Common\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Common\Contracts\Services\AuditServiceInterface;
use Modules\Grading\Events\GradeOverridden;

/**
 * Listener to log grade overrides in the audit log.
 *
 * Requirements: 20.4 - THE System SHALL log all grade overrides with reasons
 */
class LogGradeOverridden implements ShouldQueue
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
    public function handle(GradeOverridden $event): void
    {
        $this->auditService->logGradeOverride(
            $event->grade,
            $event->oldScore,
            $event->newScore,
            $event->reason,
            $event->instructorId
        );
    }
}
