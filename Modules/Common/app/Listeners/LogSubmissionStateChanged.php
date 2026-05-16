<?php

declare(strict_types=1);

namespace Modules\Common\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Common\Contracts\Services\AuditServiceInterface;
use Modules\Learning\Events\SubmissionStateChanged;

class LogSubmissionStateChanged implements ShouldQueue
{
    use \Illuminate\Queue\InteractsWithQueue;

    public string $queue = 'audit';

    public int $tries = 3;

    public int $maxExceptions = 2;

    public array $backoff = [5, 30, 120];

    public bool $afterCommit = true;

    public function __construct(
        private readonly AuditServiceInterface $auditService
    ) {}

    
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
