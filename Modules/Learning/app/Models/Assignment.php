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

class Assignment extends Model
{
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

    /**
     * Polymorphic relationship to the assignable scope (Lesson, Unit, or Course).
     */
    public function assignable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Legacy relationship to lesson (for backward compatibility).
     *
     * @deprecated Use assignable() instead
     */
    public function lesson(): BelongsTo
    {
        return $this->belongsTo(\Modules\Schemes\Models\Lesson::class);
    }

    /**
     * Get the creator of the assignment.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(\Modules\Auth\Models\User::class, 'created_by');
    }

    /**
     * Get all submissions for this assignment.
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }

    /**
     * Get all questions for this assignment.
     */
    public function questions(): HasMany
    {
        return $this->hasMany(Question::class)->ordered();
    }

    /**
     * Get prerequisites for this assignment.
     */
    public function prerequisites(): BelongsToMany
    {
        return $this->belongsToMany(
            Assignment::class,
            'assignment_prerequisites',
            'assignment_id',
            'prerequisite_id'
        )->withTimestamps();
    }

    /**
     * Get assignments that depend on this assignment.
     */
    public function dependents(): BelongsToMany
    {
        return $this->belongsToMany(
            Assignment::class,
            'assignment_prerequisites',
            'prerequisite_id',
            'assignment_id'
        )->withTimestamps();
    }

    /**
     * Get all overrides for this assignment.
     */
    public function overrides(): HasMany
    {
        return $this->hasMany(Override::class);
    }

    /**
     * Get active overrides for this assignment.
     */
    public function activeOverrides(): HasMany
    {
        return $this->hasMany(Override::class)->active();
    }

    /**
     * Check if the assignment is currently available.
     */
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

    /**
     * Check if the deadline has passed.
     */
    public function isPastDeadline(): bool
    {
        if (! $this->deadline_at) {
            return false;
        }

        return now()->gt($this->deadline_at);
    }

    /**
     * Check if submission is within tolerance window.
     */
    public function isWithinTolerance(): bool
    {
        if (! $this->deadline_at) {
            return true;
        }

        $toleranceEnd = $this->deadline_at->copy()->addMinutes($this->tolerance_minutes ?? 0);

        return now()->lte($toleranceEnd);
    }

    /**
     * Check if submission is past tolerance window.
     */
    public function isPastTolerance(): bool
    {
        if (! $this->deadline_at) {
            return false;
        }

        $toleranceEnd = $this->deadline_at->copy()->addMinutes($this->tolerance_minutes ?? 0);

        return now()->gt($toleranceEnd);
    }

    /**
     * Get the scope type (lesson, unit, or course).
     */
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

        // Fallback for legacy lesson_id
        if ($this->lesson_id) {
            return 'lesson';
        }

        return null;
    }

    /**
     * Scope to filter by assignable type.
     */
    public function scopeForLesson($query, int $lessonId)
    {
        return $query->where(function ($q) use ($lessonId) {
            $q->where('assignable_type', \Modules\Schemes\Models\Lesson::class)
                ->where('assignable_id', $lessonId);
        })->orWhere('lesson_id', $lessonId);
    }

    /**
     * Scope to filter by unit.
     */
    public function scopeForUnit($query, int $unitId)
    {
        return $query->where('assignable_type', \Modules\Schemes\Models\Unit::class)
            ->where('assignable_id', $unitId);
    }

    /**
     * Scope to filter by course.
     */
    public function scopeForCourse($query, int $courseId)
    {
        return $query->where('assignable_type', \Modules\Schemes\Models\Course::class)
            ->where('assignable_id', $courseId);
    }

    /**
     * Scope to filter published assignments.
     */
    public function scopePublished($query)
    {
        return $query->where('status', AssignmentStatus::Published);
    }

    /**
     * Scope to filter available assignments.
     */
    public function scopeAvailable($query)
    {
        return $query->published()
            ->where(function ($q) {
                $q->whereNull('available_from')
                    ->orWhere('available_from', '<=', now());
            });
    }

    /**
     * Validate that assignment has exactly one parent scope.
     */
    public function hasValidScope(): bool
    {
        $hasPolymorphic = $this->assignable_type && $this->assignable_id;
        $hasLegacy = (bool) $this->lesson_id;

        // Must have exactly one scope
        return $hasPolymorphic xor $hasLegacy;
    }

    /**
     * Get the course ID for this assignment.
     * Traverses the hierarchy to find the course.
     * Requirements: 22.5
     */
    public function getCourseId(): ?int
    {
        // If directly attached to a course
        if ($this->assignable_type === \Modules\Schemes\Models\Course::class) {
            return $this->assignable_id;
        }

        // If attached to a unit, get the course from the unit
        if ($this->assignable_type === \Modules\Schemes\Models\Unit::class) {
            $unit = $this->assignable;

            return $unit?->course_id;
        }

        // If attached to a lesson, get the course through unit
        if ($this->assignable_type === \Modules\Schemes\Models\Lesson::class) {
            $lesson = $this->assignable;

            return $lesson?->unit?->course_id;
        }

        // Legacy: if using lesson_id
        if ($this->lesson_id) {
            $this->loadMissing('lesson.unit');

            return $this->lesson?->unit?->course_id;
        }

        return null;
    }
}
