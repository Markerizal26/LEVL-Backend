<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Auth\Enums\UserStatus;



class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this['id'] ?? $this->id,
            'name' => $this['name'] ?? $this->name,
            'email' => $this['email'] ?? $this->email,
            'username' => $this['username'] ?? $this->username,
            'avatar_url' => $this['avatar_url'] ?? (is_object($this->resource) ? $this->resource->avatar_url ?? null : null),
            'status' => isset($this['status']) && $this['status'] instanceof UserStatus 
                ? $this['status']->value 
                : (string) ($this['status'] ?? $this->status),
            'account_status' => $this['account_status'] ?? $this->account_status,
            'created_at' => $this->formatDate($this['created_at'] ?? $this->created_at),
            'email_verified_at' => $this->formatDate($this['email_verified_at'] ?? $this->email_verified_at),
            'last_active_relative' => $this['last_active_relative'] ?? $this->last_active_relative,
            'is_password_set' => $this['is_password_set'] ?? $this->is_password_set,
            'roles' => $this->getRoles(),
            'media' => $this['media'] ?? $this->media ?? [],
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
