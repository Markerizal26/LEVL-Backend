<?php

namespace Modules\Gamification\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class LeaderboardResource extends JsonResource
{
    public function toArray($request)
    {
        
        
        $rank = $this->resource['rank'] ?? $this->rank ?? null;
        $stat = $this->resource['stat'] ?? $this->resource;
        
        
        
        
        if ($stat instanceof \Modules\Gamification\Models\UserGamificationStat) {
             return [
                'rank' => $this->additional['rank'] ?? $rank, 
                'user' => [
                    'id' => $stat->user_id,
                    'name' => $stat->user?->name ?? 'Unknown',
                    'avatar_url' => $stat->user?->avatar_url ?? null,
                ],
                'total_xp' => $stat->total_xp,
                'level' => $stat->global_level,
            ];
        }

        
        return parent::toArray($request);
    }
}
