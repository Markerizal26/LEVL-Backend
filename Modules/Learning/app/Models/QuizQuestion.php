<?php

declare(strict_types=1);

namespace Modules\Learning\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Common\Traits\PgSearchable;
use Modules\Learning\Enums\QuizQuestionType;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class QuizQuestion extends Model implements HasMedia
{
    use InteractsWithMedia, PgSearchable;

    protected array $searchable_columns = [
        'content',
    ];

    protected $table = 'quiz_questions';

    protected $fillable = [
        'quiz_id',
        'type',
        'content',
        'options',
        'answer_key',
        'weight',
        'order',
        'max_score',
    ];

    protected $casts = [
        'type' => QuizQuestionType::class,
        'options' => 'array',
        'answer_key' => 'array',
        'weight' => 'decimal:2',
        'max_score' => 'decimal:2',
    ];

    public function registerMediaCollections(): void
    {
        $disk = config('media-library.disk_name', 'public');
        $this->addMediaCollection('option_images')->useDisk($disk);
        $this->addMediaCollection('question_attachments')->useDisk($disk);
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(QuizAnswer::class, 'quiz_question_id');
    }

    public function canAutoGrade(): bool
    {
        return $this->type->canAutoGrade();
    }

    public function requiresOptions(): bool
    {
        return $this->type->requiresOptions();
    }

    public function isObjective(): bool
    {
        return $this->type->isObjective();
    }

    public function isValid(): bool
    {
        if (empty($this->content)) {
            return false;
        }

        if ($this->weight <= 0) {
            return false;
        }

        if ($this->requiresOptions() && empty($this->options)) {
            return false;
        }

        if ($this->canAutoGrade() && empty($this->answer_key)) {
            return false;
        }

        return true;
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }

    public function scopeOfType($query, QuizQuestionType $type)
    {
        return $query->where('type', $type);
    }

    public function scopeAutoGradable($query, bool $isAutoGradable = true)
    {
        if ($isAutoGradable) {
            return $query->whereIn('type', [
                QuizQuestionType::MultipleChoice->value,
                QuizQuestionType::Checkbox->value,
                QuizQuestionType::TrueFalse->value,
            ]);
        }

        return $query->whereNotIn('type', [
            QuizQuestionType::MultipleChoice->value,
            QuizQuestionType::Checkbox->value,
            QuizQuestionType::TrueFalse->value,
        ]);
    }

    public function scopeEssay($query)
    {
        return $query->where('type', QuizQuestionType::Essay->value);
    }
}
