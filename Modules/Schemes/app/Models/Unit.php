<?php

namespace Modules\Schemes\Models;

use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    protected $fillable = [
        'course_id', 'code', 'slug', 'title', 'description',
        'order', 'estimated_duration', 'status',
    ];

    protected $casts = [
        'order' => 'integer',
        'estimated_duration' => 'integer',
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
}
