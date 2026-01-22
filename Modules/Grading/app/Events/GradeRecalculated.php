<?php

declare(strict_types=1);

namespace Modules\Grading\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Learning\Models\Submission;

/**
 * Event dispatched when a grade is recalculated due to answer key changes.
 *
 * Requirements: 15.5 - THE System SHALL notify affected students of grade changes
 */
class GradeRecalculated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param  Submission  $submission  The submission with recalculated grade
     * @param  float  $oldScore  The previous score before recalculation
     * @param  float  $newScore  The new score after recalculation
     */
    public function __construct(
        public readonly Submission $submission,
        public readonly float $oldScore,
        public readonly float $newScore
    ) {}
}
