<?php

declare(strict_types=1);

namespace Modules\Common\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Common\Contracts\Services\AuditServiceInterface;
use Modules\Learning\Events\AnswerKeyChanged;

/**
 * Listener to log answer key changes in the audit log.
 *
 * Requirements: 20.3 - THE System SHALL log all answer key changes and recalculations
 */
class LogAnswerKeyChanged implements ShouldQueue
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
    public function handle(AnswerKeyChanged $event): void
    {
        $this->auditService->logAnswerKeyChange(
            $event->question,
            $event->oldAnswerKey,
            $event->newAnswerKey,
            $event->instructorId
        );
    }
}
