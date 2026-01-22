<?php

declare(strict_types=1);

namespace Modules\Learning\Exceptions;

class SubmissionException extends LearningDomainException
{
    public static function notAllowed(string $message): self
    {
        return new self($message);
    }

    public static function deadlinePassed(): self
    {
        return new self(__('messages.submissions.deadline_passed'));
    }

    public static function maxAttemptsReached(string $message): self
    {
        return new self($message);
    }
    
    public static function alreadyGraded(): self
    {
        return new self(__('messages.submissions.already_graded'));
    }

    public static function invalidScore(string $message): self
    {
        return new self($message);
    }
}
