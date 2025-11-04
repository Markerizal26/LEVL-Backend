<?php

namespace Modules\Grading\Models;

use Illuminate\Database\Eloquent\Model;

class GradeReview extends Model
{
    protected $fillable = [
        'grade_id', 'requested_by', 'reason', 'response',
        'reviewed_by', 'status',
    ];

    public function grade()
    {
        return $this->belongsTo(Grade::class);
    }

    public function requester()
    {
        return $this->belongsTo(\Modules\Auth\Models\User::class, 'requested_by');
    }

    public function reviewer()
    {
        return $this->belongsTo(\Modules\Auth\Models\User::class, 'reviewed_by');
    }
}
