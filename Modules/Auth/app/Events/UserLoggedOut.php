<?php

namespace Modules\Auth\Events;

class UserLoggedOut
{
    public function __construct(
        public readonly int $userId,
    ) {}
}


