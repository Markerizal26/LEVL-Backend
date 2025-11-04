<?php

namespace Modules\Assessments\Models;

use Illuminate\Database\Eloquent\Model;

class Attempt extends Model
{
    protected $fillable = [
        'exercise_id', 'user_id', 'enrollment_id',
        'score', 'total_questions', 'correct_answers',
        'status', 'started_at', 'finished_at', 'duration_seconds',
    ];

    protected $casts = [
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
}
