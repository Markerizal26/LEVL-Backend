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
        'submitted_at' => 'datetime',
        'attempt_number' => 'integer',
        'is_late' => 'boolean',
        'is_resubmission' => 'boolean',
        'score' => 'decimal:2',
        'question_set' => 'array',
    ];

    protected static function newFactory()
    {
        return \Modules\Learning\Database\Factories\SubmissionFactory::new();
    }

    protected function serializeDate(\DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i:s');
    }

    protected function getArrayableAppends(): array
    {
        $this->appends = array_unique(array_merge($this->appends, [
            'status_value',
            'state_value',
        ]));

        return parent::getArrayableAppends();
    }

    public function getStatusValueAttribute(): ?string
    {
        return $this->status?->value;
    }

    public function getStateValueAttribute(): ?string
    {
        return $this->state?->value;
    }

    public function searchableAs(): string
    {
        return 'submissions_index';
    }

        public function toSearchableArray(): array
    {
        
        $this->loadMissing(['user', 'assignment']);

        return [
            'id' => $this->id,
            'assignment_id' => $this->assignment_id,
            'user_id' => $this->user_id,
            
            'student_name' => $this->user?->name ?? '',
            'student_email' => $this->user?->email ?? '',
            
            'assignment_title' => $this->assignment?->title ?? '',
            
            'state' => $this->state?->value ?? $this->status?->value ?? '',
            
            'score' => $this->score !== null ? (float) $this->score : null,
            
            'submitted_at' => $this->submitted_at?->timestamp,
            'submitted_at_formatted' => $this->submitted_at?->toIso8601String(),
            
            'attempt_number' => $this->attempt_number,
            'is_late' => $this->is_late,
            'enrollment_id' => $this->enrollment_id,
            'created_at' => $this->created_at?->timestamp,
        ];
    }

        public function shouldBeSearchable(): bool
    {
        
        
        return $this->state !== SubmissionState::InProgress;
    }

        public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

        public function user(): BelongsTo
    {
        return $this->belongsTo(\Modules\Auth\Models\User::class);
    }

        public function enrollment(): BelongsTo
    {
        return $this->belongsTo(\Modules\Enrollments\Models\Enrollment::class);
    }

        public function files(): HasMany
    {
        return $this->hasMany(SubmissionFile::class);
    }

        public function answers(): HasMany
    {
        return $this->hasMany(Answer::class);
    }

        public function previousSubmission(): BelongsTo
    {
        return $this->belongsTo(Submission::class, 'previous_submission_id');
    }

        public function resubmissions(): HasMany
    {
        return $this->hasMany(Submission::class, 'previous_submission_id');
    }

        public function grade(): HasOne
    {
        return $this->hasOne(\Modules\Grading\Models\Grade::class, 'submission_id');
    }



        public function getStateAttribute($value): ?SubmissionState
    {
        if ($value) {
            if ($value instanceof SubmissionState) {
                return $value;
            }
            $state = SubmissionState::tryFrom($value);
            if ($state) {
                return $state;
            }
        }

        if (! $this->status) {
            return null;
        }

        return match ($this->status->value) {
            'draft' => SubmissionState::InProgress,
            'submitted' => SubmissionState::Submitted,
            'graded' => SubmissionState::Graded,
            'late' => SubmissionState::Submitted,
            'missing' => SubmissionState::Submitted,
            default => null,
        };
    }

        public function transitionTo(SubmissionState $newState, int $actorId): bool
    {
        $currentState = $this->state;

        if ($currentState && ! $currentState->canTransitionTo($newState)) {
            throw \Modules\Learning\Exceptions\SubmissionException::notAllowed(
                __('messages.submissions.invalid_state_transition', [
                    'from' => $currentState->label(),
                    'to' => $newState->label(),
                ])
            );
        }

        $oldState = $currentState;

        $this->attributes['state'] = $newState->value;

        $statusValue = match ($newState) {
            SubmissionState::InProgress => 'draft',
            SubmissionState::Submitted => ($this->status?->value === 'missing')
                ? 'missing'
                : ($this->is_late ? 'late' : 'submitted'),
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

        public function canTransitionTo(SubmissionState $newState): bool
    {
        $currentState = $this->state;

        if (! $currentState) {
            return true;
        }

        return $currentState->canTransitionTo($newState);
    }

        public function scopeForStudent($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

        public function scopeForAssignment($query, int $assignmentId)
    {
        return $query->where('assignment_id', $assignmentId);
    }

        public function scopeWithStatus($query, SubmissionStatus $status)
    {
        return $query->where('status', $status);
    }

        public function scopeHighestScore($query)
    {
        return $query->orderByDesc('score');
    }

        public function scopeLate($query)
    {
        return $query->where('is_late', true);
    }

        public function scopePendingManualGrading($query)
    {
        return $query->where('status', SubmissionStatus::Submitted)
            ->whereDoesntHave('grade', function ($q) {
                $q->where('is_draft', false);
            });
    }

        public function getFeedbackAttribute()
    {
        if ($this->relationLoaded('grade')) {
            return $this->getRelation('grade')?->feedback;
        }

        return $this->grade?->feedback;
    }

        public function getGradedAtAttribute()
    {
        if ($this->relationLoaded('grade')) {
            return $this->getRelation('grade')?->graded_at;
        }

        return $this->grade?->graded_at;
    }

    public function getDurationAttribute(): ?int
    {
        if (! $this->submitted_at) {
            return null;
        }

        return (int) $this->created_at->diffInSeconds($this->submitted_at);
    }

    public function getFormattedDurationAttribute(): ?string
    {
        if (! $this->submitted_at) {
            return null;
        }

        return $this->created_at->locale(app()->getLocale())->diffForHumans($this->submitted_at, [
            'parts' => 2,
            'short' => false,
            'syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE,
        ]);
    }

        public function getVisibleFeedback(?int $userId = null): ?string
    {
        $reviewModeService = app(\Modules\Learning\Contracts\Services\ReviewModeServiceInterface::class);

        if (! $reviewModeService->canViewFeedback($this, $userId)) {
            return null;
        }

        return $this->feedback;
    }

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

            
            if ($canViewAnswers) {
                $data['content'] = $answer->content;
                $data['selected_options'] = $answer->selected_options;
                $data['file_paths'] = $answer->file_paths;
            }

            
            if ($canViewFeedback) {
                $data['feedback'] = $answer->feedback;
            }

            return $data;
        });
    }

        public function getVisibilityStatus(?int $userId = null): array
    {
        $reviewModeService = app(\Modules\Learning\Contracts\Services\ReviewModeServiceInterface::class);

        return $reviewModeService->getVisibilityStatus($this, $userId);
    }
}
