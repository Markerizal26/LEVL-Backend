<?php

namespace Modules\Common\Models;

use Illuminate\Database\Eloquent\Model;

class Audit extends Model
{
    protected $fillable = [
        'action',
        'actor_type',
        'actor_id',
        'user_id',
        'target_table',
        'target_type',
        'target_id',
        'module',
        'context',
        'ip_address',
        'user_agent',
        'meta',
        'properties',
        'logged_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'properties' => 'array',
        'logged_at' => 'datetime',
    ];

    /**
     * Get the actor that performed the action.
     */
    public function actor()
    {
        return $this->morphTo('actor');
    }

    /**
     * Get the user that performed the action (if actor is a user).
     */
    public function user()
    {
        return $this->belongsTo(\Modules\Auth\Models\User::class);
    }

    /**
     * Get the target of the action.
     */
    public function target()
    {
        return $this->morphTo('target');
    }

    /**
     * Scope for system audits.
     */
    public function scopeSystem($query)
    {
        return $query->where('context', 'system');
    }

    /**
     * Scope for application audits.
     */
    public function scopeApplication($query)
    {
        return $query->where('context', 'application');
    }

    /**
     * Scope for specific module.
     */
    public function scopeModule($query, string $module)
    {
        return $query->where('module', $module);
    }

    /**
     * Scope for specific action.
     */
    public function scopeAction($query, string $action)
    {
        return $query->where('action', $action);
    }
}

