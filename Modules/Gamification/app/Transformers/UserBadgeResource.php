<?php

namespace Modules\Gamification\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class UserBadgeResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->badge_id,
            'code' => $this->badge?->code,
            'name' => $this->badge?->name,
            'description' => $this->description ?? $this->badge?->description,
            'icon_url' => $this->badge?->icon_url,
            'type' => $this->badge?->type?->value,
            'awarded_at' => $this->awarded_at,
        ];
    }
}
