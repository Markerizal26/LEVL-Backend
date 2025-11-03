<?php

namespace Modules\Operations\Models;

use Illuminate\Database\Eloquent\Model;

class SystemAudit extends Model
{
    protected $fillable = [
        'action', 'user_id', 'module', 'target_table', 'target_id',
        'ip_address', 'user_agent', 'meta', 'logged_at'
    ];

    protected $casts = [
        'meta' => 'array',
        'logged_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(\Modules\Auth\Models\User::class);
    }
}
