<?php

declare(strict_types=1);

namespace Modules\Learning\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Learning\Enums\SubmissionState;
use Modules\Learning\Models\Submission;

class SubmissionStateChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Submission $submission,
        public readonly ?SubmissionState $oldState,
        public readonly SubmissionState $newState,
        public readonly ?int $actorId = null
    ) {}
}
