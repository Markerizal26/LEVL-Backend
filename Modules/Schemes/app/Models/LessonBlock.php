<?php

namespace Modules\Schemes\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class LessonBlock extends Model implements HasMedia
{
  use InteractsWithMedia;

  protected $fillable = ["lesson_id", "slug", "block_type", "content", "order"];

  protected $casts = [
    "order" => "integer",
  ];

  protected $appends = ["media_url", "media_thumb_url"];

  /**
   * Register media collections for this model.
   */
  public function registerMediaCollections(): void
  {
    $this->addMediaCollection("media")
      ->singleFile()
      ->useDisk("do")
      ->acceptsMimeTypes([
        // Images
        "image/jpeg",
        "image/png",
        "image/gif",
        "image/webp",
        // Videos
        "video/mp4",
        "video/webm",
        "video/ogg",
        "video/quicktime",
        // Audio
        "audio/mpeg",
        "audio/wav",
        "audio/ogg",
        "audio/mp3",
        // Documents (for downloadable resources)
        "application/pdf",
      ]);
  }

  /**
   * Register media conversions for this model.
   */
  public function registerMediaConversions(?Media $media = null): void
  {
    $this->addMediaConversion("thumb")
      ->width(320)
      ->height(180) // 16:9 ratio for video thumbnails
      ->sharpen(10)
      ->performOnCollections("media");

    // Mobile-optimized thumbnail
    $this->addMediaConversion("mobile")->width(160)->height(90)->performOnCollections("media");

    // Large preview
    $this->addMediaConversion("preview")->width(640)->height(360)->performOnCollections("media");
  }

  public function getMediaUrlAttribute(): ?string
  {
    $media = $this->getFirstMedia("media");

    return $media?->getUrl();
  }

  public function getMediaThumbUrlAttribute(): ?string
  {
    $media = $this->getFirstMedia("media");

    return $media?->getUrl("thumb");
  }

  public function getMediaMetaAttribute(): ?array
  {
    $media = $this->getFirstMedia("media");
    if (!$media) {
      return null;
    }

    return [
      "name" => $media->file_name,
      "size" => $media->size,
      "mime_type" => $media->mime_type,
      "width" => $media->getCustomProperty("width"),
      "height" => $media->getCustomProperty("height"),
      "duration" => $media->getCustomProperty("duration"),
    ];
  }

  public function lesson()
  {
    return $this->belongsTo(Lesson::class);
  }

  public function getRouteKeyName(): string
  {
    return "slug";
  }
}
