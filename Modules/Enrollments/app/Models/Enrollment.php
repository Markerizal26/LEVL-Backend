<?php

declare(strict_types=1);

namespace Modules\Enrollments\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;
use Modules\Enrollments\Enums\EnrollmentStatus;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Enrollment extends Model
{
    use HasFactory, LogsActivity, Searchable;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->dontLogIfAttributesChangedOnly(['created_at', 'updated_at'])
            ->setDescriptionForEvent(fn (string $eventName) => match ($eventName) {
                'created' => 'Enrollment baru telah dibuat',
                'updated' => 'Enrollment telah diperbarui',
                'deleted' => 'Enrollment telah dihapus',
                default => "Enrollment {$eventName}",
            });
    }

    protected static function boot()
    {
        parent::boot();
    }

    protected $fillable = [
        'user_id', 'course_id', 'status',
        'enrolled_at', 'completed_at',
    ];

    protected $casts = [
        'status' => EnrollmentStatus::class,
        'enrolled_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    protected $hidden = [
        'course_id',
        'user_id',
    ];

    public function user()
    {
        return $this->belongsTo(\Modules\Auth\Models\User::class);
    }

    public function course()
    {
        return $this->belongsTo(\Modules\Schemes\Models\Course::class);
    }

    public function unitProgress()
    {
        return $this->hasMany(UnitProgress::class);
    }

    public function lessonProgress()
    {
        return $this->hasMany(LessonProgress::class);
    }

    public function courseProgress()
    {
        return $this->hasOne(CourseProgress::class);
    }

    public function toSearchableArray(): array
    {
        $this->loadMissing(['user', 'course']);

        return [
            'id' => (int) $this->id,
            'status' => $this->status?->value,
            'user_id' => (int) $this->user_id,
            'course_id' => (int) $this->course_id,
            'user_name' => $this->user?->name ?? '',
            'user_email' => $this->user?->email ?? '',
            'course_title' => $this->course?->title ?? '',
            'course_code' => $this->course?->code ?? '',
            'enrolled_at' => $this->enrolled_at?->timestamp,
            'completed_at' => $this->completed_at?->timestamp,
            'created_at' => $this->created_at?->timestamp,
        ];
    }

    public function searchableAs(): string
    {
        return 'enrollments_index';
    }

    public function shouldBeSearchable(): bool
    {
        return true;
    }

    protected static function newFactory()
    {
        return \Modules\Enrollments\Database\Factories\EnrollmentFactory::new();
    }
}
