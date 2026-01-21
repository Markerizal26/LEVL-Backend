<?php

declare(strict_types=1);

namespace Modules\Common\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;
use Modules\Common\Enums\CategoryStatus;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Category extends Model
{
    use HasFactory, LogsActivity, Searchable, SoftDeletes;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName) => match ($eventName) {
                'created' => 'Kategori baru telah dibuat',
                'updated' => 'Kategori telah diperbarui',
                'deleted' => 'Kategori telah dihapus',
                default => "Kategori {$eventName}",
            });
    }

    protected $fillable = [
        'name',
        'value',
        'description',
        'status',
    ];

    protected $casts = [
        'status' => CategoryStatus::class,
    ];

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'value' => $this->value,
            'description' => $this->description,
            'status' => $this->status?->value,
        ];
    }

    public function searchableAs(): string
    {
        return 'categories_index';
    }

    public function shouldBeSearchable(): bool
    {
        return $this->status === CategoryStatus::Active;
    }

    protected static function newFactory()
    {
        return \Database\Factories\CategoryFactory::new();
    }
}
