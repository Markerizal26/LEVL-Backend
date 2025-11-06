<?php

namespace Modules\Schemes\Models;

use Illuminate\Database\Eloquent\Model;

class LessonBlock extends Model
{
    protected $fillable = [
        'lesson_id', 'slug', 'block_type', 'content', 'media_url', 'media_thumbnail_url', 'media_meta_json', 'order',
    ];

    protected $casts = [
        'order' => 'integer',
        'media_meta_json' => 'array',
    ];

    protected $appends = ['media_url_full', 'media_thumbnail_url_full'];

    public function getMediaUrlFullAttribute(): ?string
    {
        $path = $this->getRawOriginal('media_url');

        return $path ? asset('storage/'.$path) : null;
    }

    public function getMediaThumbnailUrlFullAttribute(): ?string
    {
        $path = $this->getRawOriginal('media_thumbnail_url');

        return $path ? asset('storage/'.$path) : null;
    }

    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
