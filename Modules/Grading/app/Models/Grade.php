<?php

declare(strict_types=1);

namespace Modules\Grading\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Grading\Enums\GradeStatus;
use Modules\Grading\Enums\SourceType;

class Grade extends Model
{
    protected $fillable = [
        'source_type',
        'source_id',
        'submission_id',
        'user_id',
        'graded_by',
        'score',
        'original_score',
        'max_score',
        'is_override',
        'override_reason',
        'is_draft',
        'feedback',
        'status',
        'graded_at',
        'released_at',
    ];

    protected $casts = [
        'source_type' => SourceType::class,
        'status' => GradeStatus::class,
        'score' => 'decimal:2',
        'original_score' => 'decimal:2',
        'max_score' => 'decimal:2',
        'is_override' => 'boolean',
        'is_draft' => 'boolean',
        'graded_at' => 'datetime',
        'released_at' => 'datetime',
    ];

    /**
     * Get the source model (polymorphic).
     */
    public function source()
    {
        return match ($this->source_type) {
            SourceType::Assignment => $this->belongsTo(
                \Modules\Learning\Models\Assignment::class,
                'source_id'
            ),
            default => null,
        };
    }

    /**
     * Get the submission for this grade.
     */
    public function submission(): BelongsTo
    {
        return $this->belongsTo(\Modules\Learning\Models\Submission::class);
    }

    /**
     * Get the user who received this grade.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\Modules\Auth\Models\User::class);
    }

    /**
     * Get the grader who assigned this grade.
     */
    public function grader(): BelongsTo
    {
        return $this->belongsTo(\Modules\Auth\Models\User::class, 'graded_by');
    }

    /**
     * Get the reviews for this grade.
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(GradeReview::class);
    }

    /**
     * Override the grade with a new score.
     */
    public function override(float $newScore, string $reason, int $graderId): bool
    {
        if (empty($reason)) {
            throw new \InvalidArgumentException('Override reason is required');
        }

        // Preserve original score if not already overridden
        if (! $this->is_override) {
            $this->original_score = $this->score;
        }

        $this->score = $newScore;
        $this->is_override = true;
        $this->override_reason = $reason;
        $this->graded_by = $graderId;
        $this->graded_at = now();

        return $this->save();
    }

    /**
     * Mark the grade as released.
     */
    public function release(): bool
    {
        if ($this->is_draft) {
            throw new \InvalidArgumentException('Cannot release a draft grade');
        }

        $this->released_at = now();

        return $this->save();
    }

    /**
     * Check if the grade has been released.
     */
    public function isReleased(): bool
    {
        return $this->released_at !== null;
    }

    /**
     * Get the effective score (override score if overridden, otherwise original).
     */
    public function getEffectiveScoreAttribute(): float
    {
        return $this->score;
    }

    /**
     * Scope to filter draft grades.
     */
    public function scopeDraft($query)
    {
        return $query->where('is_draft', true);
    }

    /**
     * Scope to filter finalized grades.
     */
    public function scopeFinalized($query)
    {
        return $query->where('is_draft', false);
    }

    /**
     * Scope to filter released grades.
     */
    public function scopeReleased($query)
    {
        return $query->whereNotNull('released_at');
    }

    /**
     * Scope to filter unreleased grades.
     */
    public function scopeUnreleased($query)
    {
        return $query->whereNull('released_at');
    }

    /**
     * Scope to filter overridden grades.
     */
    public function scopeOverridden($query)
    {
        return $query->where('is_override', true);
    }

    /**
     * Scope to filter by submission.
     */
    public function scopeForSubmission($query, int $submissionId)
    {
        return $query->where('submission_id', $submissionId);
    }
}
