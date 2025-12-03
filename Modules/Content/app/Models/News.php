<?php

namespace Modules\Content\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Modules\Auth\Models\User;
use Modules\Content\Enums\ContentStatus;
use Modules\Schemes\Models\Tag;

class News extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'news';

    protected static function newFactory()
    {
        return \Modules\Content\Database\Factories\NewsFactory::new();
    }

    protected $fillable = [
        'author_id',
        'title',
        'slug',
        'excerpt',
        'content',
        'featured_image_path',
        'status',
        'is_featured',
        'published_at',
        'scheduled_at',
        'views_count',
        'deleted_by',
    ];

    protected $casts = [
        'status' => ContentStatus::class,
        'is_featured' => 'boolean',
        'published_at' => 'datetime',
        'scheduled_at' => 'datetime',
        'views_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($news) {
            if (empty($news->slug)) {
                $news->slug = Str::slug($news->title);
            }
        });
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(ContentCategory::class, 'news_category', 'news_id', 'category_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'taggables', 'taggable_id', 'tag_id')
            ->wherePivot('taggable_type', self::class);
    }

    public function reads(): MorphMany
    {
        return $this->morphMany(ContentRead::class, 'readable');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(ContentRevision::class, 'content_id')
            ->where('content_type', self::class);
    }

    public function scopePublished($query)
    {
        return $query->where('status', ContentStatus::Published)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function incrementViews(): void
    {
        $this->increment('views_count');
    }

    public function getTrendingScore(): float
    {
        if (! $this->published_at) {
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
