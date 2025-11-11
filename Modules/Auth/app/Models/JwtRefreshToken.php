<?php

namespace Modules\Auth\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JwtRefreshToken extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'jwt_refresh_tokens';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'device_id',
        'token',
        'replaced_by',
        'ip',
        'user_agent',
        'revoked_at',
        'last_used_at',
        'expires_at',
        'idle_expires_at',
        'absolute_expires_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'token',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'user_id' => 'integer',
        'replaced_by' => 'integer',
        'revoked_at' => 'datetime',
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
        'idle_expires_at' => 'datetime',
        'absolute_expires_at' => 'datetime',
    ];

    /**
     * Get the user that owns the refresh token.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\Modules\Auth\Models\User::class);
    }

    /**
     * Get the token that replaced this one.
     */
    public function replacedBy(): BelongsTo
    {
        return $this->belongsTo(JwtRefreshToken::class, 'replaced_by');
    }

    /**
     * Get the token that this one replaced.
     */
    public function replacedToken(): BelongsTo
    {
        return $this->belongsTo(JwtRefreshToken::class, 'replaced_by', 'id');
    }

    /**
     * Check if the token is revoked.
     */
    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    /**
     * Check if the token is expired (idle or absolute).
     */
    public function isExpired(): bool
    {
        if ($this->absolute_expires_at && $this->absolute_expires_at->isPast()) {
            return true;
        }
        
        if ($this->idle_expires_at && $this->idle_expires_at->isPast()) {
            return true;
        }
        
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if the token is replaced.
     */
    public function isReplaced(): bool
    {
        return $this->replaced_by !== null;
    }

    /**
     * Check if the token is valid (not revoked, not expired, and not replaced).
     */
    public function isValid(): bool
    {
        return !$this->isRevoked() && !$this->isExpired() && !$this->isReplaced();
    }

    /**
     * Revoke the token.
     */
    public function revoke(): void
    {
        $this->update(['revoked_at' => now()]);
    }

    /**
     * Scope a query to only include valid tokens.
     */
    public function scopeValid($query)
    {
        return $query->whereNull('revoked_at')
            ->whereNull('replaced_by')
            ->where(function ($q) {
                $q->whereNull('absolute_expires_at')
                    ->orWhere('absolute_expires_at', '>', now());
            })
            ->where(function ($q) {
                $q->whereNull('idle_expires_at')
                    ->orWhere('idle_expires_at', '>', now());
            })
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * Scope a query to only include revoked tokens.
     */
    public function scopeRevoked($query)
    {
        return $query->whereNotNull('revoked_at');
    }

    /**
     * Scope a query to only include expired tokens.
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
    }
}

