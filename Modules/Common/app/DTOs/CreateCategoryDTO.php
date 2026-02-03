<?php

declare(strict_types=1);

namespace Modules\Common\DTOs;

use App\Support\BaseDTO;

final class CreateCategoryDTO extends BaseDTO
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $description = null,
    ) {}

    public static function fromRequest(array $data): static
    {
        return new self(
            name: $data['name'],
            description: $data['description'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
        ];
    }
}
