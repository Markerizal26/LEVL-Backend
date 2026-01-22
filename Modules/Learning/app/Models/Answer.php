<?php

declare(strict_types=1);

namespace Modules\Learning\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Answer extends Model
{
    protected $fillable = [
        'submission_id',
        'question_id',
        'content',
        'selected_options',
        'file_paths',
        'files_expired_at',
        'file_metadata',
        'score',
        'is_auto_graded',
        'feedback',
    ];

    protected $casts = [
        'selected_options' => 'array',
        'file_paths' => 'array',
        'files_expired_at' => 'datetime',
        'file_metadata' => 'array',
        'score' => 'decimal:2',
        'is_auto_graded' => 'boolean',
    ];

    /**
     * Get the submission this answer belongs to.
     */
    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    /**
     * Get the question this answer is for.
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    /**
     * Check if this answer has been graded.
     */
    public function isGraded(): bool
    {
        return $this->score !== null;
    }

    /**
     * Check if this answer needs manual grading.
     */
    public function needsManualGrading(): bool
    {
        return ! $this->isGraded() && ! $this->question->canAutoGrade();
    }

    /**
     * Get the answer value based on question type.
     */
    public function getAnswerValueAttribute(): mixed
    {
        $question = $this->question;

        if (! $question) {
            return null;
        }

        return match (true) {
            $question->type->requiresOptions() => $this->selected_options,
            $question->type === \Modules\Learning\Enums\QuestionType::FileUpload => $this->file_paths,
            default => $this->content,
        };
    }

    /**
     * Check if feedback contains HTML (rich text).
     */
    public function hasRichTextFeedback(): bool
    {
        if (empty($this->feedback)) {
            return false;
        }

        // Check if feedback contains HTML tags
        return $this->feedback !== strip_tags($this->feedback);
    }

    /**
     * Get feedback as plain text (strips HTML).
     */
    public function getPlainTextFeedbackAttribute(): ?string
    {
        if (empty($this->feedback)) {
            return null;
        }

        return strip_tags($this->feedback);
    }

    /**
     * Scope to filter graded answers.
     */
    public function scopeGraded($query)
    {
        return $query->whereNotNull('score');
    }

    /**
     * Scope to filter ungraded answers.
     */
    public function scopeUngraded($query)
    {
        return $query->whereNull('score');
    }

    /**
     * Scope to filter auto-graded answers.
     */
    public function scopeAutoGraded($query)
    {
        return $query->where('is_auto_graded', true);
    }

    /**
     * Scope to filter manually graded answers.
     */
    public function scopeManuallyGraded($query)
    {
        return $query->where('is_auto_graded', false)->whereNotNull('score');
    }

    /**
     * Scope to filter answers with feedback.
     */
    public function scopeWithFeedback($query)
    {
        return $query->whereNotNull('feedback')->where('feedback', '!=', '');
    }

    /**
     * Scope to filter answers with files.
     */
    public function scopeWithFiles(Builder $query): Builder
    {
        return $query->whereNotNull('file_paths')
            ->whereRaw('JSON_LENGTH(file_paths) > 0');
    }

    /**
     * Scope to filter answers with expired files.
     */
    public function scopeWithExpiredFiles(Builder $query): Builder
    {
        return $query->whereNotNull('files_expired_at');
    }

    /**
     * Scope to filter answers with non-expired files.
     */
    public function scopeWithActiveFiles(Builder $query): Builder
    {
        return $query->withFiles()->whereNull('files_expired_at');
    }

    /**
     * Scope to filter answers eligible for file expiration.
     * Files are eligible if they exist, haven't been expired yet,
     * and were created before the retention period.
     */
    public function scopeEligibleForExpiration(Builder $query, ?int $retentionDays = null): Builder
    {
        $retentionDays = $retentionDays ?? config('grading.file_retention.retention_days', 365);

        if ($retentionDays === null) {
            // Unlimited retention - no files are eligible
            return $query->whereRaw('1 = 0');
        }

        $expirationDate = Carbon::now()->subDays($retentionDays);

        return $query->withActiveFiles()
            ->where('created_at', '<', $expirationDate);
    }

    /**
     * Check if this answer has files.
     */
    public function hasFiles(): bool
    {
        return ! empty($this->file_paths) && is_array($this->file_paths) && count($this->file_paths) > 0;
    }

    /**
     * Check if files have expired.
     */
    public function filesExpired(): bool
    {
        return $this->files_expired_at !== null;
    }

    /**
     * Check if files are eligible for expiration based on retention period.
     */
    public function isEligibleForExpiration(?int $retentionDays = null): bool
    {
        if (! $this->hasFiles() || $this->filesExpired()) {
            return false;
        }

        $retentionDays = $retentionDays ?? config('grading.file_retention.retention_days', 365);

        if ($retentionDays === null) {
            return false; // Unlimited retention
        }

        $expirationDate = Carbon::now()->subDays($retentionDays);

        return $this->created_at->lt($expirationDate);
    }

    /**
     * Get the file retention period in days from configuration.
     */
    public static function getRetentionPeriodDays(): ?int
    {
        return config('grading.file_retention.retention_days', 365);
    }

    /**
     * Mark files as expired and preserve metadata.
     *
     * @param  array<string, mixed>|null  $metadata  Optional metadata to store
     */
    public function markFilesExpired(?array $metadata = null): bool
    {
        if (! $this->hasFiles()) {
            return false;
        }

        $this->files_expired_at = Carbon::now();
        $this->file_metadata = $metadata ?? $this->file_metadata;

        return $this->save();
    }

    /**
     * Store file metadata for preservation after deletion.
     *
     * @param  array<array{name: string, size: int, type: string, uploaded_at: string}>  $metadata
     */
    public function storeFileMetadata(array $metadata): bool
    {
        $this->file_metadata = $metadata;

        return $this->save();
    }

    /**
     * Get preserved file metadata.
     *
     * @return array<array{name: string, size: int, type: string, uploaded_at: string}>|null
     */
    public function getFileMetadata(): ?array
    {
        return $this->file_metadata;
    }

    /**
     * Clear file paths after physical deletion (preserves metadata).
     */
    public function clearFilePaths(): bool
    {
        $this->file_paths = [];

        return $this->save();
    }

    /**
     * Mark expired files for a collection of answers.
     * Returns the number of answers marked as expired.
     */
    public static function markExpiredFiles(?int $retentionDays = null, ?int $limit = null): int
    {
        $limit = $limit ?? config('grading.file_retention.cleanup_batch_size', 100);

        $query = static::eligibleForExpiration($retentionDays);

        if ($limit !== null) {
            $query->limit($limit);
        }

        $answers = $query->get();
        $count = 0;

        foreach ($answers as $answer) {
            if ($answer->markFilesExpired()) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Get all answers with files that have been marked as expired.
     *
     * @return Collection<int, Answer>
     */
    public static function getExpiredFileAnswers(?int $limit = null): Collection
    {
        $query = static::withExpiredFiles()
            ->withFiles();

        if ($limit !== null) {
            $query->limit($limit);
        }

        return $query->get();
    }
}
