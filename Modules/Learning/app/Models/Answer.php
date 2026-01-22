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
        'score',
        'is_auto_graded',
        'feedback',
    ];

    protected $casts = [
        'selected_options' => 'array',
        'file_paths' => 'array',
        'score' => 'decimal:2',
        'is_auto_graded' => 'boolean',
    ];

        public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

        public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

        public function isGraded(): bool
    {
        return $this->score !== null;
    }

        public function needsManualGrading(): bool
    {
        return ! $this->isGraded() && ! $this->question->canAutoGrade();
    }

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

        public function hasRichTextFeedback(): bool
    {
        if (empty($this->feedback)) {
            return false;
        }

        
        return $this->feedback !== strip_tags($this->feedback);
    }

        public function getPlainTextFeedbackAttribute(): ?string
    {
        if (empty($this->feedback)) {
            return null;
        }

        return strip_tags($this->feedback);
    }

        public function scopeGraded($query)
    {
        return $query->whereNotNull('score');
    }

        public function scopeUngraded($query)
    {
        return $query->whereNull('score');
    }

        public function scopeAutoGraded($query)
    {
        return $query->where('is_auto_graded', true);
    }

        public function scopeManuallyGraded($query)
    {
        return $query->where('is_auto_graded', false)->whereNotNull('score');
    }

        public function scopeWithFeedback($query)
    {
        return $query->whereNotNull('feedback')->where('feedback', '!=', '');
    }

        public function scopeWithFiles(Builder $query): Builder
    {
        return $query->whereNotNull('file_paths')
            ->whereRaw('JSON_LENGTH(file_paths) > 0');
    }

        public function hasFiles(): bool
    {
        return ! empty($this->file_paths) && is_array($this->file_paths) && count($this->file_paths) > 0;
    }
}
