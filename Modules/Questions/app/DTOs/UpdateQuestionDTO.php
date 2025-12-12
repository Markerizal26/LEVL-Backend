<?php

namespace Modules\Questions\DTOs;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\Validation\ArrayType;
use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Attributes\Validation\Integer;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\LaravelData\Optional;

#[MapInputName(SnakeCaseMapper::class)]
final class UpdateQuestionDTO extends Data
{
    public function __construct(
        #[Integer]
        #[MapInputName('category_id')]
        public int|Optional|null $categoryId,

        #[In(['multiple_choice', 'essay', 'file_upload', 'true_false'])]
        public string|Optional|null $type,

        #[In(['easy', 'medium', 'hard'])]
        public string|Optional|null $difficulty,

        #[Max(5000)]
        #[MapInputName('question_text')]
        public string|Optional|null $questionText,

        #[Max(2000)]
        public string|Optional|null $explanation,

        #[Integer, Min(1)]
        public int|Optional|null $points,

        #[ArrayType]
        public array|Optional|null $tags,

        #[ArrayType]
        public array|Optional|null $meta,

        #[In(['active', 'inactive', 'archived'])]
        public string|Optional|null $status,

        #[Nullable, ArrayType]
        public array|Optional|null $options,
    ) {}

    public function toModelArray(): array
    {
        $data = [];

        if (! $this->categoryId instanceof Optional) {
            $data['category_id'] = $this->categoryId;
        }
        if (! $this->type instanceof Optional) {
            $data['type'] = $this->type;
        }
        if (! $this->difficulty instanceof Optional) {
            $data['difficulty'] = $this->difficulty;
        }
        if (! $this->questionText instanceof Optional) {
            $data['question_text'] = $this->questionText;
        }
        if (! $this->explanation instanceof Optional) {
            $data['explanation'] = $this->explanation;
        }
        if (! $this->points instanceof Optional) {
            $data['points'] = $this->points;
        }
        if (! $this->tags instanceof Optional) {
            $data['tags'] = $this->tags;
        }
        if (! $this->meta instanceof Optional) {
            $data['meta'] = $this->meta;
        }
        if (! $this->status instanceof Optional) {
            $data['status'] = $this->status;
        }

        return $data;
    }
}
