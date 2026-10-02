<?php

declare(strict_types=1);

namespace Modules\Schemes\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Schemes\Enums\BlockType;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class LessonBlock extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = ['lesson_id', 'slug', 'block_type', 'content', 'order', 'external_url'];

    protected $casts = [
        'order' => 'integer',
        'block_type' => BlockType::class,
    ];

    protected $appends = ['media_url', 'media_thumb_url', 'embed_url'];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('media')
            ->singleFile()
            ->useDisk(config('media-library.disk_name', 'public'))
            ->acceptsMimeTypes([

                'image/jpeg',
                'image/jpg',
                'image/png',
                'image/gif',
                'image/webp',
                'image/svg+xml',
                'image/bmp',

                'video/mp4',
                'video/webm',
                'video/ogg',
                'video/quicktime',
                'video/x-msvideo',
                'video/x-matroska',

                'audio/mpeg',
                'audio/wav',
                'audio/ogg',
                'audio/mp3',
                'audio/mp4',
                'audio/aac',

                'application/pdf',
                'text/plain',
                'text/csv',
                'text/html',
                'application/rtf',
                'text/rtf',

                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/vnd.ms-powerpoint',
                'application/vnd.openxmlformats-officedocument.presentationml.presentation',

                'application/zip',
                'application/x-zip-compressed',
                'application/x-rar-compressed',
                'application/x-rar',
                'application/x-7z-compressed',
                'application/x-tar',
                'application/gzip',
                'application/x-gzip',

                'application/json',
                'application/xml',
                'text/xml',
                'application/octet-stream',
            ]);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        if ($media && ! str_starts_with($media->mime_type, 'image/')) {
            return;
        }

        $this->addMediaConversion('thumb')
            ->width(320)
            ->height(180)
            ->sharpen(10)
            ->performOnCollections('media');

        $this->addMediaConversion('mobile')->width(160)->height(90)->performOnCollections('media');

        $this->addMediaConversion('preview')->width(640)->height(360)->performOnCollections('media');
    }

    public function getMediaUrlAttribute(): ?string
    {
        $media = $this->getFirstMedia('media');

        return $media?->getUrl();
    }

    public function getMediaThumbUrlAttribute(): ?string
    {
        $media = $this->getFirstMedia('media');

        return $media?->getUrl('thumb');
    }

    public function getMediaMetaAttribute(): ?array
    {
        $media = $this->getFirstMedia('media');
        if (! $media) {
            return null;
        }

        return [
            'name' => $media->file_name,
            'size' => $media->size,
            'mime_type' => $media->mime_type,
            'width' => $media->getCustomProperty('width'),
            'height' => $media->getCustomProperty('height'),
            'duration' => $media->getCustomProperty('duration'),
        ];
    }

    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function isExternalLink(): bool
    {
        return $this->block_type->isExternalLink();
    }

    public function getEmbedUrlAttribute(): ?string
    {
        if (! $this->external_url) {
            return null;
        }

        if ($this->block_type === BlockType::YouTube) {
            return $this->convertYouTubeToEmbed($this->external_url);
        }

        if ($this->block_type === BlockType::Drive) {
            return $this->convertDriveToPreview($this->external_url);
        }

        return $this->external_url;
    }

    private function convertYouTubeToEmbed(string $url): string
    {

        if (preg_match('/[?&]v=([^&]+)/', $url, $matches)) {
            return "https://www.youtube.com/embed/{$matches[1]}";
        }

        if (preg_match('/youtu\.be\/([^?]+)/', $url, $matches)) {
            return "https://www.youtube.com/embed/{$matches[1]}";
        }

        return $url;
    }

    private function convertDriveToPreview(string $url): string
    {

        if (preg_match('/\/d\/([^\/]+)/', $url, $matches)) {
            return "https://drive.google.com/file/d/{$matches[1]}/preview";
        }

        if (preg_match('/id=([^&]+)/', $url, $matches)) {
            return "https://drive.google.com/file/d/{$matches[1]}/preview";
        }

        return $url;
    }
}
