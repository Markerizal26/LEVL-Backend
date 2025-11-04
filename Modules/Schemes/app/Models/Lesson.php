<?php

namespace Modules\Schemes\Models;

use Illuminate\Database\Eloquent\Model;

class Lesson extends Model
{
    protected $fillable = [
        'unit_id', 'slug', 'title', 'description',
        'markdown_content', 'content_type', 'content_url',
        'order', 'estimated_duration',
    ];

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function blocks()
    {
        return $this->hasMany(LessonBlock::class);
    }
}
