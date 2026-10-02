<?php

declare(strict_types=1);

namespace Modules\Auth\Models;

use App\Models\Concerns\TracksTrashBin;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Modules\Auth\Enums\UserStatus;
use Modules\Auth\Traits\TracksUserActivity;
use Modules\Common\Traits\PgSearchable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Permission\Traits\HasRoles;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements HasMedia, JWTSubject
{
    use HasFactory,
        HasRoles,
        InteractsWithMedia,
        LogsActivity,
        Notifiable,
        PgSearchable,
        SoftDeletes,
        TracksTrashBin,
        TracksUserActivity;

    protected array $searchable_columns = [
        'name',
        'username',
        'email',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')
            ->singleFile()
            ->useDisk(config('media-library.disk_name', 'public'))
            ->acceptsMimeTypes([
                'image/jpeg',
                'image/png',
                'image/webp',
                'image/svg+xml',
            ]);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(150)
            ->height(150)
            ->sharpen(10)
            ->performOnCollections('avatar');

        $this->addMediaConversion('small')->width(64)->height(64)->performOnCollections('avatar');

        $this->addMediaConversion('medium')->width(256)->height(256)->performOnCollections('avatar');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(
                fn (string $eventName) => match ($eventName) {
                    'created' => 'User baru telah dibuat',
                    'updated' => 'User telah diperbarui',
                    'deleted' => 'User telah dihapus',
                    default => "User {$eventName}",
                },
            );
    }

    protected $guard_name = 'api';

    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'status',
        'email_verified_at',
        'remember_token',
        'bio',
        'specialization_id',
        'phone',
        'location',
        'last_profile_update',
        'is_password_set',
        'fcm_token',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'last_profile_update' => 'datetime',
        'status' => UserStatus::class,
        'is_password_set' => 'boolean',
    ];

    public function getAvatarUrlAttribute(): ?string
    {
        $media = $this->getFirstMedia('avatar');

        return $media?->getUrl();
    }

    public function getAvatarThumbUrlAttribute(): ?string
    {
        $media = $this->getFirstMedia('avatar');

        return $media?->getUrl('thumb');
    }

    public function gamificationStats()
    {
        return $this->hasOne(\Modules\Gamification\Models\UserGamificationStat::class);
    }

    public function badges()
    {
        return $this->hasMany(\Modules\Gamification\Models\UserBadge::class);
    }

    public function points()
    {
        return $this->hasMany(\Modules\Gamification\Models\Point::class);
    }

    public function learningStreaks()
    {
        return $this->hasMany(\Modules\Gamification\Models\LearningStreak::class);
    }

    
    public function levels()
    {
        return $this->hasOne(\Modules\Gamification\Models\UserGamificationStat::class);
    }

    public function submissions()
    {
        return $this->hasMany(\Modules\Learning\Models\Submission::class);
    }

    public function assignments()
    {
        return $this->hasMany(\Modules\Learning\Models\Assignment::class, 'created_by');
    }

    public function threads()
    {
        return $this->hasMany(\Modules\Forums\Models\Thread::class, 'author_id');
    }

    public function receivedOverrides()
    {
        return $this->hasMany(\Modules\Learning\Models\Override::class, 'student_id');
    }

    public function grantedOverrides()
    {
        return $this->hasMany(\Modules\Learning\Models\Override::class, 'grantor_id');
    }

    public function scopeActive($query, bool $isActive = true)
    {
        if ($isActive) {
            return $query->where('status', UserStatus::Active);
        }

        return $query->where('status', '!=', UserStatus::Active);
    }

    public function scopeSuspended($query)
    {
        return $query->where('status', UserStatus::Banned);
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

    public function managedCourses()
    {
        return $this->belongsToMany(
            \Modules\Schemes\Models\Course::class,
            'course_admins',
            'user_id',
            'course_id',
        );
    }

    public function specialization()
    {
        return $this->belongsTo(\Modules\Common\Models\Category::class, 'specialization_id');
    }

    protected static function newFactory()
    {
        return \Database\Factories\UserFactory::new();
    }
}
