<?php

declare(strict_types=1);

namespace Modules\Learning\Models;

use App\Models\Concerns\TracksTrashBin;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Common\Traits\PgSearchable;
use Modules\Learning\Enums\QuizStatus;
use Modules\Learning\Enums\QuizQuestionType;
use Modules\Learning\Enums\RandomizationType;
use Modules\Learning\Enums\ReviewMode;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Quiz extends Model implements HasMedia
{
    use InteractsWithMedia, \Modules\Common\Traits\PublishedOnlyScope, PgSearchable, SoftDeletes, TracksTrashBin;

    protected array $searchable_columns = [
        'title',
        'description',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('attachments')
            ->useDisk(config('media-library.disk_name', 'public'))
            ->acceptsMimeTypes([
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'image/jpeg',
                'image/png',
                'image/webp',
            ]);
    }

    protected $fillable = [
        'unit_id',
        'order',
        'created_by',
        'title',
        'description',
        'passing_grade',
        'auto_grading',
        'max_score',
        'time_limit_minutes',
        'randomization_type',
        'question_bank_count',
        'review_mode',
        'status',
    ];

    protected $casts = [
        'status' => QuizStatus::class,
        'review_mode' => ReviewMode::class,
        'randomization_type' => RandomizationType::class,
        'passing_grade' => 'decimal:2',
        'max_score' => 'decimal:2',
        'time_limit_minutes' => 'integer',
        'question_bank_count' => 'integer',
        'order' => 'integer',
        'auto_grading' => 'boolean',
    ];

    public function setAutoGradingAttribute($value)
    {
        $this->attributes['auto_grading'] = filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false';
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(\Modules\Schemes\Models\Unit::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\Modules\Auth\Models\User::class, 'created_by');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(QuizQuestion::class)->orderBy('order');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(QuizSubmission::class);
    }

    public function isAvailable(): bool
    {
        return $this->status === QuizStatus::Published;
    }

    public function hasOnlyObjectiveQuestions(): bool
    {
        return $this->questions()->where('type', QuizQuestionType::Essay->value)->doesntExist();
    }

    public function hasEssayQuestions(): bool
    {
        return $this->questions()->where('type', QuizQuestionType::Essay->value)->exists();
    }

    public function hasOnlyEssayQuestions(): bool
    {
        return $this->questions()->whereNotIn('type', [QuizQuestionType::Essay->value])->doesntExist();
    }

    public function hasObjectiveAndEssay(): bool
    {
        return $this->hasEssayQuestions() && ! $this->hasOnlyEssayQuestions();
    }

    public function getScopeTypeAttribute(): string
    {
        return 'unit';
    }

    public function unitContent(): \Illuminate\Database\Eloquent\Relations\MorphOne
    {
        return $this->morphOne(\Modules\Schemes\Models\UnitContent::class, 'contentable');
    }

    public function getCourseId(): ?int
    {
        $this->loadMissing('unit');

        return $this->unit?->course_id;
    }

    public function getCourse(): ?\Modules\Schemes\Models\Course
    {
        $this->loadMissing('unit.course');

        return $this->unit?->course;
    }

    public function scopePublished($query, bool $isPublished = true)
    {
        if ($isPublished) {
            return $query->where('status', QuizStatus::Published);
        }

        return $query->where('status', '!=', QuizStatus::Published);
    }

    public function scopeAvailable($query, bool $isAvailable = true)
    {
        if ($isAvailable) {
            return $query->published();
        }

        return $query->where('status', '!=', QuizStatus::Published);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }

    public function scopeForUnit($query, int $unitId)
    {
        return $query->where('unit_id', $unitId);
    }

    public function scopeForCourse($query, int $courseId)
    {
        return $query->whereHas('unit', function ($q) use ($courseId) {
            $q->where('course_id', $courseId);
        });
    }
}
