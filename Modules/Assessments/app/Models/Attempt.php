<?php

namespace Modules\Assessments\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Assessments\Enums\AttemptStatus;

class Attempt extends Model
{
    protected $fillable = [
        'exercise_id', 'user_id', 'enrollment_id',
        'score', 'total_questions', 'correct_answers',
        'status', 'started_at', 'finished_at', 'duration_seconds', 'feedback',
    ];

    protected $casts = [
        'status' => AttemptStatus::class,
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function exercise()
    {
        return $this->belongsTo(Exercise::class);
    }

    public function user()
    {
        return $this->belongsTo(\Modules\Auth\Models\User::class);
    }

    public function answers()
    {
        return $this->hasMany(Answer::class);
    }

    public function enrollment()
    {
        return $this->belongsTo(\Modules\Enrollments\Models\Enrollment::class);
    }
}
