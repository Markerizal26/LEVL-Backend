<?php

namespace Modules\Assessments\Entities;

use Illuminate\Database\Eloquent\Model;

class AnswerFile extends Model
{
    protected $fillable = [
        'answer_id', 'file_path', 'file_name', 'file_size'
    ];

    public function answer()
    {
        return $this->belongsTo(Answer::class);
    }
}