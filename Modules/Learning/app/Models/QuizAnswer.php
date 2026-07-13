<?php

declare(strict_types=1);

namespace Modules\Learning\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuizAnswer extends Model
{
    protected $table = 'quiz_answers';

    protected $fillable = [
        'quiz_submission_id',
        'quiz_question_id',
        'content',
        'selected_options',
        'score',
        'is_auto_graded',
        'is_ai_assisted',
        'ai_suggested_score',
        'ai_reasoning',
        'feedback',
    ];

    protected $casts = [
        'selected_options' => 'array',
        'score' => 'decimal:2',
        'is_auto_graded' => 'integer',
        'is_ai_assisted' => 'boolean',
        'ai_suggested_score' => 'decimal:2',
    ];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(QuizSubmission::class, 'quiz_submission_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(QuizQuestion::class, 'quiz_question_id');
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
            default => $this->content,
        };
    }

    public function scopeGraded($query, bool $isGraded = true)
    {
        if ($isGraded) {
            return $query->whereNotNull('score');
        }

        return $query->whereNull('score');
    }

    public function scopeUngraded($query)
    {
        return $query->whereNull('score');
    }

    public function scopeAutoGraded($query, bool $isAutoGraded = true)
    {
        return $query->where('is_auto_graded', $isAutoGraded);
    }

    public function scopeManuallyGraded($query)
    {
        return $query->where('is_auto_graded', false)->whereNotNull('score');
    }

    public function setIsAiAssistedAttribute($value)
    {
        $this->attributes['is_ai_assisted'] = $value ? '1' : '0';
    }
}
