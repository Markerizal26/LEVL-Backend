<?php

namespace Modules\Enrollments\Entities;

use Illuminate\Database\Eloquent\Model;

class UnitProgress extends Model
{
    protected $table = 'unit_progress';

    protected $fillable = [
        'enrollment_id', 'unit_id', 'status',
        'progress_percent', 'started_at', 'completed_at'
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

    public function unit()
    {
        return $this->belongsTo(\Modules\Schemes\Entities\Unit::class);
    }
}
