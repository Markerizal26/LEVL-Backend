<?php

namespace Modules\Grading\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Grading\Enums\GradeStatus;
use Modules\Grading\Enums\SourceType;

class Grade extends Model
{
    protected $fillable = [
        'source_type', 'source_id', 'user_id', 'graded_by',
        'score', 'max_score', 'feedback', 'status', 'graded_at',
    ];

    protected $casts = [
        'source_type' => SourceType::class,
        'status' => GradeStatus::class,
        'graded_at' => 'datetime',
    ];

    /**
     * Get the source model (polymorphic).
     * Note: For assignments, source_id refers to assignment_id, not submission_id.
     */
    public function source()
    {
        return match ($this->source_type) {
            SourceType::Assignment => $this->belongsTo(\Modules\Learning\Models\Assignment::class, 'source_id'),
            SourceType::Attempt => $this->belongsTo(\Modules\Assessments\Models\Attempt::class, 'source_id'),
            default => null,
        };
    }

    /**
     * Get the submission for this grade (if source_type is assignment).
     */
    public function submission()
    {
        if ($this->source_type !== SourceType::Assignment) {
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
