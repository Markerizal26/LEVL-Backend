<?php

declare(strict_types=1);

namespace Modules\Auth\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\User\Models\User;

class OtpCode extends Model
{
    use HasFactory;

    protected $table = 'otp_codes';

    protected $fillable = [
        'uuid',
        'user_id',
        'channel',
        'provider',
        'purpose',
        'code',
        'meta',
        'expires_at',
        'consumed_at',
    ];

    protected $hidden = [
        'code',
    ];

    protected $casts = [
        'user_id' => 'int',
        'expires_at' => 'datetime',
        'consumed_at' => 'datetime',
        'meta' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at instanceof Carbon
            && $this->expires_at->isPast();
    }

    public function isConsumed(): bool
    {
        return $this->consumed_at !== null;
    }

    public function isValid(): bool
    {
        return ! $this->isConsumed() && ! $this->isExpired();
    }

    public function verify(string $code): bool
    {
        if (! $this->isValid()) {
            return false;
        }

        if (hash_equals((string) $this->code, $code)) {
            $this->markAsConsumed();

            return true;
        }

        return false;
    }

    public function markAsConsumed(): void
    {
        $this->update([
            'consumed_at' => now(),
        ]);
    }

    public function scopeValid(Builder $query): Builder
    {
        return $query
            ->whereNull('consumed_at')
            ->where('expires_at', '>', now());
    }

    public function scopeConsumed(Builder $query): Builder
    {
        return $query->whereNotNull('consumed_at');
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('expires_at', '<=', now());
    }

    public function scopeForPurpose(Builder $query, string $purpose): Builder
    {
        return $query->where('purpose', $purpose);
    }

    public function scopeForUser(Builder $query, int|User $user): Builder
    {
        $userId = $user instanceof User ? $user->id : $user;

        return $query->where('user_id', $userId);
    }
}
