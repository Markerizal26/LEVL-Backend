<?php

namespace Modules\Learning\Models;

use Illuminate\Database\Eloquent\Model;

class Submission extends Model
{
    protected $fillable = [
        'assignment_id', 'user_id', 'enrollment_id',
        'answer_text', 'status', 'score', 'feedback',
        'submitted_at', 'graded_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'graded_at' => 'datetime',
        'score' => 'integer',
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
}
