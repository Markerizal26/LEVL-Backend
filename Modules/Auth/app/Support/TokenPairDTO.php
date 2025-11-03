<?php

namespace Modules\Auth\Support;

class TokenPairDTO
{
    public function __construct(
        public readonly string $accessToken,
        public readonly int $expiresIn,
        public readonly string $tokenType = 'bearer',
        public readonly ?string $refreshToken = null,
    ) {}

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        return [
            'access_token' => $this->accessToken,
            'token_type' => $this->tokenType,
            'expires_in' => $this->expiresIn,
            'refresh_token' => $this->refreshToken,
        ];
    }
}


