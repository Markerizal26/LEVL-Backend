<?php

declare(strict_types=1);

namespace Modules\Learning\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Learning\Models\Question;

class AnswerKeyChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Question $question,
        public readonly array $oldAnswerKey,
        public readonly array $newAnswerKey,
        public readonly int $instructorId
    ) {}
}
