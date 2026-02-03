<?php

declare(strict_types=1);

namespace Modules\Common\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommonChallengeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'type' => $this->type?->value,
            'criteria' => $this->criteria,
            'target_count' => $this->target_count,
            'points_reward' => $this->points_reward,
            'badge_id' => $this->badge_id,
            'badge' => $this->whenLoaded('badge', fn () => new BadgeResource($this->badge)),
            'start_at' => $this->start_at?->toISOString(),
            'end_at' => $this->end_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
