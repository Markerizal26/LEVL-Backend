<?php

namespace Modules\Schemes\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CourseOutcome extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'outcome_text',
        'order',
    ];

    protected $casts = [
        'order' => 'integer',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }
}

