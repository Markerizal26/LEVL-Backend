<?php

namespace Modules\Gamification\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class ChallengeResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'type' => $this->type->value,
            'points_reward' => $this->points_reward,
            'criteria_target' => $this->criteria_target,
            'badge' => $this->whenLoaded('badge', function () {
                return [
                    'id' => $this->badge->id,
                    'name' => $this->badge->name,
                    'icon_url' => $this->badge->icon_url,
                ];
            }),
            'user_progress' => $this->when($this->user_progress, $this->user_progress),
        ];
    }
}
