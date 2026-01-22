<?php

declare(strict_types=1);

namespace Modules\Learning\Exceptions;

class AssignmentException extends LearningDomainException
{
    public static function duplicateError(string $message): self
    {
        return new self($message);
    }

    public static function notFound(): self
    {
        return new self(__('messages.assignments.not_found'));
    }
}
