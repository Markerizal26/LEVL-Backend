<?php

declare(strict_types=1);

namespace Modules\Learning\DTOs;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
final class CreateSubmissionDTO extends Data
{
    public function __construct(
        #[Required]
        #[MapInputName('assignment_id')]
        public int $assignmentId,

        public ?string $content = null,

        public ?array $files = null,
    ) {}

    public function toModelArray(): array
    {
        return [
            'assignment_id' => $this->assignmentId,
            'content' => $this->content,
            'files' => $this->files,
        ];
    }
}
