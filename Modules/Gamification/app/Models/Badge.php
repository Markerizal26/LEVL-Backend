<?php

namespace Modules\Gamification\Models;

use App\Models\Concerns\TracksTrashBin;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Common\Traits\PgSearchable;
use Modules\Gamification\Enums\BadgeRarity;
use Modules\Gamification\Enums\BadgeType;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Badge extends Model implements HasMedia
{
    use InteractsWithMedia;
    use PgSearchable;
    use SoftDeletes;
    use TracksTrashBin;

    protected array $searchable_columns = [
        'code',
        'name',
        'description',
    ];

    protected $table = 'badges';

    protected $fillable = [
        'code',
        'name',
        'description',
        'type',
        'rarity',
        'xp_reward',
        'active',
        'threshold',
        'is_repeatable',
        'max_awards_per_user',
    ];

    protected $casts = [
        'threshold' => 'integer',
        'type' => BadgeType::class,
        'rarity' => BadgeRarity::class,
        'xp_reward' => 'integer',
        'active' => 'boolean',
        'is_repeatable' => 'boolean',
        'max_awards_per_user' => 'integer',
    ];

    protected $appends = ['icon_url', 'icon_thumb_url'];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('icon')
            ->singleFile()
            ->useDisk(config('media-library.disk_name', 'public'))
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/svg+xml', 'image/webp']);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(64)
            ->height(64)
            ->sharpen(10)
            ->performOnCollections('icon');

        $this->addMediaConversion('large')->width(128)->height(128)->performOnCollections('icon');
    }

    public function getIconUrlAttribute(): ?string
    {
        $media = $this->getFirstMedia('icon');

        return $media?->getUrl();
    }

    public function getIconThumbUrlAttribute(): ?string
    {
        $media = $this->getFirstMedia('icon');

        if (! $media) {
            return null;
        }

        if (str_contains($media->mime_type, 'svg')) {
            return $media->getUrl(); 
        }

        return $media->hasGeneratedConversion('thumb') ? $media->getUrl('thumb') : $media->getUrl();
    }

    public function users(): HasMany
    {
        return $this->hasMany(UserBadge::class);
    }

    public function rules(): HasMany
    {
        return $this->hasMany(BadgeRule::class);
    }

    public function scopeMilestone($query)
    {
        return $query->where('type', 'milestone');
    }

    public function scopeCompletion($query)
    {
        return $query->where('type', 'completion');
    }
}
