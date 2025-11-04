<?php

namespace Modules\Schemes\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Course extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code', 'slug', 'title', 'short_desc', 'type',
        'level_tag', 'category', 'tags_json', 'outcomes_json',
        'prereq_text', 'duration_estimate', 'thumbnail_path',
        'banner_path', 'visibility', 'progression_mode',
        'status', 'published_at', 'instructor_id',
    ];

    protected $casts = [
        'tags_json' => 'array',
        'outcomes_json' => 'array',
        'published_at' => 'datetime',
    ];

    public function tags(): HasMany
    {
        return $this->hasMany(CourseTag::class);
    }

    public function units(): HasMany
    {
        return $this->hasMany(Unit::class);
    }

    /**
     * Get the instructor of the course.
     */
    public function instructor(): BelongsTo
    {
        return $this->belongsTo(\Modules\Auth\Models\User::class, 'instructor_id');
    }

    /**
     * Get the admins of the course (many-to-many through course_admins pivot).
     */
    public function admins(): BelongsToMany
    {
        return $this->belongsToMany(
            \Modules\Auth\Models\User::class,
            'course_admins',
            'course_id',
            'user_id'
        )->withTimestamps();
    }

    /**
     * Get the course admins pivot records.
     */
    public function courseAdmins(): HasMany
    {
        return $this->hasMany(\Modules\Schemes\Models\CourseAdmin::class);
    }

    /**
     * Check if a user is an admin of this course.
     */
    public function hasAdmin($user): bool
    {
        return $this->admins()->where('user_id', is_object($user) ? $user->id : $user)->exists();
    }

    /**
     * Check if a user is the instructor of this course.
     */
    public function hasInstructor($user): bool
    {
        return $this->instructor_id === (is_object($user) ? $user->id : $user);
    }
}
