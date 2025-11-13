<?php

namespace Modules\Schemes\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CoursePrerequisite extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'prerequisite_course_id',
        'is_required',
    ];

    protected $casts = [
        'is_required' => 'boolean',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function prerequisiteCourse()
    {
        return $this->belongsTo(Course::class, 'prerequisite_course_id');
    }
}

