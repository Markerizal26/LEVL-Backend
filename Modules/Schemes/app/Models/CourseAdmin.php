<?php

namespace Modules\Schemes\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseAdmin extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'course_admins';

    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'course_id',
        'user_id',
    ];

    /**
     * Get the course that this admin manages.
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(\Modules\Schemes\Models\Course::class);
    }

    /**
     * Get the user who is the admin.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\Modules\Auth\Models\User::class);
    }
}
