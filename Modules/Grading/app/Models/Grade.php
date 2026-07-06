<?php

declare(strict_types=1);

namespace Modules\Grading\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Common\Traits\PgSearchable;
use Modules\Grading\Enums\GradeStatus;
use Modules\Grading\Enums\SourceType;

class Grade extends Model
{
    use PgSearchable;

    protected array $searchable_columns = [
        'feedback',
    ];

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
        'is_ai_assisted',
        'ai_suggested_score',
        'ai_reasoning',
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
        'is_ai_assisted' => 'boolean',
        'ai_suggested_score' => 'decimal:2',
        'graded_at' => 'datetime',
        'released_at' => 'datetime',
    ];

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

    public function submission(): BelongsTo
    {
        return $this->belongsTo(\Modules\Learning\Models\Submission::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\Modules\Auth\Models\User::class);
    }

    public function grader(): BelongsTo
    {
        return $this->belongsTo(\Modules\Auth\Models\User::class, 'graded_by');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(GradeReview::class);
    }

    public function override(float $newScore, string $reason, int $graderId): bool
    {
        if (empty($reason)) {
            throw new \InvalidArgumentException('Override reason is required');
        }

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

    public function release(): bool
    {
        if ($this->is_draft) {
            throw new \InvalidArgumentException('Cannot release a draft grade');
        }

        $this->released_at = now();

        return $this->save();
    }

    public function isReleased(): bool
    {
        return $this->released_at !== null;
    }

    public function getEffectiveScoreAttribute(): float
    {
        return (float) $this->score;

    }

    public function scopeDraft($query, bool $isDraft = true)
    {
        return $query->where('is_draft', $isDraft);
    }

    public function scopeFinalized($query)
    {
        return $query->where('is_draft', false);
    }

    public function scopeReleased($query)
    {
        return $query->whereNotNull('released_at');
    }

    public function scopeUnreleased($query)
    {
        return $query->whereNull('released_at');
    }

    public function scopeOverridden($query)
    {
        return $query->where('is_override', true);
    }

    public function scopeForSubmission($query, int $submissionId)
    {
        return $query->where('submission_id', $submissionId);
    }

    public function setIsAiAssistedAttribute($value)
    {
        $this->attributes['is_ai_assisted'] = $value ? 'true' : 'false';
    }
}
