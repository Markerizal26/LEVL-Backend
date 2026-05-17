<?php

declare(strict_types=1);

namespace Modules\Schemes\Models;

use App\Models\Concerns\TracksTrashBin;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Enrollments\Models\UnitProgress;
use Modules\Common\Traits\PgSearchable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Unit extends Model
{
    use HasFactory, HasSlug, LogsActivity, \Modules\Common\Traits\PublishedOnlyScope, PgSearchable, SoftDeletes, TracksTrashBin;

    protected array $searchable_columns = [
        'title',
        'description',
        'code',
        'slug',
    ];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('title')
            ->saveSlugsTo('slug')
            ->doNotGenerateSlugsOnUpdate();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName) => match ($eventName) {
                'created' => 'Unit baru telah dibuat',
                'updated' => 'Unit telah diperbarui',
                'deleted' => 'Unit telah dihapus',
                default => "Unit {$eventName}",
            });
    }

    protected $fillable = [
        'course_id', 'code', 'slug', 'title', 'description',
        'order', 'status',
    ];

    protected $casts = [
        'order' => 'integer',
        'status' => \Modules\Schemes\Enums\PublishStatus::class,
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(\Modules\Schemes\Models\Course::class);
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(\Modules\Schemes\Models\Lesson::class);
    }

    public function progress(): HasMany
    {
        return $this->hasMany(UnitProgress::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(\Modules\Learning\Models\Assignment::class);
    }

    public function quizzes(): HasMany
    {
        return $this->hasMany(\Modules\Learning\Models\Quiz::class);
    }

    public function contents(): HasMany
    {
        return $this->elements();
    }

    public function elements(): HasMany
    {
        return $this->hasMany(UnitContent::class)->orderBy('order');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected static function newFactory()
    {
        return \Modules\Schemes\Database\Factories\UnitFactory::new();
    }
}
