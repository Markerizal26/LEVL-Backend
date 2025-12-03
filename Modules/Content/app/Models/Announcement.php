<?php

namespace Modules\Content\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Auth\Models\User;
use Modules\Content\Enums\ContentStatus;
use Modules\Content\Enums\Priority;
use Modules\Content\Enums\TargetType;
use Modules\Schemes\Models\Course;

class Announcement extends Model
{
    use HasFactory, SoftDeletes;

    protected static function newFactory()
    {
        return \Modules\Content\Database\Factories\AnnouncementFactory::new();
    }

    protected $fillable = [
        'author_id',
        'course_id',
        'title',
        'content',
        'status',
        'target_type',
        'target_value',
        'priority',
        'published_at',
        'scheduled_at',
        'views_count',
        'deleted_by',
    ];

    protected $casts = [
        'status' => ContentStatus::class,
        'target_type' => TargetType::class,
        'priority' => Priority::class,
        'published_at' => 'datetime',
        'scheduled_at' => 'datetime',
        'views_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function reads(): MorphMany
    {
        return $this->morphMany(ContentRead::class, 'readable');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(ContentRevision::class, 'content_id')
            ->where('content_type', self::class);
    }

    public function scopePublished($query)
    {
        return $query->where('status', ContentStatus::Published)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function scopeForUser($query, User $user)
    {
        return $query->where(function ($q) use ($user) {
            $q->where('target_type', TargetType::All)
                ->orWhere(function ($q2) use ($user) {
                    $q2->where('target_type', TargetType::Role)
                        ->where('target_value', $user->roles->pluck('name')->first());
                })
                ->orWhere(function ($q3) use ($user) {
                    $q3->where('target_type', TargetType::Course)
                        ->whereIn('course_id', $user->enrollments->pluck('course_id'));
                });
        });
    }

    public function scopeForCourse($query, $courseId)
    {
        return $query->where('course_id', $courseId);
    }

    public function isPublished(): bool
    {
        return $this->status === ContentStatus::Published &&
               $this->published_at !== null &&
               $this->published_at->isPast();
    }

    public function isScheduled(): bool
    {
        return $this->status === ContentStatus::Scheduled &&
               $this->scheduled_at !== null &&
               $this->scheduled_at->isFuture();
    }

    public function markAsReadBy(User $user): void
    {
        ContentRead::firstOrCreate([
            'user_id' => $user->id,
            'readable_type' => self::class,
            'readable_id' => $this->id,
        ]);
    }

    public function isReadBy(User $user): bool
    {
        return $this->reads()
            ->where('user_id', $user->id)
            ->exists();
    }
}
