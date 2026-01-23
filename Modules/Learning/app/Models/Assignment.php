<?php

declare(strict_types=1);

namespace Modules\Learning\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\Learning\Enums\AssignmentStatus;
use Modules\Learning\Enums\RandomizationType;
use Modules\Learning\Enums\ReviewMode;
use Modules\Learning\Enums\SubmissionType;
use Laravel\Scout\Searchable;

/**
 * @property int $id
 * @property int|null $lesson_id
 * @property string|null $assignable_type
 * @property int|null $assignable_id
 * @property int $created_by
 * @property string $title
 * @property string|null $description
 * @property string $type
 * @property SubmissionType $submission_type
 * @property float $max_score
 * @property \Illuminate\Support\Carbon|null $available_from
 * @property \Illuminate\Support\Carbon|null $deadline_at
 * @property int $tolerance_minutes
 * @property int|null $max_attempts
 * @property int $cooldown_minutes
 * @property bool $retake_enabled
 * @property ReviewMode $review_mode
 * @property RandomizationType $randomization_type
 * @property int|null $question_bank_count
 * @property AssignmentStatus $status
 * @property bool $allow_resubmit
 * @property int $late_penalty_percent
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read string|null $scope_type
 * @property-read \Modules\Schemes\Models\Lesson|null $lesson
 * @property-read \Modules\Auth\Models\User $creator
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Submission> $submissions
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Question> $questions
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Assignment> $prerequisites
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Assignment> $dependents
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Override> $overrides
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Override> $activeOverrides
 * @property-read \Modules\Schemes\Models\Lesson|\Modules\Schemes\Models\Unit|\Modules\Schemes\Models\Course|null $assignable
 */

