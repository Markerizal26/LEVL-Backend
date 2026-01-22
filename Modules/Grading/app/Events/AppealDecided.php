<?php

declare(strict_types=1);

namespace Modules\Grading\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Grading\Models\Appeal;

/**
 * Event dispatched when an appeal decision is made.
 *
 * Requirements: 17.4, 17.5 - THE System SHALL notify the student of appeal decisions
 */
class AppealDecided
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param  Appeal  $appeal  The decided appeal
     */
    public function __construct(
        public readonly Appeal $appeal
    ) {}

    /**
     * Check if the appeal was approved.
     */
    public function isApproved(): bool
    {
        return $this->appeal->isApproved();
    }

    /**
     * Check if the appeal was denied.
     */
    public function isDenied(): bool
    {
        return $this->appeal->isDenied();
    }

    /**
     * Get the decision reason (for denied appeals).
     */
    public function getDecisionReason(): ?string
    {
        return $this->appeal->decision_reason;
    }
}
