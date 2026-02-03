<?php

declare(strict_types=1);

namespace Modules\Common\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Auth;
use Laravel\Scout\Searchable;

/**
 * AuditLog Model - Immutable (append-only) audit log for compliance.
 *
 * This model is designed to be immutable - once created, records cannot be
 * updated or deleted. This ensures audit trail integrity for compliance
 * and dispute resolution.
 *
 * @property int $id
 * @property string|null $event Legacy event field
 * @property string|null $action Action performed (e.g., 'submission_created', 'grade_override')
 * @property string|null $target_type Legacy target type (polymorphic)
 * @property int|null $target_id Legacy target ID (polymorphic)
 * @property string|null $actor_type Actor type (e.g., 'Modules\Auth\Models\User')
 * @property int|null $actor_id Actor ID
 * @property string|null $subject_type Subject type (polymorphic - the entity being acted upon)
 * @property int|null $subject_id Subject ID (polymorphic)
 * @property int|null $user_id User who performed the action
 * @property array|null $properties Legacy properties field
 * @property array|null $context Additional context data (JSON)
 * @property \Carbon\Carbon|null $logged_at Legacy timestamp
 * @property \Carbon\Carbon $created_at Creation timestamp
 */
class AuditLog extends Model
{
    use HasFactory, Searchable;

    /**
     * The table associated with the model.
     */
    protected $table = 'audit_logs';

    /**
     * Disable updated_at timestamp since this is an append-only model.
     */
    public const UPDATED_AT = null;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'event',
        'action',
        'target_type',
        'target_id',
        'actor_type',
        'actor_id',
        'subject_type',
        'subject_id',
        'user_id',
        'properties',
        'context',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'action' => 'string',
        'actor_id' => 'integer',
        'actor_type' => 'string',
        'subject_id' => 'integer',
        'subject_type' => 'string',
        'context' => 'array',
        'properties' => 'array',
        'logged_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    /**
     * Boot the model and register event listeners to enforce immutability.
     *
     * This ensures the audit log is append-only by preventing updates and deletes.
     * Requirement 20.6: THE Audit_Log SHALL be immutable (append-only)
     */
    protected static function boot(): void
    {
        parent::boot();

        // Prevent updates - audit logs are immutable
        static::updating(function () {
            return false;
        });

        // Prevent deletes - audit logs are immutable
        static::deleting(function () {
            return false;
        });
    }

    /**
     * Scope for filtering by multiple actions.
     */
    public function scopeActionIn($query, array $actions)
    {
        return $query->whereIn('action', $actions);
    }

    /**
     * Scope for date range filtering.
     */
    public function scopeCreatedBetween($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [
            \Carbon\Carbon::parse($startDate)->startOfDay(), 
            \Carbon\Carbon::parse($endDate)->endOfDay()
        ]);
    }

    /**
     * Scope for searching in context JSON.
     */
    public function scopeContextContains($query, $search)
    {
        // Simple text search in JSON, strictly depends on DB capability. 
        // For Postgres: cast to text or use ->> operator if keys known.
        // Assuming Postgres here for generic search:
        return $query->whereRaw("context::text ILIKE ?", ["%{$search}%"]);
    }

    /**
     * Scope for assignment_id in context.
     */
    public function scopeAssignmentId($query, $id)
    {
        return $query->whereRaw("context->>'assignment_id' = ?", [(string)$id]);
    }

    /**
     * Scope for student_id in context.
     */
    public function scopeStudentId($query, $id)
    {
        return $query->whereRaw("context->>'student_id' = ?", [(string)$id]);
    }


    /**
     * Get the target model (legacy polymorphic relationship).
     */
    public function target(): MorphTo
    {
        return $this->morphTo('target');
    }

    /**
     * Get the actor model (polymorphic relationship).
     */
    public function actor(): MorphTo
    {
        return $this->morphTo('actor');
    }

    /**
     * Get the subject model (polymorphic relationship).
     */
    public function subject(): MorphTo
    {
        return $this->morphTo('subject');
    }

    /**
     * Get the user who performed the action.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\Modules\Auth\Models\User::class);
    }

    /**
     * Scope a query to only include logs for a specific event.
     */
    public function scopeForEvent($query, string $event)
    {
        return $query->where('event', $event);
    }

    /**
     * Scope a query to only include logs for a specific action.
     */
    public function scopeForAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope a query to only include logs for a specific target (legacy).
     */
    public function scopeForTarget($query, $model)
    {
        return $query->where('target_type', get_class($model))
            ->where('target_id', $model->getKey());
    }

    /**
     * Scope a query to only include logs for a specific subject.
     */
    public function scopeForSubject($query, $model)
    {
        return $query->where('subject_type', get_class($model))
            ->where('subject_id', $model->getKey());
    }

    /**
     * Scope a query to only include logs for a specific actor.
     */
    public function scopeForActor($query, $model)
    {
        return $query->where('actor_type', get_class($model))
            ->where('actor_id', $model->getKey());
    }

    /**
     * Scope a query to only include logs for a specific user.
     */
    public function scopeForUser($query, $user)
    {
        $userId = is_object($user) ? $user->id : $user;

        return $query->where('user_id', $userId);
    }

    /**
     * Scope a query to only include logs within a date range.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope a query to only include logs within a date range (legacy).
     */
    public function scopeDateRangeLegacy($query, $startDate, $endDate)
    {
        return $query->whereBetween('logged_at', [$startDate, $endDate]);
    }

    /**
     * Log an event (legacy method for backward compatibility).
     */
    public static function log(
        string $event,
        $target = null,
        $actor = null,
        array $properties = []
    ): self {
        return static::create([
            'event' => $event,
            'target_type' => $target ? get_class($target) : null,
            'target_id' => $target ? $target->getKey() : null,
            'actor_type' => $actor ? get_class($actor) : null,
            'actor_id' => $actor ? $actor->getKey() : null,
            'user_id' => Auth::check() ? Auth::id() : null,
            'properties' => $properties,
            'logged_at' => now(),
        ]);
    }

    /**
     * Log an action with the new schema.
     *
     * @param  string  $action  The action being logged (e.g., 'submission_created', 'grade_override')
     * @param  Model|null  $subject  The entity being acted upon
     * @param  Model|null  $actor  The entity performing the action
     * @param  array  $context  Additional context data
     */
    public static function logAction(
        string $action,
        $subject = null,
        $actor = null,
        array $context = []
    ): self {
        return static::create([
            'action' => $action,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id' => $subject ? $subject->getKey() : null,
            'actor_type' => $actor ? get_class($actor) : null,
            'actor_id' => $actor ? $actor->getKey() : null,
            'user_id' => Auth::check() ? Auth::id() : null,
            'context' => $context,
        ]);
    }

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'action' => $this->action,
            'actor_type' => $this->actor_type,
            'subject_type' => $this->subject_type,
            'context' => $this->context,
            'event' => $this->event, // Legacy
        ];
    }
}
