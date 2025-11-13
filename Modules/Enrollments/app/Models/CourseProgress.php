<?php

namespace Modules\Enrollments\Models;

use Illuminate\Database\Eloquent\Model;

class CourseProgress extends Model
{
    protected $table = 'course_progress';

    protected $fillable = [
        'enrollment_id',
        'status',
        'progress_percent',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'progress_percent' => 'float',
    ];

    public function enrollment()
    {
        return $this->belongsTo(Enrollment::class);
    }

    /**
     * Get the course through enrollment.
     */
    public function course()
    {
        return $this->hasOneThrough(
            \Modules\Schemes\Models\Course::class,
            Enrollment::class,
            'id', // Foreign key on enrollments table
            'id', // Foreign key on courses table
            'enrollment_id', // Local key on course_progress table
            'course_id' // Local key on enrollments table
        );
    }

    /**
     * Get course_id via enrollment relationship.
     */
    public function getCourseIdAttribute()
    {
        return $this->enrollment?->course_id;
    }
}


