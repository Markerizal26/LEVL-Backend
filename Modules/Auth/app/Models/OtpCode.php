<?php

namespace Modules\Auth\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OtpCode extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'otp_codes';

    /**
     * The attributes that are mass assignable.
     */
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

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'code',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'user_id' => 'integer',
        'expires_at' => 'datetime',
        'consumed_at' => 'datetime',
        'meta' => 'array',
    ];

    /**
     * Get the user that owns the OTP code.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if the OTP code is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if the OTP code is consumed.
     */
    public function isConsumed(): bool
    {
        return $this->consumed_at !== null;
    }

    /**
     * Check if the OTP code is valid (not consumed and not expired).
     */
    public function isValid(): bool
    {
        return !$this->isConsumed() && !$this->isExpired();
    }

    /**
     * Verify the OTP code.
     */
    public function verify(string $code): bool
    {
        if (!$this->isValid()) {
            return false;
        }

        if (hash_equals($this->code, $code)) {
            $this->markAsConsumed();
            return true;
        }

        return false;
    }

    /**
     * Mark the OTP code as consumed.
     */
    public function markAsConsumed(): void
    {
        $this->update(['consumed_at' => now()]);
    }

    /**
     * Scope a query to only include valid OTP codes.
     */
    public function scopeValid($query)
    {
        return $query->whereNull('consumed_at')
            ->where('expires_at', '>', now());
    }

    /**
     * Scope a query to only include consumed OTP codes.
     */
    public function scopeConsumed($query)
    {
        return $query->whereNotNull('consumed_at');
    }

    /**
     * Scope a query to only include expired OTP codes.
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
    }

    /**
     * Scope a query to only include OTP codes for a specific purpose.
     */
    public function scopeForPurpose($query, string $purpose)
    {
        return $query->where('purpose', $purpose);
    }

    /**
     * Scope a query to only include OTP codes for a specific user.
     */
    public function scopeForUser($query, $user)
    {
        $userId = is_object($user) ? $user->id : $user;
        return $query->where('user_id', $userId);
    }
}

