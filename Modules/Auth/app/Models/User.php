<?php

namespace Modules\Auth\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Modules\Auth\Enums\UserStatus;
use Modules\Auth\Traits\HasProfilePrivacy;
use Modules\Auth\Traits\TracksUserActivity;
use Spatie\Permission\Traits\HasRoles;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, HasProfilePrivacy, HasRoles, Notifiable, TracksUserActivity;

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
        'bio',
        'phone',
        'account_status',
        'last_profile_update',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'last_profile_update' => 'datetime',
        'status' => UserStatus::class,
    ];

    protected $appends = ['avatar_url'];

    public function getAvatarUrlAttribute(): ?string
    {
        if (! $this->avatar_path) {
            return null;
        }

        $uploader = app(\App\Services\UploadService::class);

        return $uploader->getPublicUrl($this->avatar_path);
    }

    public function gamificationStats()
    {
        return $this->hasOne(\Modules\Gamification\Models\UserGamificationStat::class);
    }

    public function badges()
    {
        return $this->hasMany(\Modules\Gamification\Models\UserBadge::class);
    }

    public function privacySettings()
    {
        return $this->hasOne(ProfilePrivacySetting::class);
    }

    public function activities()
    {
        return $this->hasMany(UserActivity::class);
    }

    public function pinnedBadges()
    {
        return $this->hasMany(PinnedBadge::class);
    }

    public function auditLogs()
    {
        return $this->hasMany(ProfileAuditLog::class, 'user_id');
    }

    public function scopeActive($query)
    {
        return $query->where('account_status', 'active');
    }

    public function scopeSuspended($query)
    {
        return $query->where('account_status', 'suspended');
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

    public function attempts()
    {
        return $this->hasMany(\Modules\Assessments\Models\Attempt::class);
    }

    /**
     * Get courses managed by this user (courses where user is an admin)
     */
    public function managedCourses()
    {
        return $this->belongsToMany(
            \Modules\Schemes\Models\Course::class,
            'course_admins',
            'user_id',
            'course_id'
        );
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Database\Factories\UserFactory::new();
    }
}
