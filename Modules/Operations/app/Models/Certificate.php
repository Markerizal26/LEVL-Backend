<?php

namespace Modules\Operations\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Common\Traits\PgSearchable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Certificate extends Model implements HasMedia
{
    use InteractsWithMedia, PgSearchable;

    protected array $searchable_columns = [
        'certificate_number',
    ];

    protected $fillable = [
        'user_id', 'course_id', 'certificate_number',
        'issued_at', 'expired_at', 'status',
    ];

    protected $casts = [
        'issued_at' => 'datetime',
        'expired_at' => 'datetime',
        'status' => \Modules\Operations\Enums\CertificateStatus::class,
    ];

    protected $appends = ['file_url'];

    
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('certificate')
            ->singleFile()
            ->useDisk(config('media-library.disk_name', 'public'))
            ->acceptsMimeTypes(['application/pdf', 'image/jpeg', 'image/png']);
    }

    public function getFileUrlAttribute(): ?string
    {
        $media = $this->getFirstMedia('certificate');

        return $media?->getUrl();
    }

    public function user()
    {
        return $this->belongsTo(\Modules\Auth\Models\User::class);
    }

    public function course()
    {
        return $this->belongsTo(\Modules\Schemes\Models\Course::class);
    }
}
