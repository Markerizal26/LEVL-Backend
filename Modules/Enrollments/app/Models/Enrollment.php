<?php

declare(strict_types=1);

namespace Modules\Enrollments\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Common\Traits\PgSearchable;
use Modules\Enrollments\Enums\EnrollmentStatus;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Enrollment extends Model
{
    use HasFactory, LogsActivity, PgSearchable;

    protected array $searchable_columns = [
        // No local text columns suitable for fuzzy search.
        // Add columns here if needed.
    ];

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

    protected static function newFactory()
    {
        return \Modules\Enrollments\Database\Factories\EnrollmentFactory::new();
    }
}
