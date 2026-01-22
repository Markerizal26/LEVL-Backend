<?php

declare(strict_types=1);

namespace Modules\Learning\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Learning\Models\Override;

/**
 * Resource for transforming Override models to API responses.
 *
 * @mixin Override
 */
class OverrideResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Override $override */
        $override = $this->resource;

        return [
            'id' => $override->id,
            'assignment_id' => $override->assignment_id,
            'student_id' => $override->student_id,
            'grantor_id' => $override->grantor_id,
            'type' => $override->type?->value,
            'reason' => $override->reason,
            'value' => $override->value,
            'is_active' => $override->isActive(),
            'granted_at' => $override->granted_at?->toIso8601String(),
            'expires_at' => $override->expires_at?->toIso8601String(),
            'created_at' => $override->created_at?->toIso8601String(),
            'updated_at' => $override->updated_at?->toIso8601String(),

            // Relationships
            'student' => $this->whenLoaded('student', function () use ($override) {
                return [
                    'id' => $override->student->id,
                    'name' => $override->student->name,
                    'email' => $override->student->email,
                ];
            }),
            'grantor' => $this->whenLoaded('grantor', function () use ($override) {
                return [
                    'id' => $override->grantor->id,
                    'name' => $override->grantor->name,
                    'email' => $override->grantor->email,
                ];
            }),
        ];
    }
}
