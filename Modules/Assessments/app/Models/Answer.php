<?php

namespace Modules\Assessments\Models;

use Illuminate\Database\Eloquent\Model;

class Answer extends Model
{
    protected $fillable = [
        'attempt_id', 'question_id', 'selected_option_id',
        'answer_text', 'score_awarded', 'feedback',
    ];

    public function attempt()
    {
        return $this->belongsTo(Attempt::class);
    }

    public function question()
    {
        return $this->belongsTo(Question::class);
    }

    public function selectedOption()
    {
        return $this->belongsTo(QuestionOption::class, 'selected_option_id');
    }

    public function files()
    {
        return $this->hasMany(AnswerFile::class);
    }
}
