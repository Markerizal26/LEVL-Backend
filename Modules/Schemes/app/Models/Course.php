<?php

namespace Modules\Schemes\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Course extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Fields that support full-text search via QueryFilter.
     *
     * @var array<int, string>
     */
    protected array $searchable = ['title', 'short_desc'];

    protected $fillable = [
        'code', 'slug', 'title', 'short_desc', 'type',
        'level_tag', 'category_id', 'tags_json',
        'duration_estimate', 'thumbnail_path',
        'banner_path', 'progression_mode', 'enrollment_type', 'enrollment_key',
        'status', 'published_at', 'instructor_id',
    ];

    protected $casts = [
        'tags_json' => 'array',
        'published_at' => 'datetime',
    ];

    protected $appends = ['thumbnail_url', 'banner_url', 'tag_list'];

    protected $hidden = [
        'thumbnail_path',
        'banner_path',
        'enrollment_key',
        'deleted_at',
    ];

    public function getThumbnailUrlAttribute(): ?string
    {
        if (! $this->thumbnail_path) {
            return null;
        }

        return asset('storage/'.$this->thumbnail_path);
    }

    public function getBannerUrlAttribute(): ?string
    {
        if (! $this->banner_path) {
            return null;
        }

        return asset('storage/'.$this->banner_path);
    }

    public function tagPivot(): HasMany
    {
        return $this->hasMany(CourseTag::class, 'course_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(
            Tag::class,
            'course_tag_pivot',
            'course_id',
            'tag_id'
        )->withTimestamps();
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

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function getTagListAttribute(): array
    {
        if ($this->relationLoaded('tags')) {
            return $this->tags->pluck('name')->unique()->values()->toArray();
        }

        if (is_array($this->tags_json)) {
            return $this->tags_json;
        }

        return [];
    }

    /**
     * Get the outcomes for this course.
     */
    public function outcomes(): HasMany
    {
        return $this->hasMany(CourseOutcome::class)->orderBy('order');
    }

    /**
     * Get the prerequisites for this course.
     */
    public function prerequisites(): HasMany
    {
        return $this->hasMany(CoursePrerequisite::class, 'course_id');
    }

    /**
     * Get courses that require this course as prerequisite.
     */
    public function requiredBy(): HasMany
    {
        return $this->hasMany(CoursePrerequisite::class, 'prerequisite_course_id');
    }

    /**
     * Get prerequisite courses.
     */
    public function prerequisiteCourses(): BelongsToMany
    {
        return $this->belongsToMany(
            Course::class,
            'course_prerequisites',
            'course_id',
            'prerequisite_course_id'
        )->withPivot('is_required')->withTimestamps();
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Database\Factories\CourseFactory::new();
    }
}
