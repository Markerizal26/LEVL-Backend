<?php

namespace Modules\Common\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class ActivityLogResource extends JsonResource
{
    public function toArray($request)
    {
        $properties = $this->properties ?? [];

        return [
            'id' => $this->id,
            'log_name' => $this->log_name,
            'description' => $this->description,
            'subject_type' => $this->subject_type,
            'subject_id' => $this->subject_id,
            'causer_type' => $this->causer_type,
            'causer_id' => $this->causer_id,
            'properties' => $properties,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'event' => $this->event,
            'ip_address' => $properties['ip'] ?? null,
            'browser' => $properties['browser'] ?? null,
            'platform' => $properties['platform'] ?? null,
            'device_type' => $properties['device_type'] ?? null,
            'location' => [
                'city' => $properties['city'] ?? null,
                'region' => $properties['region'] ?? null,
                'country' => $properties['country'] ?? null,
            ],
            'device_info' => $this->device_info, // Accessor from model
        ];
    }
}
