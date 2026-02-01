<?php

namespace Modules\Gamification\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class UserChallengeAssignmentResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'challenge' => new ChallengeResource($this->whenLoaded('challenge')),
            'progress' => [
                'current' => $this->current_progress,
                'target' => $this->challenge?->criteria_target ?? 1,
                'percentage' => $this->getProgressPercentage(),
            ],
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'assigned_date' => $this->assigned_date,
            'expires_at' => $this->expires_at,
            'is_claimable' => $this->isClaimable(),
        ];
    }
}
