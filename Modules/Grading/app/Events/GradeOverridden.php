<?php

declare(strict_types=1);

namespace Modules\Grading\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Grading\Models\Grade;

/**
 * Event dispatched when a grade is overridden.
 *
 * Requirements: 20.4 - THE System SHALL log all grade overrides with reasons
 */
class GradeOverridden
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param  Grade  $grade  The overridden grade
     * @param  float  $oldScore  The original score before override
     * @param  float  $newScore  The new override score
     * @param  string  $reason  The reason for the override
     * @param  int  $instructorId  The ID of the instructor who performed the override
     */
    public function __construct(
        public readonly Grade $grade,
        public readonly float $oldScore,
        public readonly float $newScore,
        public readonly string $reason,
        public readonly int $instructorId
    ) {}
}
