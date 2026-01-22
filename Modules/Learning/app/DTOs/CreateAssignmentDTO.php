<?php

declare(strict_types=1);

namespace Modules\Learning\DTOs;

use Carbon\Carbon;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
final class CreateAssignmentDTO extends Data
{
    public function __construct(
        #[Required, Max(255)]
        public string $title,

        #[Required]
        public string $description,

        #[Required]
        #[MapInputName('lesson_id')]
        public int $lessonId,

        #[MapInputName('submission_type')]
        public ?string $submissionType = 'file',

        #[MapInputName('max_score'), Min(0)]
        public ?int $maxScore = 100,

        #[MapInputName('due_date')]
        public ?Carbon $dueDate = null,
    ) {}

    public function toModelArray(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'lesson_id' => $this->lessonId,
            'submission_type' => $this->submissionType,
            'max_score' => $this->maxScore,
            'due_date' => $this->dueDate?->format('Y-m-d H:i:s'),
        ];
    }
}
