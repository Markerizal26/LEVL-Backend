<?php

namespace Modules\Gamification\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Gamification\Enums\ChallengeAssignmentStatus;

class UserChallengeAssignmentResource extends JsonResource
{
    public function toArray($request)
    {
        $status = $this->status;
        $statusValue = $status instanceof ChallengeAssignmentStatus ? $status->value : (string) $status;
        $statusLabel = $status instanceof ChallengeAssignmentStatus ? $status->label() : (string) $status;

        // Avoid triggering lazy-loading (and N+1) if 'challenge' wasn't eager loaded
        $challengeTarget = 1;
        if (is_object($this->resource) && method_exists($this->resource, 'relationLoaded') && $this->resource->relationLoaded('challenge')) {
            $challengeTarget = $this->challenge?->criteria_target ?? 1;
        }

        return [
            'id' => $this->id,
            'challenge' => new ChallengeResource($this->whenLoaded('challenge')),
            'progress' => [
                'current' => $this->current_progress,
                'target' => $challengeTarget,
                // If challenge isn't loaded, we can't compute an accurate percentage without extra queries.
                'percentage' => $challengeTarget > 0
                    ? min(100.0, ($this->current_progress / $challengeTarget) * 100)
                    : 100.0,
            ],
            'status' => $statusValue,
            'status_label' => $statusLabel,
            'assigned_date' => $this->assigned_date,
            'expires_at' => $this->expires_at,
            'is_claimable' => $this->isClaimable(),
        ];
    }
}
