<?php

declare(strict_types=1);

namespace Modules\Learning\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Learning\Enums\OverrideType;

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

        public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

        public function student(): BelongsTo
    {
        return $this->belongsTo(\Modules\Auth\Models\User::class, 'student_id');
    }

        public function grantor(): BelongsTo
    {
        return $this->belongsTo(\Modules\Auth\Models\User::class, 'grantor_id');
    }

        public function isActive(): bool
    {
        if ($this->expires_at === null) {
            return true;
        }

        return now()->lt($this->expires_at);
    }

        public function isExpired(): bool
    {
        return ! $this->isActive();
    }

        public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
                ->orWhere('expires_at', '>', now());
        });
    }

        public function scopeOfType($query, OverrideType $type)
    {
        return $query->where('type', $type);
    }

        public function scopeForStudent($query, int $studentId)
    {
        return $query->where('student_id', $studentId);
    }

        public function scopeForAssignment($query, int $assignmentId)
    {
        return $query->where('assignment_id', $assignmentId);
    }

        public function getExtendedDeadline(): ?\Carbon\Carbon
    {
        if ($this->type !== OverrideType::Deadline) {
            return null;
        }

        $deadline = $this->value['extended_deadline'] ?? null;

        return $deadline ? \Carbon\Carbon::parse($deadline) : null;
    }

        public function getAdditionalAttempts(): ?int
    {
        if ($this->type !== OverrideType::Attempts) {
            return null;
        }

        return $this->value['additional_attempts'] ?? null;
    }

        public function getBypassedPrerequisites(): ?array
    {
        if ($this->type !== OverrideType::Prerequisite) {
            return null;
        }

        return $this->value['bypassed_prerequisites'] ?? [];
    }
}
