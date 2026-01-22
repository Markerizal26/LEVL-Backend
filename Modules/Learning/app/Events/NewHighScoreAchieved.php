<?php

declare(strict_types=1);

namespace Modules\Learning\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Learning\Models\Submission;

class NewHighScoreAchieved
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Submission $submission,
        public readonly ?float $previousHighScore = null,
        public readonly float $newHighScore = 0.0
    ) {}
}
