<?php

declare(strict_types=1);

namespace Modules\Grading\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Grading\Enums\AppealStatus;

/**
 * Appeal model for late submission appeals.
 *
 * @property int $id
 * @property int $submission_id
 * @property int $student_id
 * @property int|null $reviewer_id
 * @property string $reason
 * @property array|null $supporting_documents
 * @property AppealStatus $status
 * @property string|null $decision_reason
 * @property \Illuminate\Support\Carbon|null $submitted_at
 * @property \Illuminate\Support\Carbon|null $decided_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Modules\Learning\Models\Submission $submission
 * @property-read \Modules\Auth\Models\User $student
 * @property-read \Modules\Auth\Models\User|null $reviewer
 */
class Appeal extends Model
{
    protected $fillable = [
        'submission_id',
        'student_id',
        'reviewer_id',
        'reason',
        'supporting_documents',
        'status',
        'decision_reason',
        'submitted_at',
        'decided_at',
    ];

    protected $casts = [
        'reason' => 'string',
        'supporting_documents' => 'array',
        'status' => AppealStatus::class,
        'decision_reason' => 'string',
        'submitted_at' => 'datetime',
        'decided_at' => 'datetime',
    ];

    /**
     * Get the submission for this appeal.
     */
    public function submission(): BelongsTo
    {
        return $this->belongsTo(\Modules\Learning\Models\Submission::class);
    }

    /**
     * Get the student who submitted this appeal.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(\Modules\Auth\Models\User::class, 'student_id');
    }

    /**
     * Get the reviewer who decided on this appeal.
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(\Modules\Auth\Models\User::class, 'reviewer_id');
    }

    /**
     * Approve the appeal.
     *
     * @throws \InvalidArgumentException if appeal is already decided
     */
    public function approve(int $reviewerId): bool
    {
        if ($this->status->isDecided()) {
            throw new \InvalidArgumentException('Appeal has already been decided');
        }

        $this->status = AppealStatus::Approved;
        $this->reviewer_id = $reviewerId;
        $this->decided_at = now();

        return $this->save();
    }

    /**
     * Deny the appeal with a reason.
     *
     * @throws \InvalidArgumentException if appeal is already decided or reason is empty
     */
    public function deny(int $reviewerId, string $reason): bool
    {
        if ($this->status->isDecided()) {
            throw new \InvalidArgumentException('Appeal has already been decided');
        }

        if (empty($reason)) {
            throw new \InvalidArgumentException('Decision reason is required when denying an appeal');
        }

        $this->status = AppealStatus::Denied;
        $this->reviewer_id = $reviewerId;
        $this->decision_reason = $reason;
        $this->decided_at = now();

        return $this->save();
    }

    /**
     * Check if the appeal is pending.
     */
    public function isPending(): bool
    {
        return $this->status->isPending();
    }

    /**
     * Check if the appeal has been decided.
     */
    public function isDecided(): bool
    {
        return $this->status->isDecided();
    }

    /**
     * Check if the appeal was approved.
     */
    public function isApproved(): bool
    {
        return $this->status->isApproved();
    }

    /**
     * Check if the appeal was denied.
     */
    public function isDenied(): bool
    {
        return $this->status->isDenied();
    }

    /**
     * Scope to filter pending appeals.
     */
    public function scopePending($query)
    {
        return $query->where('status', AppealStatus::Pending);
    }

    /**
     * Scope to filter approved appeals.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', AppealStatus::Approved);
    }

    /**
     * Scope to filter denied appeals.
     */
    public function scopeDenied($query)
    {
        return $query->where('status', AppealStatus::Denied);
    }

    /**
     * Scope to filter by submission.
     */
    public function scopeForSubmission($query, int $submissionId)
    {
        return $query->where('submission_id', $submissionId);
    }

    /**
     * Scope to filter by student.
     */
    public function scopeForStudent($query, int $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    /**
     * Scope to filter by reviewer.
     */
    public function scopeReviewedBy($query, int $reviewerId)
    {
        return $query->where('reviewer_id', $reviewerId);
    }
}
