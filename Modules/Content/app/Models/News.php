<?php

namespace Modules\Content\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Auth\Models\User;
use Modules\Content\Enums\ContentStatus;
use Modules\Content\Traits\HasContentRevisions;
use Modules\Schemes\Models\Tag;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class News extends Model implements HasMedia
{
  use HasContentRevisions, HasFactory, HasSlug, InteractsWithMedia, LogsActivity, SoftDeletes;

  protected $table = "news";

  protected static function newFactory()
  {
    return \Modules\Content\Database\Factories\NewsFactory::new();
  }

  /**
   * Register media collections for this model.
   */
  public function registerMediaCollections(): void
  {
    $this->addMediaCollection("featured_image")
      ->singleFile()
      ->useDisk("do")
      ->acceptsMimeTypes(["image/jpeg", "image/png", "image/webp"]);

    $this->addMediaCollection("gallery")
      ->useDisk("do")
      ->acceptsMimeTypes(["image/jpeg", "image/png", "image/gif", "image/webp"]);
  }

  /**
   * Register media conversions for this model.
   */
  public function registerMediaConversions(?Media $media = null): void
  {
    $this->addMediaConversion("thumb")->width(400)->height(300)->sharpen(10);

    $this->addMediaConversion("og_image")
      ->width(1200)
      ->height(630) // Open Graph ratio for social sharing
      ->performOnCollections("featured_image");

    // Mobile-optimized sizes
    $this->addMediaConversion("mobile")
      ->width(320)
      ->height(240)
      ->performOnCollections("featured_image", "gallery");

    $this->addMediaConversion("medium")
      ->width(800)
      ->height(600)
      ->performOnCollections("featured_image", "gallery");
  }

  /**
   * Get the options for generating the slug.
   */
  public function getSlugOptions(): SlugOptions
  {
    return SlugOptions::create()
      ->generateSlugsFrom("title")
      ->saveSlugsTo("slug")
      ->doNotGenerateSlugsOnUpdate();
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
          "created" => "Berita baru telah dibuat",
          "updated" => "Berita telah diperbarui",
          "deleted" => "Berita telah dihapus",
          default => "Berita {$eventName}",
        },
      );
  }

  protected $fillable = [
    "author_id",
    "title",
    "slug",
    "excerpt",
    "content",
    "status",
    "is_featured",
    "published_at",
    "scheduled_at",
    "views_count",
    "deleted_by",
  ];

  protected $appends = ["featured_image_url"];

  public function getFeaturedImageUrlAttribute(): ?string
  {
    $media = $this->getFirstMedia("featured_image");

    return $media?->getUrl();
  }

  public function getFeaturedImageThumbUrlAttribute(): ?string
  {
    $media = $this->getFirstMedia("featured_image");

    return $media?->getUrl("thumb");
  }

  public function getOgImageUrlAttribute(): ?string
  {
    $media = $this->getFirstMedia("featured_image");

    return $media?->getUrl("og_image");
  }

  protected $casts = [
    "status" => ContentStatus::class,
    "is_featured" => "boolean",
    "published_at" => "datetime",
    "scheduled_at" => "datetime",
    "views_count" => "integer",
    "created_at" => "datetime",
    "updated_at" => "datetime",
    "deleted_at" => "datetime",
  ];

  public function author(): BelongsTo
  {
    return $this->belongsTo(User::class, "author_id");
  }

  public function deletedBy(): BelongsTo
  {
    return $this->belongsTo(User::class, "deleted_by");
  }

  public function categories(): BelongsToMany
  {
    return $this->belongsToMany(ContentCategory::class, "news_category", "news_id", "category_id");
  }

  public function tags(): BelongsToMany
  {
    return $this->belongsToMany(Tag::class, "taggables", "taggable_id", "tag_id")->wherePivot(
      "taggable_type",
      self::class,
    );
  }

  public function reads(): MorphMany
  {
    return $this->morphMany(ContentRead::class, "readable");
  }

  public function revisions(): HasMany
  {
    return $this->hasMany(ContentRevision::class, "content_id")->where("content_type", self::class);
  }

  public function scopePublished($query)
  {
    return $query
      ->where("status", ContentStatus::Published)
      ->whereNotNull("published_at")
      ->where("published_at", "<=", now());
  }

  public function scopeFeatured($query)
  {
    return $query->where("is_featured", true);
  }

  public function incrementViews(): void
  {
    $this->increment("views_count");
  }

  public function getTrendingScore(): float
  {
    if (!$this->published_at) {
      return 0.0;
    }

    $hoursOld = $this->published_at->diffInHours(now());

    if ($hoursOld === 0) {
      $hoursOld = 1;
    }

    return round($this->views_count / $hoursOld, 1);
  }

  public function isPublished(): bool
  {
    return $this->status === ContentStatus::Published &&
      $this->published_at !== null &&
      $this->published_at->isPast();
  }

  public function isScheduled(): bool
  {
    return $this->status === ContentStatus::Scheduled &&
      $this->scheduled_at !== null &&
      $this->scheduled_at->isFuture();
  }
}
