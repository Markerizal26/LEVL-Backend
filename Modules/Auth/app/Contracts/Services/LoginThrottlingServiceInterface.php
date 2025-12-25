<?php

namespace Modules\Auth\Contracts\Services;

interface LoginThrottlingServiceInterface
{
    public function ensureNotLocked(string $login): void;

    public function tooManyAttempts(string $login, string $ip): bool;

    public function hitAttempt(string $login, string $ip): void;

    public function clearAttempts(string $login, string $ip): void;

    public function recordFailureAndMaybeLock(string $login): void;

    public function getRetryAfterSeconds(string $login, string $ip): int;

    /**
     * @return array{max:int,decay:int}
     */
    public function getRateLimitConfig(): array;

    public function getLockRemainingSeconds(string $login): int;

    /**
     * @return array{threshold:int,window:int,duration:int}
     */
    public function getLockConfig(): array;
}
