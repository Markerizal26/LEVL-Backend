<?php

namespace Modules\Gamification\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Auth\Models\User;

class UserScopeStat extends Model
{
    protected $table = 'user_scope_stats';

    protected $fillable = [
        'user_id',
        'scope_type',
        'scope_id',
        'total_xp',
        'current_level',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'scope_id' => 'integer',
        'total_xp' => 'integer',
        'current_level' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
