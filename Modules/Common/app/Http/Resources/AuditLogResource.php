<?php

declare(strict_types=1);

namespace Modules\Common\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Common\Models\AuditLog;

/**
 * Resource for audit log entries.
 *
 * Transforms AuditLog model data for API responses.
 *
 * Requirement: 20.7
 *
 * @mixin AuditLog
 */
class AuditLogResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var AuditLog $auditLog */
        $auditLog = $this->resource;

        return [
            'id' => $auditLog->id,
            'action' => $auditLog->action,
            'actor' => [
                'id' => $auditLog->actor_id,
                'type' => $auditLog->actor_type,
                'name' => $this->whenLoaded('actor', function () use ($auditLog) {
                    return $auditLog->actor->name ?? null;
                }),
            ],
            'subject' => [
                'id' => $auditLog->subject_id,
                'type' => $auditLog->subject_type,
            ],
            'context' => $auditLog->context,
            'created_at' => $auditLog->created_at->toIso8601String(),

            // Legacy fields for backward compatibility
            'event' => $this->when($auditLog->event !== null, $auditLog->event),
            'target' => $this->when($auditLog->target_type !== null, [
                'id' => $auditLog->target_id,
                'type' => $auditLog->target_type,
            ]),
            'properties' => $this->when($auditLog->properties !== null, $auditLog->properties),
            'logged_at' => $this->when(
                $auditLog->logged_at !== null,
                $auditLog->logged_at?->toIso8601String()
            ),
        ];
    }
}
