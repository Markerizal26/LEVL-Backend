<?php

declare(strict_types=1);

namespace Modules\Learning\DTOs;

use Illuminate\Support\Collection;

class PrerequisiteCheckResult
{
    public function __construct(
        public readonly bool $passed,
        public readonly Collection $incompletePrerequisites,
        public readonly ?string $message = null
    ) {}

    public static function pass(): self
    {
        return new self(
            passed: true,
            incompletePrerequisites: collect(),
            message: null
        );
    }

    public static function fail(Collection $incompletePrerequisites): self
    {
        $count = $incompletePrerequisites->count();
        $message = "You must complete {$count} prerequisite assignment(s) before accessing this assignment.";

        return new self(
            passed: false,
            incompletePrerequisites: $incompletePrerequisites,
            message: $message
        );
    }

    public function toArray(): array
    {
        return [
            'passed' => $this->passed,
            'incomplete_prerequisites' => $this->incompletePrerequisites->map(fn ($a) => [
                'id' => $a->id,
                'title' => $a->title,
            ])->toArray(),
            'message' => $this->message,
        ];
    }
}
