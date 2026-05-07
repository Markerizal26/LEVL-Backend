<?php

declare(strict_types=1);

namespace Modules\Forums\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Auth\Models\User;
use Modules\Forums\Models\Reply;

class ReplyAccepted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Reply $reply,
        public readonly User $actor
    ) {}
}
