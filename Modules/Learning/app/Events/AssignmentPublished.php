<?php

declare(strict_types=1);

namespace Modules\Learning\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Learning\Models\Assignment;

class AssignmentPublished
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Assignment $assignment
    ) {}
}

