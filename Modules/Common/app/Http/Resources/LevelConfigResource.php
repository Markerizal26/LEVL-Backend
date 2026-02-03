<?php

declare(strict_types=1);

namespace Modules\Common\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LevelConfigResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'level' => $this->level,
            'name' => $this->name,
            'xp_required' => $this->xp_required,
            'rewards' => $this->rewards,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
