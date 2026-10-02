<?php

declare(strict_types=1);

namespace Modules\Learning\Models;

use App\Models\Concerns\TracksTrashBin;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Common\Traits\PgSearchable;
use Modules\Learning\Enums\AssignmentStatus;
use Modules\Learning\Enums\AssignmentType;
use Modules\Learning\Enums\ReviewMode;
use Modules\Learning\Enums\SubmissionType;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Assignment extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, \Modules\Common\Traits\PublishedOnlyScope, PgSearchable, SoftDeletes, TracksTrashBin;

    protected array $searchable_columns = [
        'title',
        'description',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('attachments')
            ->useDisk(config('media-library.disk_name', 'public'))
            ->acceptsFile(static fn (): bool => true);
    }

    protected $fillable = [
        'unit_id',
        'order',
        'created_by',
        'title',
        'description',
        'type',
        'submission_type',
        'max_score',
        'passing_grade',
        'review_mode',
        'status',
    ];

    protected $casts = [
        'type' => AssignmentType::class,
        'submission_type' => SubmissionType::class,
        'status' => AssignmentStatus::class,
        'review_mode' => ReviewMode::class,
        'passing_grade' => 'decimal:2',
        'order' => 'integer',
    ];

    protected static function newFactory()
    {
        return \Modules\Learning\Database\Factories\AssignmentFactory::new();
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(\Modules\Schemes\Models\Unit::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\Modules\Auth\Models\User::class, 'created_by');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }

    public function isAvailable(): bool
    {
        return $this->status === AssignmentStatus::Published;
    }

    public function getScopeTypeAttribute(): string
    {
        return 'unit';
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

    public function scopePublished($query, bool $isPublished = true)
    {
        if ($isPublished) {
            return $query->where('status', AssignmentStatus::Published);
        }

        return $query->where('status', '!=', AssignmentStatus::Published);
    }

    public function scopeAvailable($query, bool $isAvailable = true)
    {
        if ($isAvailable) {
            return $query->published();
        }

        return $query->where('status', '!=', AssignmentStatus::Published);
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

    
    public function isAssignment(): bool
    {
        return $this->type === AssignmentType::Assignment;
    }

    
    public function isQuiz(): bool
    {
        return $this->type === AssignmentType::Quiz;
    }

    
    public function scopeOfType($query, AssignmentType $type)
    {
        return $query->where('type', $type);
    }

    
    public function scopeAssignments($query)
    {
        return $query->where('type', AssignmentType::Assignment);
    }

    
    public function scopeQuizzes($query)
    {
        return $query->where('type', AssignmentType::Quiz);
    }
}
