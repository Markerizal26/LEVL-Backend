<?php

declare(strict_types=1);

namespace Modules\Learning\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Learning\Models\Override;

/**
 * Event dispatched when an instructor grants an override.
 *
 * Requirements: 24.5 - THE System SHALL log all overrides in Audit_Log
 */
class OverrideGranted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param  Override  $override  The granted override
     * @param  int  $instructorId  The ID of the instructor who granted the override
     */
    public function __construct(
        public readonly Override $override,
        public readonly int $instructorId
    ) {}
}
