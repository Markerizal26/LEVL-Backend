<?php

declare(strict_types=1);

namespace Modules\Common\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Laravel\Scout\Searchable;

class MasterDataItem extends Model
{
    use Searchable;

    protected $table = 'master_data';

    protected $fillable = [
        'type',
        'value',
        'label',
        'metadata',
        'is_system',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_system' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    private const CACHE_PREFIX = 'master_data:';

    private const CACHE_TTL = 3600;

    public static function getByType(string $type): \Illuminate\Database\Eloquent\Collection
    {
        return Cache::remember(
            self::CACHE_PREFIX.$type,
            self::CACHE_TTL,
            fn () => self::where('type', $type)
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get()
        );
    }

    public static function getByTypeAndValue(string $type, string $value): ?self
    {
        $items = self::getByType($type);

        return $items->firstWhere('value', $value);
    }

    public static function clearCache(string $type): void
    {
        Cache::forget(self::CACHE_PREFIX.$type);
    }

    public static function clearAllCache(): void
    {
        self::distinct('type')->pluck('type')->each(function ($type) {
            self::clearCache($type);
        });
    }

    protected static function booted(): void
    {
        static::saved(function (self $item) {
            self::clearCache($item->type);
        });

        static::deleted(function (self $item) {
            self::clearCache($item->type);
        });
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'value' => $this->value,
            'label' => $this->label,
            'is_active' => $this->is_active,
        ];
    }

    public function searchableAs(): string
    {
        return 'master_data_index';
    }

    public function shouldBeSearchable(): bool
    {
        return $this->is_active;
    }
}
