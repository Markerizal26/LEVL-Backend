<?php

namespace Modules\Schemes\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Schemes\Enums\ContentType;

class Lesson extends Model
{
    use HasFactory;

    protected $fillable = [
        'unit_id', 'slug', 'title', 'description',
        'markdown_content', 'content_type', 'content_url',
        'order', 'duration_minutes', 'status', 'published_at',
    ];

    protected $casts = [
        'order' => 'integer',
        'duration_minutes' => 'integer',
        'published_at' => 'datetime',
        'content_type' => ContentType::class,
    ];

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function blocks()
    {
        return $this->hasMany(LessonBlock::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Database\Factories\LessonFactory::new();
    }
}
