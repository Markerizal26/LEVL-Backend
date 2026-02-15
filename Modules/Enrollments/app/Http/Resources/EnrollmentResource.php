<?php

declare(strict_types=1);

namespace Modules\Enrollments\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EnrollmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'enrolled_at' => $this->enrolled_at?->toISOString(),
            'completed_at' => $this->completed_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),

            'user' => $this->whenLoaded('user', function () {
                $avatarUrl = null;
                if (is_object($this->user) && method_exists($this->user, 'getFirstMedia')) {
                    $avatarUrl = $this->user->getFirstMedia('avatar')?->getUrl();
                }

                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                    'avatar_url' => $avatarUrl,
                ];
            }),

            'course' => $this->whenLoaded('course', function () {
                return [
                    'id' => $this->course->id,
                    'title' => $this->course->title,
                    'slug' => $this->course->slug,
                    'code' => $this->course->code ?? null,
                ];
            }),
        ];
    }
}
