<?php

declare(strict_types=1);

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

    public function actor()
    {
        return $this->morphTo('actor');
    }

    public function user()
    {
        return $this->belongsTo(\Modules\Auth\Models\User::class);
    }

    public function target()
    {
        return $this->morphTo('target');
    }

    public function scopeSystem($query)
    {
        return $query->where('context', 'system');
    }

    public function scopeApplication($query)
    {
        return $query->where('context', 'application');
    }

    public function scopeModule($query, string $module)
    {
        return $query->where('module', $module);
    }

    public function scopeAction($query, string $action)
    {
        return $query->where('action', $action);
    }
}

