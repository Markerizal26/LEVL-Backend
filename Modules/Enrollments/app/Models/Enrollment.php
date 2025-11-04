<?php

namespace Modules\Enrollments\Models;

use Illuminate\Database\Eloquent\Model;

class Enrollment extends Model
{
    protected $fillable = [
        'user_id', 'course_id', 'status',
        'enrolled_at', 'completed_at', 'progress_percent',
    ];

    protected $casts = [
        'enrolled_at' => 'datetime',
        'completed_at' => 'datetime',
        'progress_percent' => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(\Modules\Auth\Models\User::class);
    }

    public function course()
    {
        return $this->belongsTo(\Modules\Schemes\Models\Course::class);
    }

    public function unitProgress()
    {
        return $this->hasMany(UnitProgress::class);
    }

    public function lessonProgress()
    {
        return $this->hasMany(LessonProgress::class);
    }
}
