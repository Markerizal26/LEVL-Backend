<?php

namespace Modules\Grading\Entities;

use Illuminate\Database\Eloquent\Model;

class GradingRubric extends Model
{
    protected $fillable = [
        'scope_type', 'scope_id', 'criteria', 'description',
        'max_score', 'weight'
    ];

    public function scope()
    {
        return match ($this->scope_type) {
            'exercise' => $this->belongsTo(\Modules\Assessments\Entities\Exercise::class, 'scope_id'),
            'assignment' => $this->belongsTo(\Modules\Learning\Entities\Assignment::class, 'scope_id'),
        };
    }
}
