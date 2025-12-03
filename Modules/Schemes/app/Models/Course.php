<?php

namespace Modules\Schemes\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;
use Modules\Schemes\Enums\CourseStatus;
use Modules\Schemes\Enums\CourseType;
use Modules\Schemes\Enums\EnrollmentType;
use Modules\Schemes\Enums\LevelTag;
use Modules\Schemes\Enums\ProgressionMode;

class Course extends Model
{
    use HasFactory, Searchable, SoftDeletes;

    /**
     * Fields that support full-text search via QueryFilter.
     *
     * @var array<int, string>
     */
    protected array $searchable = ['title', 'short_desc'];

    protected $fillable = [
        'code', 'slug', 'title', 'short_desc', 'type',
        'level_tag', 'category_id', 'tags_json', 'prereq_text',
        'duration_estimate', 'thumbnail_path',
        'banner_path', 'progression_mode', 'enrollment_type', 'enrollment_key',
        'status', 'published_at', 'instructor_id', 'deleted_by',
    ];

    protected $casts = [
        'tags_json' => 'array',
        'published_at' => 'datetime',
        'status' => CourseStatus::class,
        'type' => CourseType::class,
        'level_tag' => LevelTag::class,
        'enrollment_type' => EnrollmentType::class,
        'progression_mode' => ProgressionMode::class,
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

        $uploader = app(\App\Services\UploadService::class);

        return $uploader->getPublicUrl($this->thumbnail_path);
    }

    public function getBannerUrlAttribute(): ?string
    {
        if (! $this->banner_path) {
            return null;
        }

        $uploader = app(\App\Services\UploadService::class);

        return $uploader->getPublicUrl($this->banner_path);
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
     * Get the user who deleted the course.
     */
    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(\Modules\Auth\Models\User::class, 'deleted_by');
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

    public function category(): BelongsTo
    {
        return $this->belongsTo(\Modules\Common\Models\Category::class, 'category_id');
    }

    /**
     * Get the outcomes for this course.
     */
    public function outcomes(): HasMany
    {
        return $this->hasMany(CourseOutcome::class)->orderBy('order');
    }

    /**
     * Get the indexable data array for the model.
     */
    public function toSearchableArray(): array
    {
        // Load relationships if not already loaded
        $this->loadMissing(['category', 'instructor', 'tags']);

        return [
            'id' => $this->id,
            'title' => $this->title,
            'short_desc' => $this->short_desc,
            'code' => $this->code,
            'level_tag' => $this->level_tag,
            'category_id' => $this->category_id,
            'category_name' => $this->category?->name,
            'instructor_id' => $this->instructor_id,
            'instructor_name' => $this->instructor?->name,
            'tags' => $this->tags->pluck('name')->toArray(),
            'status' => $this->status,
            'type' => $this->type,
            'duration_estimate' => $this->duration_estimate,
            'published_at' => $this->published_at?->timestamp,
        ];
    }

    /**
     * Get the name of the index associated with the model.
     */
    public function searchableAs(): string
    {
        return 'courses_index';
    }

    /**
     * Determine if the model should be searchable.
     */
    public function shouldBeSearchable(): bool
    {
        return $this->status === CourseStatus::Published;
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Database\Factories\CourseFactory::new();
    }
}
