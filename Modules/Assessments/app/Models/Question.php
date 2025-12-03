<?php

namespace Modules\Assessments\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Assessments\Enums\QuestionType;

class Question extends Model
{
    protected $fillable = [
        'exercise_id', 'question_text', 'type',
        'score_weight', 'is_required', 'order',
    ];

    protected $casts = [
        'type' => QuestionType::class,
        'is_required' => 'boolean',
    ];

    public function exercise()
    {
        return $this->belongsTo(Exercise::class);
    }

    public function options()
    {
        return $this->hasMany(QuestionOption::class);
    }

    public function answers()
    {
        return $this->hasMany(Answer::class);
    }
}
