<?php

declare(strict_types=1);

namespace Modules\Grading\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

/**
 * Event dispatched when grades are released in deferred mode.
 *
 * Requirements: 14.6 - WHEN instructor releases grades in deferred mode, THE System SHALL notify students
 */
class GradesReleased
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param  Collection<int, \Modules\Learning\Models\Submission>  $submissions  The submissions whose grades are being released
     * @param  int|null  $instructorId  The ID of the instructor who released the grades
     */
    public function __construct(
        public readonly Collection $submissions,
        public readonly ?int $instructorId = null
    ) {}
}
