<?php

declare(strict_types=1);

namespace Modules\Common\DTOs;

use App\Support\BaseDTO;

final class UpdateCategoryDTO extends BaseDTO
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $description = null,
        public readonly ?int $parentId = null,
        public readonly ?string $status = null,
    ) {}

    public static function fromRequest(array $data): static
    {
        return new self(
            name: $data['name'] ?? null,
            description: $data['description'] ?? null,
            parentId: $data['parent_id'] ?? null,
            status: $data['status'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'parent_id' => $this->parentId,
            'status' => $this->status,
        ];
    }
}
