<?php

namespace Modules\Gamification\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class ChallengeCompletionResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'challenge' => new ChallengeResource($this->whenLoaded('challenge')),
            'completed_date' => $this->completed_date,
            'xp_earned' => $this->xp_earned,
            'completion_data' => $this->completion_data,
        ];
    }
}
