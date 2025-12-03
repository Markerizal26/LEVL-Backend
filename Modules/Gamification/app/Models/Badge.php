<?php

namespace Modules\Gamification\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Gamification\Enums\BadgeType;

class Badge extends Model
{
    protected $table = 'badges';

    protected $fillable = [
        'code',
        'name',
        'description',
        'icon_path',
        'type',
        'threshold',
    ];

    protected $casts = [
        'threshold' => 'integer',
        'type' => BadgeType::class,
    ];

    public function users(): HasMany
    {
        return $this->hasMany(UserBadge::class);
    }

    public function scopeAchievement($query)
    {
        return $query->where('type', 'achievement');
    }

    public function scopeMilestone($query)
    {
        return $query->where('type', 'milestone');
    }

    public function scopeCompletion($query)
    {
        return $query->where('type', 'completion');
    }
}
