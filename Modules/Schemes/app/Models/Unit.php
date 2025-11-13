<?php

namespace Modules\Schemes\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    use HasFactory;
    protected $fillable = [
        'course_id', 'code', 'slug', 'title', 'description',
        'order', 'status',
    ];

    protected $casts = [
        'order' => 'integer',
    ];

    public function course()
    {
        return $this->belongsTo(\Modules\Schemes\Models\Course::class);
    }

    public function lessons()
    {
        return $this->hasMany(\Modules\Schemes\Models\Lesson::class);
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
        return \Database\Factories\UnitFactory::new();
    }
}
