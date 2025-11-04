<?php

namespace Modules\Assessments\Models;

use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    protected $fillable = [
        'exercise_id', 'question_text', 'type',
        'score_weight', 'is_required', 'order',
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
