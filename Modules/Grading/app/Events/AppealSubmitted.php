<?php

declare(strict_types=1);

namespace Modules\Grading\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Grading\Models\Appeal;

/**
 * Event dispatched when an appeal is submitted.
 *
 * Requirements: 17.3 - THE System SHALL notify instructors of pending appeals
 */
class AppealSubmitted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param  Appeal  $appeal  The submitted appeal
     * @param  int  $instructorId  The ID of the instructor to notify
     */
    public function __construct(
        public readonly Appeal $appeal,
        public readonly int $instructorId
    ) {}
}
