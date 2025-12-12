<?php

namespace Modules\Auth\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Scout\Searchable;
use Modules\Auth\Enums\UserStatus;
use Modules\Auth\Traits\HasProfilePrivacy;
use Modules\Auth\Traits\TracksUserActivity;
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
    HasProfilePrivacy,
    HasRoles,
    InteractsWithMedia,
    LogsActivity,
    Notifiable,
    Searchable,
    TracksUserActivity;

  /**
   * Register media collections for this model.
   */
  public function registerMediaCollections(): void
  {
    $this->addMediaCollection("avatar")
      ->singleFile()
      ->useDisk("do")
      ->acceptsMimeTypes(["image/jpeg", "image/png", "image/gif", "image/webp"]);
  }

  /**
   * Register media conversions for this model.
   */
  public function registerMediaConversions(?Media $media = null): void
  {
    $this->addMediaConversion("thumb")
      ->width(150)
      ->height(150)
      ->sharpen(10)
      ->performOnCollections("avatar");

    // Responsive images for different screen sizes
    $this->addMediaConversion("small")->width(64)->height(64)->performOnCollections("avatar");

    $this->addMediaConversion("medium")->width(256)->height(256)->performOnCollections("avatar");
  }

  /**
   * Get activity log options for this model.
   */
  public function getActivitylogOptions(): LogOptions
  {
    return LogOptions::defaults()
      ->logAll()
      ->logOnlyDirty()
      ->dontSubmitEmptyLogs()
      ->setDescriptionForEvent(
        fn(string $eventName) => match ($eventName) {
          "created" => "User baru telah dibuat",
          "updated" => "User telah diperbarui",
          "deleted" => "User telah dihapus",
          default => "User {$eventName}",
        },
      );
  }

  protected $guard_name = "api";

  protected $fillable = [
    "name",
    "username",
    "email",
    "password",
    "status",
    "email_verified_at",
    "remember_token",
    "bio",
    "phone",
    "account_status",
    "last_profile_update",
  ];

  protected $hidden = ["password", "remember_token"];

  protected $casts = [
    "email_verified_at" => "datetime",
    "password" => "hashed",
    "last_profile_update" => "datetime",
    "status" => UserStatus::class,
  ];

  protected $appends = ["avatar_url"];

  public function getAvatarUrlAttribute(): ?string
  {
    $media = $this->getFirstMedia("avatar");

    return $media?->getUrl();
  }

  public function getAvatarThumbUrlAttribute(): ?string
  {
    $media = $this->getFirstMedia("avatar");

    return $media?->getUrl("thumb");
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
    return $this->hasMany(ProfileAuditLog::class, "user_id");
  }

  public function scopeActive($query)
  {
    return $query->where("account_status", "active");
  }

  public function scopeSuspended($query)
  {
    return $query->where("account_status", "suspended");
  }

  public function getJWTIdentifier()
  {
    return $this->getKey();
  }

  public function getJWTCustomClaims(): array
  {
    return [
      "status" => $this->status,
      "roles" => $this->getRoleNames()->values()->toArray(),
    ];
  }

  public function enrollments()
  {
    return $this->hasMany(\Modules\Enrollments\Models\Enrollment::class);
  }

  /**
   * Get courses managed by this user (courses where user is an admin)
   */
  public function managedCourses()
  {
    return $this->belongsToMany(
      \Modules\Schemes\Models\Course::class,
      "course_admins",
      "user_id",
      "course_id",
    );
  }

  /**
   * Get the indexable data array for the model.
   */
  public function toSearchableArray(): array
  {
    return [
      "id" => $this->id,
      "name" => $this->name,
      "email" => $this->email,
      "username" => $this->username,
      "status" => $this->status?->value,
      "account_status" => $this->account_status,
    ];
  }

  /**
   * Get the name of the index associated with the model.
   */
  public function searchableAs(): string
  {
    return "users_index";
  }

  /**
   * Determine if the model should be searchable.
   */
  public function shouldBeSearchable(): bool
  {
    return $this->account_status === "active";
  }

  /**
   * Create a new factory instance for the model.
   */
  protected static function newFactory()
  {
    return \Database\Factories\UserFactory::new();
  }
}
