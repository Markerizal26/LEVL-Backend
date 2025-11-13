<?php

namespace Modules\Grading\Models;

use Illuminate\Database\Eloquent\Model;

class Grade extends Model
{
    protected $fillable = [
        'source_type', 'source_id', 'user_id', 'graded_by',
        'score', 'max_score', 'feedback', 'status', 'graded_at',
    ];

    protected $casts = [
        'graded_at' => 'datetime',
    ];

    /**
     * Get the source model (polymorphic).
     * Note: For assignments, source_id refers to assignment_id, not submission_id.
     */
    public function source()
    {
        return match ($this->source_type) {
            'assignment' => $this->belongsTo(\Modules\Learning\Models\Assignment::class, 'source_id'),
            'attempt' => $this->belongsTo(\Modules\Assessments\Models\Attempt::class, 'source_id'),
            default => null,
        };
    }

    /**
     * Get the submission for this grade (if source_type is assignment).
     */
    public function submission()
    {
        if ($this->source_type !== 'assignment') {
            return null;
        }

        return \Modules\Learning\Models\Submission::query()
            ->where('assignment_id', $this->source_id)
            ->where('user_id', $this->user_id)
            ->latest('id')
            ->first();
    }

    public function user()
    {
        return $this->belongsTo(\Modules\Auth\Models\User::class);
    }

    public function grader()
    {
        return $this->belongsTo(\Modules\Auth\Models\User::class, 'graded_by');
    }

    public function reviews()
    {
        return $this->hasMany(GradeReview::class);
    }
}
