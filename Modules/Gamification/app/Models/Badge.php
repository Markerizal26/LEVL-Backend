<?php

namespace Modules\Gamification\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Gamification\Enums\BadgeType;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Badge extends Model implements HasMedia
{
  use InteractsWithMedia;

  protected $table = "badges";

  protected $fillable = ["code", "name", "description", "type", "threshold"];

  protected $casts = [
    "threshold" => "integer",
    "type" => BadgeType::class,
  ];

  protected $appends = ["icon_url"];

  /**
   * Register media collections for this model.
   */
  public function registerMediaCollections(): void
  {
    $this->addMediaCollection("icon")
      ->singleFile()
      ->useDisk("do")
      ->acceptsMimeTypes(["image/jpeg", "image/png", "image/svg+xml", "image/webp"]);
  }

  /**
   * Register media conversions for this model.
   */
  public function registerMediaConversions(?Media $media = null): void
  {
    $this->addMediaConversion("thumb")
      ->width(64)
      ->height(64)
      ->sharpen(10)
      ->performOnCollections("icon");

    // Large icon for display
    $this->addMediaConversion("large")->width(128)->height(128)->performOnCollections("icon");
  }

  public function getIconUrlAttribute(): ?string
  {
    $media = $this->getFirstMedia("icon");

    return $media?->getUrl();
  }

  public function getIconThumbUrlAttribute(): ?string
  {
    $media = $this->getFirstMedia("icon");

    return $media?->getUrl("thumb");
  }

  public function users(): HasMany
  {
    return $this->hasMany(UserBadge::class);
  }

  public function scopeAchievement($query)
  {
    return $query->where("type", "achievement");
  }

  public function scopeMilestone($query)
  {
    return $query->where("type", "milestone");
  }

  public function scopeCompletion($query)
  {
    return $query->where("type", "completion");
  }
}
