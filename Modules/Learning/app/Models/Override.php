<?php

declare(strict_types=1);

namespace Modules\Learning\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Learning\Enums\OverrideType;

/**
 * Override model for instructor overrides of system restrictions.
 *
 * Allows instructors to override:
 * - Prerequisite requirements (24.1)
 * - Attempt limits (24.2)
 * - Deadlines (24.3)
 *
 * All overrides require a reason (24.4).
 *
 * @property int $id
 * @property int $assignment_id
 * @property int $student_id
 * @property int $grantor_id
 * @property OverrideType $type
 * @property string $reason
 * @property array<string, mixed> $value
 * @property \Carbon\Carbon|null $granted_at
 * @property \Carbon\Carbon|null $expires_at
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class Override extends Model
{
    protected $fillable = [
        'assignment_id',
        'student_id',
        'grantor_id',
        'type',
        'reason',
        'value',
        'granted_at',
        'expires_at',
    ];

    protected $casts = [
        'type' => OverrideType::class,
        'value' => 'array',
        'granted_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * Get the assignment this override applies to.
     */
    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

    /**
     * Get the student who received this override.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(\Modules\Auth\Models\User::class, 'student_id');
    }

    /**
     * Get the instructor who granted this override.
     */
    public function grantor(): BelongsTo
    {
        return $this->belongsTo(\Modules\Auth\Models\User::class, 'grantor_id');
    }

    /**
     * Check if this override is currently active (not expired).
     */
    public function isActive(): bool
    {
        if ($this->expires_at === null) {
            return true;
        }

        return now()->lt($this->expires_at);
    }

    /**
     * Check if this override has expired.
     */
    public function isExpired(): bool
    {
        return ! $this->isActive();
    }

    /**
     * Scope to filter active (non-expired) overrides.
     */
    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
                ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * Scope to filter by override type.
     */
    public function scopeOfType($query, OverrideType $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to filter by student.
     */
    public function scopeForStudent($query, int $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    /**
     * Scope to filter by assignment.
     */
    public function scopeForAssignment($query, int $assignmentId)
    {
        return $query->where('assignment_id', $assignmentId);
    }

    /**
     * Get the extended deadline value for deadline overrides.
     * Returns null if this is not a deadline override.
     */
    public function getExtendedDeadline(): ?\Carbon\Carbon
    {
        if ($this->type !== OverrideType::Deadline) {
            return null;
        }

        $deadline = $this->value['extended_deadline'] ?? null;

        return $deadline ? \Carbon\Carbon::parse($deadline) : null;
    }

    /**
     * Get the additional attempts value for attempts overrides.
     * Returns null if this is not an attempts override.
     */
    public function getAdditionalAttempts(): ?int
    {
        if ($this->type !== OverrideType::Attempts) {
            return null;
        }

        return $this->value['additional_attempts'] ?? null;
    }

    /**
     * Get the bypassed prerequisite IDs for prerequisite overrides.
     * Returns null if this is not a prerequisite override.
     */
    public function getBypassedPrerequisites(): ?array
    {
        if ($this->type !== OverrideType::Prerequisite) {
            return null;
        }

        return $this->value['bypassed_prerequisites'] ?? [];
    }
}
