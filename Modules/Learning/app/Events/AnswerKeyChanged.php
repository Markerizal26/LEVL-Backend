<?php

declare(strict_types=1);

namespace Modules\Learning\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Learning\Models\Question;

/**
 * Event dispatched when an answer key is changed.
 *
 * Requirements: 20.3 - THE System SHALL log all answer key changes and recalculations
 */
class AnswerKeyChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param  Question  $question  The question with changed answer key
     * @param  array<string, mixed>  $oldAnswerKey  The previous answer key
     * @param  array<string, mixed>  $newAnswerKey  The new answer key
     * @param  int  $instructorId  The ID of the instructor who made the change
     */
    public function __construct(
        public readonly Question $question,
        public readonly array $oldAnswerKey,
        public readonly array $newAnswerKey,
        public readonly int $instructorId
    ) {}
}
