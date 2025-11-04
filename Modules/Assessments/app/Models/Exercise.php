<?php

namespace Modules\Assessments\Models;

use Illuminate\Database\Eloquent\Model;

class Exercise extends Model
{
    protected $fillable = [
        'scope_type', 'scope_id', 'created_by',
        'title', 'description', 'type',
        'time_limit_minutes', 'max_score',
        'total_questions', 'status',
        'available_from', 'available_until',
    ];

    protected $casts = [
        'available_from' => 'datetime',
        'available_until' => 'datetime',
    ];

    // Dynamic polymorphic relation: bisa ke Course/Unit/Lesson
    public function scope()
    {
        return match ($this->scope_type) {
            'course' => $this->belongsTo(\Modules\Schemes\Models\Course::class, 'scope_id'),
            'unit' => $this->belongsTo(\Modules\Schemes\Models\Unit::class, 'scope_id'),
            'lesson' => $this->belongsTo(\Modules\Schemes\Models\Lesson::class, 'scope_id'),
        };
    }

    public function creator()
    {
        return $this->belongsTo(\Modules\Auth\Models\User::class, 'created_by');
    }

    public function questions()
    {
        return $this->hasMany(Question::class);
    }

    public function attempts()
    {
        return $this->hasMany(Attempt::class);
    }
}
