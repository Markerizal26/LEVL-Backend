<?php

declare(strict_types=1);

namespace Modules\Learning\DTOs;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\LaravelData\Optional;

#[MapInputName(SnakeCaseMapper::class)]
final class UpdateSubmissionDTO extends Data
{
    public function __construct(
        public string|Optional|null $content,

        public array|Optional|null $files,

        public string|Optional|null $status,
    ) {}

    public function toModelArray(): array
    {
        $data = [];

        if (! $this->content instanceof Optional) {
            $data['content'] = $this->content;
        }
        if (! $this->files instanceof Optional) {
            $data['files'] = $this->files;
        }
        if (! $this->status instanceof Optional) {
            $data['status'] = $this->status;
        }

        return $data;
    }
}
