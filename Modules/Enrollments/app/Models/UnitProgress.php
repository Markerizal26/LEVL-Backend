<?php

namespace Modules\Enrollments\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Enrollments\Enums\ProgressStatus;

class UnitProgress extends Model
{
    protected $table = 'unit_progress';

    protected $fillable = [
        'enrollment_id', 'unit_id', 'status',
        'progress_percent', 'started_at', 'completed_at',
    ];

    protected $casts = [
        'status' => ProgressStatus::class,
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'progress_percent' => 'float',
    ];

    public function enrollment()
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function unit()
    {
        return $this->belongsTo(\Modules\Schemes\Models\Unit::class);
    }
}
