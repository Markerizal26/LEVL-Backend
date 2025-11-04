<?php

namespace Modules\Learning\Models;

use Illuminate\Database\Eloquent\Model;

class SubmissionFile extends Model
{
    protected $fillable = [
        'submission_id', 'file_path', 'file_name', 'file_size',
    ];

    public function submission()
    {
        return $this->belongsTo(Submission::class);
    }
}
