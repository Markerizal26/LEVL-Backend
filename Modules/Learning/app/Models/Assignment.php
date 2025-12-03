<?php

namespace Modules\Learning\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Learning\Enums\AssignmentStatus;
use Modules\Learning\Enums\SubmissionType;

class Assignment extends Model
{
    protected $fillable = [
        'lesson_id', 'created_by', 'title', 'description',
        'type', 'submission_type', 'max_score',
        'available_from', 'deadline_at', 'status',
        'allow_resubmit', 'late_penalty_percent',
    ];

    protected $casts = [
        'submission_type' => SubmissionType::class,
        'status' => AssignmentStatus::class,
        'available_from' => 'datetime',
        'deadline_at' => 'datetime',
        'allow_resubmit' => 'boolean',
        'late_penalty_percent' => 'integer',
    ];

    public function lesson()
    {
        return $this->belongsTo(\Modules\Schemes\Models\Lesson::class);
    }

    public function creator()
    {
        return $this->belongsTo(\Modules\Auth\Models\User::class, 'created_by');
    }

    public function submissions()
    {
        return $this->hasMany(Submission::class);
    }

    public function isAvailable(): bool
    {
        if ($this->status !== AssignmentStatus::Published) {
            return false;
        }

        $now = now();
        if ($this->available_from && $now->lt($this->available_from)) {
            return false;
        }

        return true;
    }

    public function isPastDeadline(): bool
    {
        if (! $this->deadline_at) {
            return false;
        }

        return now()->gt($this->deadline_at);
    }
}
