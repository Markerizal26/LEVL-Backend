<?php

namespace Modules\Notifications\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $fillable = [
        'type', 'title', 'message', 'channel',
        'priority', 'is_broadcast', 'scheduled_at', 'sent_at',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
        'is_broadcast' => 'boolean',
    ];

    public function users()
    {
        return $this->belongsToMany(\Modules\Auth\Models\User::class, 'user_notifications')
            ->withPivot(['status', 'read_at'])
            ->withTimestamps();
    }
}
