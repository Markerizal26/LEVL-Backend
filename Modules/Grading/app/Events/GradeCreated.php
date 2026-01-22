<?php

declare(strict_types=1);

namespace Modules\Grading\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Grading\Models\Grade;

/**
 * Event dispatched when a grade is created or updated.
 *
 * Requirements: 20.2 - THE System SHALL log all grading actions with instructor identity and timestamp
 */
class GradeCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param  Grade  $grade  The created/updated grade
     * @param  int  $instructorId  The ID of the instructor who performed the grading
     */
    public function __construct(
        public readonly Grade $grade,
        public readonly int $instructorId
    ) {}
}
