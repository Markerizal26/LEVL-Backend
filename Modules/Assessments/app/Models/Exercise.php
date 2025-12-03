<?php

namespace Modules\Assessments\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Assessments\Enums\ExerciseStatus;
use Modules\Assessments\Enums\ExerciseType;
use Modules\Assessments\Enums\ScopeType;

class Exercise extends Model
{
    use HasFactory;

    protected $fillable = [
        'scope_type',
        'scope_id',
        'created_by',
        'title',
        'description',
        'type',
        'time_limit_minutes',
        'max_score',
        'total_questions',
        'max_capacity',
        'status',
        'allow_retake',
        'available_from',
        'available_until',
    ];

    protected $casts = [
        'type' => ExerciseType::class,
        'status' => ExerciseStatus::class,
        'scope_type' => ScopeType::class,
        'available_from' => 'datetime',
        'available_until' => 'datetime',
        'allow_retake' => 'boolean',
    ];

    /**
     * Calculate total points from questions
     */
    public function getTotalPointsAttribute(): int
    {
        return $this->questions()->sum('score_weight');
    }

    // Dynamic polymorphic relation: bisa ke Course/Unit/Lesson
    public function scope()
    {
        return match ($this->scope_type) {
            ScopeType::Course => $this->belongsTo(\Modules\Schemes\Models\Course::class, 'scope_id'),
            ScopeType::Unit => $this->belongsTo(\Modules\Schemes\Models\Unit::class, 'scope_id'),
            ScopeType::Lesson => $this->belongsTo(\Modules\Schemes\Models\Lesson::class, 'scope_id'),
            default => null,
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

    protected static function newFactory()
    {
        return \Modules\Assessments\Database\Factories\ExerciseFactory::new();
    }
}
