<?php

declare(strict_types=1);

namespace Modules\Learning\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Learning\Enums\QuestionType;

class Question extends Model
{
    protected $fillable = [
        'assignment_id',
        'type',
        'content',
        'options',
        'answer_key',
        'weight',
        'order',
        'max_score',
        'max_file_size',
        'allowed_file_types',
        'allow_multiple_files',
    ];

    protected $casts = [
        'type' => QuestionType::class,
        'options' => 'array',
        'answer_key' => 'array',
        'weight' => 'decimal:2',
        'max_score' => 'decimal:2',
        'max_file_size' => 'integer',
        'allowed_file_types' => 'array',
        'allow_multiple_files' => 'boolean',
    ];

    /**
     * Get the assignment this question belongs to.
     */
    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

    /**
     * Get all answers for this question.
     */
    public function answers(): HasMany
    {
        return $this->hasMany(Answer::class);
    }

    /**
     * Check if this question can be auto-graded.
     */
    public function canAutoGrade(): bool
    {
        return $this->type->canAutoGrade();
    }

    /**
     * Check if this question requires options.
     */
    public function requiresOptions(): bool
    {
        return $this->type->requiresOptions();
    }

    /**
     * Validate that the question has valid configuration.
     */
    public function isValid(): bool
    {
        // Must have content
        if (empty($this->content)) {
            return false;
        }

        // Must have positive weight
        if ($this->weight <= 0) {
            return false;
        }

        // MCQ and Checkbox must have options
        if ($this->requiresOptions() && empty($this->options)) {
            return false;
        }

        // Auto-gradable questions should have answer key
        if ($this->canAutoGrade() && empty($this->answer_key)) {
            return false;
        }

        return true;
    }

    /**
     * Scope to order by display order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }

    /**
     * Scope to filter by type.
     */
    public function scopeOfType($query, QuestionType $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to filter auto-gradable questions.
     */
    public function scopeAutoGradable($query)
    {
        return $query->whereIn('type', [
            QuestionType::MultipleChoice->value,
            QuestionType::Checkbox->value,
        ]);
    }

    /**
     * Scope to filter manual grading questions.
     */
    public function scopeManualGrading($query)
    {
        return $query->whereIn('type', [
            QuestionType::Essay->value,
            QuestionType::FileUpload->value,
        ]);
    }
}
