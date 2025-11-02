<?php

namespace Modules\Grading\Entities;

use Illuminate\Database\Eloquent\Model;

class Grade extends Model
{
    protected $fillable = [
        'source_type', 'source_id', 'user_id', 'graded_by',
        'score', 'max_score', 'feedback', 'status', 'graded_at'
    ];

    protected $casts = [
        'graded_at' => 'datetime',
    ];

    public function source()
    {
        return match ($this->source_type) {
            'assignment' => $this->belongsTo(\Modules\Learning\Entities\Submission::class, 'source_id'),
            'attempt' => $this->belongsTo(\Modules\Assessments\Entities\Attempt::class, 'source_id'),
        };
    }

    public function user()
    {
        return $this->belongsTo(\Modules\Auth\Entities\User::class);
    }

    public function grader()
    {
        return $this->belongsTo(\Modules\Auth\Entities\User::class, 'graded_by');
    }

    public function reviews()
    {
        return $this->hasMany(GradeReview::class);
    }
}