use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Assignment extends Model implements HasMedia
{
    use Searchable, InteractsWithMedia;

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('attachments')
            ->useDisk('do')
            ->acceptsMimeTypes([
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/vnd.ms-powerpoint',
                'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                'application/zip',
                'image/jpeg',
                'image/png',
                'image/webp'
            ]);
    }

    protected $fillable = [
        'lesson_id',
        'assignable_type',
        'assignable_id',
        'created_by',
        'title',
        'description',
        'type',
        'submission_type',
        'max_score',
        'available_from',
        'deadline_at',
        'tolerance_minutes',
        'max_attempts',
        'cooldown_minutes',
        'retake_enabled',
        'review_mode',
        'randomization_type',
        'question_bank_count',
        'status',
        'allow_resubmit',
        'late_penalty_percent',
    ];

    protected $casts = [
        'submission_type' => SubmissionType::class,
        'status' => AssignmentStatus::class,
        'review_mode' => ReviewMode::class,
        'randomization_type' => RandomizationType::class,
        'available_from' => 'datetime',
        'deadline_at' => 'datetime',
        'tolerance_minutes' => 'integer',
        'max_attempts' => 'integer',
        'cooldown_minutes' => 'integer',
        'question_bank_count' => 'integer',
        'allow_resubmit' => 'boolean',
        'retake_enabled' => 'boolean',
        'late_penalty_percent' => 'integer',
    ];

        public function assignable(): MorphTo
    {
        return $this->morphTo();
    }

        public function lesson(): BelongsTo
    {
        return $this->belongsTo(\Modules\Schemes\Models\Lesson::class);
    }

        public function creator(): BelongsTo
    {
        return $this->belongsTo(\Modules\Auth\Models\User::class, 'created_by');
    }

        public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }

        public function questions(): HasMany
    {
        return $this->hasMany(Question::class)->ordered();
    }

        public function prerequisites(): BelongsToMany
    {
        return $this->belongsToMany(
            Assignment::class,
            'assignment_prerequisites',
            'assignment_id',
            'prerequisite_id'
        )->withTimestamps();
    }

        public function dependents(): BelongsToMany
    {
        return $this->belongsToMany(
            Assignment::class,
            'assignment_prerequisites',
            'prerequisite_id',
            'assignment_id'
        )->withTimestamps();
    }

        public function overrides(): HasMany
    {
        return $this->hasMany(Override::class);
    }

        public function activeOverrides(): HasMany
    {
        return $this->hasMany(Override::class)->active();
    }

        public function isAvailable(): bool
    {
        if ($this->status !== AssignmentStatus::Published) {
            return false;
        }

        $now = now();
        if ($this->available_from && $now->lt($this->available_from)) {
            return false;
        }

        return true;
    }

        public function isPastDeadline(): bool
    {
        if (! $this->deadline_at) {
            return false;
        }

        return now()->gt($this->deadline_at);
    }

        public function isWithinTolerance(): bool
    {
        if (! $this->deadline_at) {
            return true;
        }

        $toleranceEnd = $this->deadline_at->copy()->addMinutes($this->tolerance_minutes ?? 0);

        return now()->lte($toleranceEnd);
    }

        public function isPastTolerance(): bool
    {
        if (! $this->deadline_at) {
            return false;
        }

        $toleranceEnd = $this->deadline_at->copy()->addMinutes($this->tolerance_minutes ?? 0);

        return now()->gt($toleranceEnd);
    }

        public function getScopeTypeAttribute(): ?string
    {
        if ($this->assignable_type) {
            return match ($this->assignable_type) {
                \Modules\Schemes\Models\Lesson::class => 'lesson',
                \Modules\Schemes\Models\Unit::class => 'unit',
                \Modules\Schemes\Models\Course::class => 'course',
                default => null,
            };
        }

        
        if ($this->lesson_id) {
            return 'lesson';
        }

        return null;
    }

        public function scopeForLesson($query, int $lessonId)
    {
        return $query->where(function ($q) use ($lessonId) {
            $q->where('assignable_type', \Modules\Schemes\Models\Lesson::class)
                ->where('assignable_id', $lessonId);
        })->orWhere('lesson_id', $lessonId);
    }

        public function scopeForUnit($query, int $unitId)
    {
        $lessonIds = \Modules\Schemes\Models\Lesson::where('unit_id', $unitId)->pluck('id')->toArray();

        return $query->where(function ($q) use ($unitId, $lessonIds) {
            $q->where(function ($subQ) use ($unitId) {
                $subQ->where('assignable_type', \Modules\Schemes\Models\Unit::class)
                    ->where('assignable_id', $unitId);
            })
            ->orWhere(function ($subQ) use ($lessonIds) {
                $subQ->where('assignable_type', \Modules\Schemes\Models\Lesson::class)
                    ->whereIn('assignable_id', $lessonIds);
            })
            ->orWhereIn('lesson_id', $lessonIds);
        });
    }

        public function scopeForCourse($query, int $courseId)
    {
        $unitIds = \Modules\Schemes\Models\Unit::where('course_id', $courseId)->pluck('id')->toArray();
        $lessonIds = \Modules\Schemes\Models\Lesson::whereIn('unit_id', $unitIds)->pluck('id')->toArray();

        return $query->where(function ($q) use ($courseId, $unitIds, $lessonIds) {
            $q->where(function ($subQ) use ($courseId) {
                $subQ->where('assignable_type', \Modules\Schemes\Models\Course::class)
                    ->where('assignable_id', $courseId);
            })
            ->orWhere(function ($subQ) use ($unitIds) {
                $subQ->where('assignable_type', \Modules\Schemes\Models\Unit::class)
                    ->whereIn('assignable_id', $unitIds);
            })
            ->orWhere(function ($subQ) use ($lessonIds) {
                $subQ->where('assignable_type', \Modules\Schemes\Models\Lesson::class)
                    ->whereIn('assignable_id', $lessonIds);
            })
            ->orWhereIn('lesson_id', $lessonIds);
        });
    }

        public function scopePublished($query)
    {
        return $query->where('status', AssignmentStatus::Published);
    }

        public function scopeAvailable($query)
    {
        return $query->published()
            ->where(function ($q) {
                $q->whereNull('available_from')
                    ->orWhere('available_from', '<=', now());
            });
    }

        public function hasValidScope(): bool
    {
        $hasPolymorphic = $this->assignable_type && $this->assignable_id;
        $hasLegacy = (bool) $this->lesson_id;

        
        return $hasPolymorphic xor $hasLegacy;
    }

        public function getCourseId(): ?int
    {
        
        if ($this->assignable_type === \Modules\Schemes\Models\Course::class) {
            return $this->assignable_id;
        }

        
        if ($this->assignable_type === \Modules\Schemes\Models\Unit::class) {
            $unit = $this->assignable;

            return $unit?->course_id;
        }

        
        if ($this->assignable_type === \Modules\Schemes\Models\Lesson::class) {
            $lesson = $this->assignable;

            return $lesson?->unit?->course_id;
        }

        
        if ($this->lesson_id) {
            $this->loadMissing('lesson.unit');

            return $this->lesson?->unit?->course_id;
        }

        return null;
    }
}
