<?php

declare(strict_types=1);

namespace Modules\Learning\DTOs;

use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

final class UpdateAssignmentDTO extends Data
{
    public function __construct(
        #[Max(255)]
        public string|Optional $title,

        public string|Optional $description,

        public string|Optional $submissionType,

        #[Min(0)]
        public int|Optional $maxScore,

        public ?\DateTimeInterface $dueDate = null,

        public ?string $status = null,
    ) {}
}
