<?php

namespace Modules\Gamification\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class PointResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'points' => $this->points,
            'source_type' => $this->source_type?->value,
            'source_type_label' => $this->source_type?->label(),
            'reason' => $this->reason?->value,
            'reason_label' => $this->reason?->label(),
            'description' => $this->description,
            'created_at' => $this->created_at,
        ];
    }
}
