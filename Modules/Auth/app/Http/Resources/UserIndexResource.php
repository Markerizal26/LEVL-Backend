<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Auth\Enums\UserStatus;

class UserIndexResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this['id'] ?? (is_object($this->resource) ? $this->id : null),
            'name' => $this['name'] ?? (is_object($this->resource) ? $this->name : null),
            'email' => $this['email'] ?? (is_object($this->resource) ? $this->email : null),
            'username' => $this['username'] ?? (is_object($this->resource) ? $this->username : null),
            'avatar_url' => $this->whenLoaded('media') ? ($this->getFirstMedia('avatar')?->getUrl() ?? '') : '',
            'status' => isset($this['status']) && $this['status'] instanceof UserStatus
                ? $this['status']->value
                : (string) ($this['status'] ?? (is_object($this->resource) ? $this->status : null)),
            'account_status' => $this['account_status'] ?? (is_object($this->resource) ? $this->account_status : null),
            'created_at' => $this->formatDate($this['created_at'] ?? (is_object($this->resource) ? $this->created_at : null)),
            'email_verified_at' => $this->formatDate($this->getEmailVerifiedAt()),
            'is_password_set' => $this['is_password_set'] ?? (is_object($this->resource) ? $this->is_password_set : null),
            'role_names' => $this->whenLoaded('roles') ? $this->roles->pluck('name')->values()->toArray() : [],
        ];
    }

    protected function getEmailVerifiedAt(): ?string
    {
        $val = $this['email_verified_at'] ?? (is_object($this->resource) ? $this->resource->email_verified_at : null);

        if ($val instanceof \DateTimeInterface) {
            return $val->format(\DateTimeInterface::ATOM);
        }

        return $val ? (string) $val : null;
    }

    protected function formatDate(mixed $date): ?string
    {
        if ($date instanceof \DateTimeInterface) {
            return $date->format(\DateTimeInterface::ATOM);
        }
        return $date ? (string) $date : null;
    }
}