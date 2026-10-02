<?php

declare(strict_types=1);

namespace Modules\Forums\Models;

use App\Models\Concerns\TracksTrashBin;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Common\Traits\PgSearchable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Thread extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, PgSearchable, SoftDeletes, TracksTrashBin;

    protected array $searchable_columns = [
        'title',
        'content',
    ];

    protected static function newFactory()
    {
        return \Modules\Forums\Database\Factories\ThreadFactory::new();
    }

    protected $fillable = [
        'course_id',
        'author_id',
        'title',
        'content',
        'is_pinned',
        'is_closed',
        'is_resolved',
        'views_count',
        'replies_count',
        'last_activity_at',
        'edited_at',
        'deleted_by',
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
        'is_closed' => 'boolean',
        'is_resolved' => 'boolean',
        'views_count' => 'integer',
        'replies_count' => 'integer',
        'last_activity_at' => 'datetime',
        'edited_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(\Modules\Schemes\Models\Course::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(\Modules\Auth\Models\User::class, 'author_id');
    }

    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(\Modules\Auth\Models\User::class, 'deleted_by');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(Reply::class);
    }

    public function topLevelReplies(): HasMany
    {
        return $this->hasMany(Reply::class)->whereNull('parent_id');
    }

    public function reactions(): MorphMany
    {
        return $this->morphMany(Reaction::class, 'reactable');
    }

    public function mentions(): MorphMany
    {
        return $this->morphMany(Mention::class, 'mentionable');
    }

    public function scopeByCourse($query, int $courseId)
    {
        return $query->where('course_id', $courseId);
    }

    public function scopeForCourse($query, int $courseId)
    {
        return $this->scopeByCourse($query, $courseId);
    }

    public function scopeForScheme($query, int $schemeId)
    {
        return $this->scopeForCourse($query, $schemeId);
    }

    public function scopePinned($query, bool $isPinned = true)
    {
        return $query->whereRaw('is_pinned = '.($isPinned ? 'true' : 'false'));
    }

    public function scopeResolved($query, bool $isResolved = true)
    {
        return $query->whereRaw('is_resolved = '.($isResolved ? 'true' : 'false'));
    }

    public function scopeClosed($query, bool $isClosed = true)
    {
        return $query->whereRaw('is_closed = '.($isClosed ? 'true' : 'false'));
    }

    public function scopeOpen($query)
    {
        return $query->whereRaw('is_closed = false');
    }

    public function isPinned(): bool
    {
        return $this->is_pinned;
    }

    public function isClosed(): bool
    {
        return $this->is_closed;
    }

    public function isResolved(): bool
    {
        return $this->is_resolved;
    }

    public function setIsPinnedAttribute(mixed $value): void
    {
        $this->attributes['is_pinned'] = filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false';
    }

    public function setIsClosedAttribute(mixed $value): void
    {
        $this->attributes['is_closed'] = filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false';
    }

    public function setIsResolvedAttribute(mixed $value): void
    {
        $this->attributes['is_resolved'] = filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false';
    }

    public function incrementViews(): void
    {
        $this->increment('views_count');
    }

    public function updateLastActivity(): void
    {
        $this->update(['last_activity_at' => now()]);
    }

    public function scopeWithIsMentioned($query)
    {
        if (auth()->check()) {
            return $query->withExists(['mentions as is_mentioned' => function ($q) {
                $q->where('user_id', auth()->id());
            }]);
        }

        return $query;
    }

    public function scopeIsMentioned($query, $state = true)
    {
        if (! auth()->check()) {
            return $query;
        }

        $method = filter_var($state, FILTER_VALIDATE_BOOLEAN) ? 'whereHas' : 'whereDoesntHave';

        return $query->$method('mentions', function ($q) {
            $q->where('user_id', auth()->id());
        });
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('attachments')
            ->useDisk(config('media-library.disk_name', 'public'));
    }

    public function registerMediaConversions(?\Spatie\MediaLibrary\MediaCollections\Models\Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(300)
            ->height(200)
            ->sharpen(10)
            ->performOnCollections('attachments');

        $this->addMediaConversion('preview')
            ->width(800)
            ->height(600)
            ->performOnCollections('attachments');
    }
}
