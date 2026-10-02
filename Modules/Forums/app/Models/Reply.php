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

class Reply extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, PgSearchable, SoftDeletes, TracksTrashBin;

    protected array $searchable_columns = [
        'content',
    ];

    const MAX_DEPTH = 5;

    protected static function newFactory()
    {
        return \Modules\Forums\Database\Factories\ReplyFactory::new();
    }

    protected $fillable = [
        'thread_id',
        'parent_id',
        'author_id',
        'content',
        'depth',
        'is_accepted_answer',
        'edited_at',
        'deleted_by',
    ];

    protected $casts = [
        'depth' => 'integer',
        'is_accepted_answer' => 'boolean',
        'edited_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($reply) {
            if ($reply->parent_id) {
                $parent = static::find($reply->parent_id);
                $reply->depth = $parent ? $parent->depth + 1 : 0;
            } else {
                $reply->depth = 0;
            }
        });
    }

    public function thread(): BelongsTo
    {
        return $this->belongsTo(Thread::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(\Modules\Auth\Models\User::class, 'author_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Reply::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Reply::class, 'parent_id');
    }

    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(\Modules\Auth\Models\User::class, 'deleted_by');
    }

    public function reactions(): MorphMany
    {
        return $this->morphMany(Reaction::class, 'reactable');
    }

    public function mentions(): MorphMany
    {
        return $this->morphMany(Mention::class, 'mentionable');
    }

    public function isAcceptedAnswer(): bool
    {
        return $this->is_accepted_answer;
    }

    public function getDepth(): int
    {
        return $this->depth;
    }

    public function canHaveChildren(): bool
    {
        return $this->depth < self::MAX_DEPTH;
    }

    public function scopeTopLevel($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeAccepted($query)
    {
        return $query->where('is_accepted_answer', true);
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
