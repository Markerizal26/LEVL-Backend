<?php

namespace Modules\Gamification\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class LeaderboardResource extends JsonResource
{
    public function toArray($request)
    {
        // Rank is calculated and passed or attached, we need to handle both
        // If passed as array context or property
        $rank = $this->resource['rank'] ?? $this->rank ?? null;
        $stat = $this->resource['stat'] ?? $this->resource;
        
        // Handling direct UserGamificationStat model or array structure from Service
        // LeaderboardController sends {stat, rank} via map, but here we can standardize.
        // If the resource is the model:
        if ($stat instanceof \Modules\Gamification\Models\UserGamificationStat) {
             return [
                'rank' => $this->additional['rank'] ?? $rank, // Allow passing rank via additional
                'user' => [
                    'id' => $stat->user_id,
                    'name' => $stat->user?->name ?? 'Unknown',
                    'avatar_url' => $stat->user?->avatar_url ?? null,
                ],
                'total_xp' => $stat->total_xp,
                'level' => $stat->global_level,
            ];
        }

        // Fallback for array if service returns array
        return parent::toArray($request);
    }
}
