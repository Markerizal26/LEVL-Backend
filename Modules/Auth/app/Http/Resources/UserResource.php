<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Auth\Enums\UserStatus;
use Modules\Enrollments\Http\Resources\EnrollmentResource;
use Modules\Forums\Http\Resources\ThreadResource;
use Modules\Gamification\Transformers\ChallengeCompletionResource;
use Modules\Gamification\Transformers\UserChallengeAssignmentResource;
use Modules\Learning\Http\Resources\AssignmentIndexResource;
use Modules\Learning\Http\Resources\OverrideResource;
use Modules\Learning\Http\Resources\SubmissionIndexResource;
use Modules\Schemes\Http\Resources\CourseIndexResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this['id'] ?? (is_object($this->resource) ? $this->id : null),
            'name' => $this['name'] ?? (is_object($this->resource) ? $this->name : null),
            'email' => $this['email'] ?? (is_object($this->resource) ? $this->email : null),
            'username' => $this['username'] ?? (is_object($this->resource) ? $this->username : null),
            'avatar_url' => $this['avatar_url'] ?? (is_object($this->resource) ? $this->resource->avatar_url ?? null : null),
            'status' => isset($this['status']) && $this['status'] instanceof UserStatus
                ? $this['status']->value
                : (string) ($this['status'] ?? (is_object($this->resource) ? $this->status : null)),
            'account_status' => $this['account_status'] ?? (is_object($this->resource) ? $this->account_status : null),
            'created_at' => $this->formatDate($this['created_at'] ?? (is_object($this->resource) ? $this->created_at : null)),
            'email_verified_at' => $this->formatDate($this['email_verified_at'] ?? (is_object($this->resource) ? $this->email_verified_at : null)),

            'roles' => $this->whenLoaded('roles', fn () => $this->getRoles()),

            'privacySettings' => $this->whenLoaded('privacySettings', fn () => new ProfilePrivacyResource($this->privacySettings)),
            'enrollments' => $this->whenLoaded('enrollments', fn () => EnrollmentResource::collection($this->enrollments)),
            'managedCourses' => $this->whenLoaded('managedCourses', fn () => CourseIndexResource::collection($this->managedCourses)),
            'gamificationStats' => $this->whenLoaded('gamificationStats', fn () => $this->gamificationStats ? $this->gamificationStats->toArray() : null),
            'badges' => $this->whenLoaded('badges', fn () => $this->badges->toArray()),
            'challenges' => $this->whenLoaded('challenges', fn () => UserChallengeAssignmentResource::collection($this->challenges)),
            'challengeCompletions' => $this->whenLoaded('challengeCompletions', fn () => ChallengeCompletionResource::collection($this->challengeCompletions)),
            'points' => $this->whenLoaded('points', fn () => $this->points->toArray()),
            'levels' => $this->whenLoaded('levels', fn () => $this->levels->toArray()),
            'learningStreaks' => $this->whenLoaded('learningStreaks', fn () => $this->learningStreaks->toArray()),
            'submissions' => $this->whenLoaded('submissions', fn () => SubmissionIndexResource::collection($this->submissions)),
            'assignments' => $this->whenLoaded('assignments', fn () => AssignmentIndexResource::collection($this->assignments)),
            'receivedOverrides' => $this->whenLoaded('receivedOverrides', fn () => OverrideResource::collection($this->receivedOverrides)),
            'grantedOverrides' => $this->whenLoaded('grantedOverrides', fn () => OverrideResource::collection($this->grantedOverrides)),
            'threads' => $this->whenLoaded('threads', fn () => ThreadResource::collection($this->threads)),
        ];
    }

    protected function formatDate(mixed $date): ?string
    {
        if ($date instanceof \DateTimeInterface) {
            return $date->format(\DateTimeInterface::ATOM);
        }
        return $date ? (string) $date : null;
    }

    protected function getRoles(): array
    {
        if (isset($this['roles'])) {
            $roles = $this['roles'];
            if ($roles instanceof \Illuminate\Support\Collection) {
                return $roles->toArray();
            }
            if (is_array($roles)) {
                return $roles;
            }
        }
        
        // If resource is a User model object
        if ($this->resource instanceof \Modules\Auth\Models\User) {
            return $this->resource->roles->toArray();
        }
        
        // Fallback for generic object with roles relation
        if (is_object($this->resource) && isset($this->resource->roles)) {
             return $this->resource->roles->toArray();
        }

        return [];
    }
}
