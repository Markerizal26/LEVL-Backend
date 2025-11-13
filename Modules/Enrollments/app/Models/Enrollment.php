<?php

namespace Modules\Enrollments\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Enrollment extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id', 'course_id', 'status',
        'enrolled_at', 'completed_at',
    ];

    protected $casts = [
        'enrolled_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    protected $hidden = [
        'course_id',
        'user_id',
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

    public function courseProgress()
    {
        return $this->hasOne(CourseProgress::class);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Database\Factories\EnrollmentFactory::new();
    }
}
