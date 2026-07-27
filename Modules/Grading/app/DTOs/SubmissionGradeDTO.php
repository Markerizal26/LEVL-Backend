<?php

declare(strict_types=1);

namespace Modules\Grading\DTOs;

use Spatie\LaravelData\Data;

class SubmissionGradeDTO extends Data
{
    public function __construct(
        public int $submissionId,
        public array $answers,
        public ?float $scoreOverride = null,
        public ?string $feedback = null,
        public ?int $graderId = null,
        public bool $isAiAssisted = false,
        public ?float $aiSuggestedScore = null,
        public ?string $aiReasoning = null,
        public ?string $aiScoringReference = null
    ) {}
}
