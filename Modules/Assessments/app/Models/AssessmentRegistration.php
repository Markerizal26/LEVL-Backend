<?php

namespace Modules\Assessments\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Assessments\Enums\PaymentStatus;
use Modules\Assessments\Enums\RegistrationStatus;
use Modules\Auth\Models\User;
use Modules\Enrollments\Models\Enrollment;

class AssessmentRegistration extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'exercise_id',
        'enrollment_id',
        'scheduled_at',
        'status',
        'payment_status',
        'payment_amount',
        'payment_method',
        'payment_reference',
        'prerequisites_met',
        'prerequisites_checked_at',
        'confirmation_sent_at',
        'reminder_sent_at',
        'completed_at',
        'result',
        'score',
        'notes',
    ];

    protected $casts = [
        'status' => RegistrationStatus::class,
        'payment_status' => PaymentStatus::class,
        'payment_method' => PaymentMethod::class,
        'result' => RegistrationResult::class,
        'scheduled_at' => 'datetime',
        'prerequisites_met' => 'boolean',
        'prerequisites_checked_at' => 'datetime',
        'confirmation_sent_at' => 'datetime',
        'reminder_sent_at' => 'datetime',
        'completed_at' => 'datetime',
        'payment_amount' => 'decimal:2',
        'score' => 'decimal:2',
    ];

    /**
     * Get the user who registered for the assessment.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the exercise (assessment) for this registration.
     */
    public function exercise(): BelongsTo
    {
        return $this->belongsTo(Exercise::class);
    }

    /**
     * Get the enrollment associated with this registration.
     */
    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Modules\Assessments\Database\Factories\AssessmentRegistrationFactory::new();
    }
}
