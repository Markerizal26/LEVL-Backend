<?php

namespace Modules\Auth\Events;

class TokenRefreshed
{
    public function __construct(
        public readonly int $userId,
        public readonly int $refreshTokenId,
    ) {}
}


