<?php

declare(strict_types=1);

namespace Modules\Learning\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class SubmissionFile extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'submission_id',
    ];

    protected $appends = ['file_url', 'file_name', 'file_size'];

    /**
     * Register media collections for this model.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('file')
            ->singleFile()
            ->useDisk('do')
            ->acceptsMimeTypes([
                // Images
                'image/jpeg', 'image/png', 'image/gif', 'image/webp',
                // Documents
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/vnd.ms-powerpoint',
                'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                // Text
                'text/plain',
                // Archives
                'application/zip', 'application/x-rar-compressed',
            ]);
    }

    public function getFileUrlAttribute(): ?string
    {
        $media = $this->getFirstMedia('file');

        return $media?->getUrl();
    }

    public function getFileNameAttribute(): ?string
    {
        $media = $this->getFirstMedia('file');

        return $media?->file_name;
    }

    public function getFileSizeAttribute(): ?int
    {
        $media = $this->getFirstMedia('file');

        return $media?->size;
    }

    public function submission()
    {
        return $this->belongsTo(Submission::class);
    }
}
