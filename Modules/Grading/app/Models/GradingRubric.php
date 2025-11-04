<?php

namespace Modules\Grading\Models;

use Illuminate\Database\Eloquent\Model;

class GradingRubric extends Model
{
    protected $fillable = [
        'scope_type', 'scope_id', 'criteria', 'description',
        'max_score', 'weight',
    ];

    public function scope()
    {
        return match ($this->scope_type) {
            'exercise' => $this->belongsTo(\Modules\Assessments\Models\Exercise::class, 'scope_id'),
            'assignment' => $this->belongsTo(\Modules\Learning\Models\Assignment::class, 'scope_id'),
        };
    }
}
