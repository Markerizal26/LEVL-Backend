<?php

namespace Modules\Learning\Models;

use Illuminate\Database\Eloquent\Model;

class Submission extends Model
{
    protected $fillable = [
        'assignment_id', 'user_id', 'enrollment_id',
        'answer_text', 'status',
        'submitted_at', 'attempt_number',
        'is_late', 'is_resubmission', 'previous_submission_id',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'attempt_number' => 'integer',
        'is_late' => 'boolean',
        'is_resubmission' => 'boolean',
    ];

    protected $appends = [
        'score',
        'feedback',
        'graded_at',
    ];

    public function assignment()
    {
        return $this->belongsTo(Assignment::class);
    }

    public function user()
    {
        return $this->belongsTo(\Modules\Auth\Models\User::class);
    }

    public function enrollment()
    {
        return $this->belongsTo(\Modules\Enrollments\Models\Enrollment::class);
    }

    public function files()
    {
        return $this->hasMany(SubmissionFile::class);
    }

    public function previousSubmission()
    {
        return $this->belongsTo(Submission::class, 'previous_submission_id');
    }

    public function resubmissions()
    {
        return $this->hasMany(Submission::class, 'previous_submission_id');
    }

    /**
     * Get the grade for this submission.
     * Note: Grade is linked via assignment_id and user_id, not submission_id.
     */
    public function grade()
    {
        return $this->hasOne(\Modules\Grading\Models\Grade::class, 'source_id', 'assignment_id')
            ->where('source_type', 'assignment')
            ->where('user_id', $this->user_id);
    }

    /**
     * Get score from grade relationship.
     */
    public function getScoreAttribute()
    {
        if ($this->relationLoaded('grade')) {
            return $this->getRelation('grade')?->score;
        }
        
        $grade = \Modules\Grading\Models\Grade::query()
            ->where('source_type', 'assignment')
            ->where('source_id', $this->assignment_id)
            ->where('user_id', $this->user_id)
            ->first();
        
        return $grade?->score;
    }

    /**
     * Get feedback from grade relationship.
     */
    public function getFeedbackAttribute()
    {
        if ($this->relationLoaded('grade')) {
            return $this->getRelation('grade')?->feedback;
        }
        
        $grade = \Modules\Grading\Models\Grade::query()
            ->where('source_type', 'assignment')
            ->where('source_id', $this->assignment_id)
            ->where('user_id', $this->user_id)
            ->first();
        
        return $grade?->feedback;
    }

    /**
     * Get graded_at from grade relationship.
     */
    public function getGradedAtAttribute()
    {
        if ($this->relationLoaded('grade')) {
            return $this->getRelation('grade')?->graded_at;
        }
        
        $grade = \Modules\Grading\Models\Grade::query()
            ->where('source_type', 'assignment')
            ->where('source_id', $this->assignment_id)
            ->where('user_id', $this->user_id)
            ->first();
        
        return $grade?->graded_at;
    }
}
