<?php

namespace Modules\Auth\Services;

use Illuminate\Cache\RateLimiter;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Auth\Contracts\Services\LoginThrottlingServiceInterface;
use Modules\Common\Models\SystemSetting;

class LoginThrottlingService implements LoginThrottlingServiceInterface
{
    public function __construct(private readonly RateLimiter $rateLimiter)
    {
    }

    protected function makeKey(string $login, string $ip): string
    {
        $normalizedLogin = Str::lower(trim($login));
        return 'auth:login:' . sha1($normalizedLogin . '|' . $ip);
    }

    protected function lockKey(string $login): string
    {
        return 'auth:lock:' . sha1(Str::lower(trim($login)));
    }

    protected function failuresKey(string $login): string
    {
        return 'auth:failures:' . sha1(Str::lower(trim($login)));
    }

    public function ensureNotLocked(string $login): void
    {
        $lockKey = $this->lockKey($login);
        $unlockAtTs = Cache::get($lockKey);
        if ($unlockAtTs) {
            $remaining = max(0, $unlockAtTs - time());
            $threshold = (int) SystemSetting::get('auth.lockout_failed_attempts_threshold', 5);
            $window = (int) SystemSetting::get('auth.lockout_window_minutes', 60);
            $minutes = intdiv($remaining, 60);
            $seconds = $remaining % 60;
            $retryIn = $minutes > 0 ? ($minutes . ' menit' . ($seconds > 0 ? ' ' . $seconds . ' detik' : '')) : ($seconds . ' detik');
            throw ValidationException::withMessages([
                'login' => "Akun terkunci sementara (gagal >= {$threshold} kali dalam {$window} menit). Coba lagi dalam {$retryIn}.",
            ]);
        }
    }

    public function tooManyAttempts(string $login, string $ip): bool
    {
        if (!SystemSetting::get('auth.login_rate_limit_enabled', true)) {
            return false;
        }

        $maxAttempts = (int) SystemSetting::get('auth.login_rate_limit_max_attempts', 5);
        $key = $this->makeKey($login, $ip);
        return $this->rateLimiter->tooManyAttempts($key, $maxAttempts);
    }

    public function hitAttempt(string $login, string $ip): void
    {
        if (!SystemSetting::get('auth.login_rate_limit_enabled', true)) {
            return;
        }

        $decayMinutes = (int) SystemSetting::get('auth.login_rate_limit_decay_minutes', 1);
        $key = $this->makeKey($login, $ip);
        $this->rateLimiter->hit($key, $decayMinutes * 60);
    }

    public function clearAttempts(string $login, string $ip): void
    {
        $key = $this->makeKey($login, $ip);
        $this->rateLimiter->clear($key);
    }

    public function recordFailureAndMaybeLock(string $login): void
    {
        if (!SystemSetting::get('auth.lockout_enabled', true)) {
            return;
        }

        $threshold = (int) SystemSetting::get('auth.lockout_failed_attempts_threshold', 5);
        $windowMinutes = (int) SystemSetting::get('auth.lockout_window_minutes', 60);
        $durationMinutes = (int) SystemSetting::get('auth.lockout_duration_minutes', 15);

        $failKey = $this->failuresKey($login);
        $current = (int) (Cache::get($failKey) ?? 0);
        $current++;
        Cache::put($failKey, $current, now()->addMinutes($windowMinutes));

        if ($current >= $threshold) {
            $unlockAt = now()->addMinutes($durationMinutes)->timestamp;
            Cache::put($this->lockKey($login), $unlockAt, now()->addMinutes($durationMinutes));
            Cache::forget($failKey);
        }
    }

    public function getRetryAfterSeconds(string $login, string $ip): int
    {
        $key = $this->makeKey($login, $ip);
        return $this->rateLimiter->availableIn($key);
    }

    /**
     * @return array{max:int,decay:int}
     */
    public function getRateLimitConfig(): array
    {
        return [
            'max' => (int) SystemSetting::get('auth.login_rate_limit_max_attempts', 5),
            'decay' => (int) SystemSetting::get('auth.login_rate_limit_decay_minutes', 1),
        ];
    }

    public function getLockRemainingSeconds(string $login): int
    {
        $unlockAtTs = Cache::get($this->lockKey($login));
        return $unlockAtTs ? max(0, $unlockAtTs - time()) : 0;
    }

    /**
     * @return array{threshold:int,window:int,duration:int}
     */
    public function getLockConfig(): array
    {
        return [
            'threshold' => (int) SystemSetting::get('auth.lockout_failed_attempts_threshold', 5),
            'window' => (int) SystemSetting::get('auth.lockout_window_minutes', 60),
            'duration' => (int) SystemSetting::get('auth.lockout_duration_minutes', 15),
        ];
    }
}


