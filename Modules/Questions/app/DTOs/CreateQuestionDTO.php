<?php

namespace Modules\Questions\DTOs;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\Validation\ArrayType;
use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Attributes\Validation\Integer;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
final class CreateQuestionDTO extends Data
{
    public function __construct(
        #[Required, Integer]
        #[MapInputName('category_id')]
        public ?int $categoryId,

        #[Required, In(['multiple_choice', 'essay', 'file_upload', 'true_false'])]
        public string $type,

        #[Required, In(['easy', 'medium', 'hard'])]
        public string $difficulty,

        #[Required, Max(5000)]
        #[MapInputName('question_text')]
        public string $questionText,

        #[Nullable, Max(2000)]
        public ?string $explanation,

        #[Required, Integer, Min(1)]
        public int $points,

        #[Nullable, ArrayType]
        public ?array $tags,

        #[Nullable, ArrayType]
        public ?array $meta,

        #[Nullable, ArrayType]
        public ?array $options,
    ) {}

    public function toModelArray(): array
    {
        return [
            'category_id' => $this->categoryId,
            'type' => $this->type,
            'difficulty' => $this->difficulty,
            'question_text' => $this->questionText,
            'explanation' => $this->explanation,
            'points' => $this->points,
            'tags' => $this->tags,
            'meta' => $this->meta,
            'status' => 'active',
        ];
    }
}
