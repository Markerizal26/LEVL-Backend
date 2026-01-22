<?php

declare(strict_types=1);

namespace Modules\Learning\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Laravel\Scout\Searchable;
use Modules\Learning\Enums\SubmissionState;
use Modules\Learning\Enums\SubmissionStatus;

/**
 * Submission model for student assignment submissions.
 *
 * @property int $id
 * @property int $assignment_id
 * @property int $user_id
 * @property int|null $enrollment_id
 * @property string|null $answer_text
 * @property SubmissionStatus|null $status
 * @property SubmissionState|null $state
 * @property float|null $score
 * @property array<int>|null $question_set
 * @property \Carbon\Carbon|null $submitted_at
 * @property int $attempt_number
 * @property bool $is_late
 * @property bool $is_resubmission
 * @property int|null $previous_submission_id
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class Submission extends Model
{
    use Searchable;

    protected $fillable = [
        'assignment_id',
        'user_id',
        'enrollment_id',
        'answer_text',
        'status',
        'state',
        'score',
        'question_set',
        'submitted_at',
        'attempt_number',
        'is_late',
        'is_resubmission',
        'previous_submission_id',
    ];

    protected $casts = [
        'status' => SubmissionStatus::class,
        'state' => SubmissionState::class,
        'submitted_at' => 'datetime',
        'attempt_number' => 'integer',
        'is_late' => 'boolean',
        'is_resubmission' => 'boolean',
        'score' => 'decimal:2',
        'question_set' => 'array',
    ];

    /**
     * Get the name of the index associated with the model.
     */
    public function searchableAs(): string
    {
        return 'submissions_index';
    }

    /**
     * Get the indexable data array for the model.
     *
     * This method defines the searchable fields for Meilisearch:
     * - Student name/email for searching by student
     * - Assignment title for searching by assignment
     * - Submission state for filtering
     * - Score for filtering by score range
     * - Submitted date for filtering by date range
     *
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        // Load relationships if not already loaded
        $this->loadMissing(['user', 'assignment']);

        return [
            'id' => $this->id,
            'assignment_id' => $this->assignment_id,
            'user_id' => $this->user_id,
            // Student information for searching
            'student_name' => $this->user?->name ?? '',
            'student_email' => $this->user?->email ?? '',
            // Assignment information for searching
            'assignment_title' => $this->assignment?->title ?? '',
            // Submission state for filtering
            'state' => $this->state?->value ?? $this->status?->value ?? '',
            // Score for filtering by score range
            'score' => $this->score !== null ? (float) $this->score : null,
            // Submitted date as timestamp for filtering by date range
            'submitted_at' => $this->submitted_at?->timestamp,
            'submitted_at_formatted' => $this->submitted_at?->toIso8601String(),
            // Additional useful fields
            'attempt_number' => $this->attempt_number,
            'is_late' => $this->is_late,
            'enrollment_id' => $this->enrollment_id,
            'created_at' => $this->created_at?->timestamp,
        ];
    }

    /**
     * Determine if the model should be searchable.
     */
    public function shouldBeSearchable(): bool
    {
        // Only index submissions that have been submitted
        // (not in_progress submissions)
        return $this->state !== SubmissionState::InProgress;
    }

    /**
     * Get the assignment for this submission.
     */
    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

    /**
     * Get the user who made this submission.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\Modules\Auth\Models\User::class);
    }

    /**
     * Get the enrollment for this submission.
     */
    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(\Modules\Enrollments\Models\Enrollment::class);
    }

    /**
     * Get the files attached to this submission.
     */
    public function files(): HasMany
    {
        return $this->hasMany(SubmissionFile::class);
    }

    /**
     * Get the answers for this submission.
     */
    public function answers(): HasMany
    {
        return $this->hasMany(Answer::class);
    }

    /**
     * Get the previous submission (for resubmissions).
     */
    public function previousSubmission(): BelongsTo
    {
        return $this->belongsTo(Submission::class, 'previous_submission_id');
    }

    /**
     * Get resubmissions of this submission.
     */
    public function resubmissions(): HasMany
    {
        return $this->hasMany(Submission::class, 'previous_submission_id');
    }

    /**
     * Get the grade for this submission.
     */
    public function grade(): HasOne
    {
        return $this->hasOne(\Modules\Grading\Models\Grade::class, 'submission_id');
    }

    /**
     * Get the appeal for this submission.
     */
    public function appeal(): HasOne
    {
        return $this->hasOne(\Modules\Grading\Models\Appeal::class, 'submission_id');
    }

    /**
     * Get the state as SubmissionState enum.
     * Uses the new state field if available, otherwise maps from status.
     */
    public function getStateAttribute($value): ?SubmissionState
    {
        // If state field has a value, use it directly
        if ($value) {
            return $value instanceof SubmissionState ? $value : SubmissionState::from($value);
        }

        // Fallback: Map old status values to new state values
        if (! $this->status) {
            return null;
        }

        return match ($this->status->value) {
            'draft' => SubmissionState::InProgress,
            'submitted' => SubmissionState::Submitted,
            'graded' => SubmissionState::Graded,
            'late' => SubmissionState::Submitted,
            default => null,
        };
    }

    /**
     * Transition to a new state with validation.
     *
     * @throws \InvalidArgumentException if transition is invalid
     */
    public function transitionTo(SubmissionState $newState, int $actorId): bool
    {
        $currentState = $this->state;

        if ($currentState && ! $currentState->canTransitionTo($newState)) {
            throw new \InvalidArgumentException(
                "Invalid state transition from {$currentState->value} to {$newState->value}"
            );
        }

        $oldState = $currentState;

        $this->attributes['state'] = $newState->value;

        $statusValue = match ($newState) {
            SubmissionState::InProgress => 'draft',
            SubmissionState::Submitted => $this->is_late ? 'late' : 'submitted',
            SubmissionState::AutoGraded => 'graded',
            SubmissionState::PendingManualGrading => 'submitted',
            SubmissionState::Graded => 'graded',
            SubmissionState::Released => 'graded',
        };

        $this->status = SubmissionStatus::from($statusValue);

        $saved = $this->save();

        if ($saved) {
            \Modules\Learning\Events\SubmissionStateChanged::dispatch(
                $this,
                $oldState,
                $newState,
                $actorId
            );
        }

        return $saved;
    }

    /**
     * Check if this submission can transition to the given state.
     */
    public function canTransitionTo(SubmissionState $newState): bool
    {
        $currentState = $this->state;

        if (! $currentState) {
            return true;
        }

        return $currentState->canTransitionTo($newState);
    }

    /**
     * Scope to filter by student.
     */
    public function scopeForStudent($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to filter by assignment.
     */
    public function scopeForAssignment($query, int $assignmentId)
    {
        return $query->where('assignment_id', $assignmentId);
    }

    /**
     * Scope to filter by status.
     */
    public function scopeWithStatus($query, SubmissionStatus $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get the highest scoring submission for a student/assignment.
     */
    public function scopeHighestScore($query)
    {
        return $query->orderByDesc('score');
    }

    /**
     * Scope to filter late submissions.
     */
    public function scopeLate($query)
    {
        return $query->where('is_late', true);
    }

    /**
     * Scope to filter pending manual grading.
     */
    public function scopePendingManualGrading($query)
    {
        return $query->where('status', SubmissionStatus::Submitted)
            ->whereDoesntHave('grade', function ($q) {
                $q->where('is_draft', false);
            });
    }

    /**
     * Get the feedback from the grade relationship.
     */
    public function getFeedbackAttribute()
    {
        if ($this->relationLoaded('grade')) {
            return $this->getRelation('grade')?->feedback;
        }

        return $this->grade?->feedback;
    }

    /**
     * Get the graded_at from the grade relationship.
     */
    public function getGradedAtAttribute()
    {
        if ($this->relationLoaded('grade')) {
            return $this->getRelation('grade')?->graded_at;
        }

        return $this->grade?->graded_at;
    }

    /**
     * Get visible feedback based on review mode.
     * Returns null if feedback should not be visible.
     */
    public function getVisibleFeedback(?int $userId = null): ?string
    {
        $reviewModeService = app(\Modules\Learning\Contracts\Services\ReviewModeServiceInterface::class);

        if (! $reviewModeService->canViewFeedback($this, $userId)) {
            return null;
        }

        return $this->feedback;
    }

    /**
     * Get visible answers with feedback based on review mode.
     * Returns answers with feedback hidden if not visible.
     */
    public function getVisibleAnswers(?int $userId = null): \Illuminate\Support\Collection
    {
        $reviewModeService = app(\Modules\Learning\Contracts\Services\ReviewModeServiceInterface::class);

        $canViewAnswers = $reviewModeService->canViewAnswers($this, $userId);
        $canViewFeedback = $reviewModeService->canViewFeedback($this, $userId);

        return $this->answers->map(function ($answer) use ($canViewAnswers, $canViewFeedback) {
            $data = [
                'id' => $answer->id,
                'question_id' => $answer->question_id,
                'score' => $answer->score,
                'is_auto_graded' => $answer->is_auto_graded,
            ];

            // Include answer content only if visible
            if ($canViewAnswers) {
                $data['content'] = $answer->content;
                $data['selected_options'] = $answer->selected_options;
                $data['file_paths'] = $answer->file_paths;
            }

            // Include feedback only if visible
            if ($canViewFeedback) {
                $data['feedback'] = $answer->feedback;
            }

            return $data;
        });
    }

    /**
     * Get the review mode visibility status.
     */
    public function getVisibilityStatus(?int $userId = null): array
    {
        $reviewModeService = app(\Modules\Learning\Contracts\Services\ReviewModeServiceInterface::class);

        return $reviewModeService->getVisibilityStatus($this, $userId);
    }
}
