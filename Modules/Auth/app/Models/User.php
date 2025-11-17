<?php

namespace Modules\Auth\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, HasRoles, Notifiable;

    protected $guard_name = 'api';

    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'status',
        'email_verified_at',
        'remember_token',
        'avatar_path',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    protected $appends = ['avatar_url'];

    public function getAvatarUrlAttribute(): ?string
    {
        if (! $this->avatar_path) {
            return null;
        }

        return asset('storage/'.$this->avatar_path);
    }

    public function gamificationStats()
    {
        return $this->hasOne(\Modules\Gamification\Models\UserGamificationStat::class);
    }

    public function badges()
    {
        return $this->hasMany(\Modules\Gamification\Models\UserBadge::class);
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [
            'status' => $this->status,
            'roles' => $this->getRoleNames()->values()->toArray(),
        ];
    }

    public function enrollments()
    {
        return $this->hasMany(\Modules\Enrollments\Models\Enrollment::class);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Database\Factories\UserFactory::new();
    }
}
