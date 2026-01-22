<?php

declare(strict_types=1);

namespace Modules\Learning\Contracts\Services;

use Modules\Learning\Models\Submission;

interface ReviewModeServiceInterface
{
    public function canViewAnswers(Submission $submission, ?int $userId = null): bool;

    public function canViewFeedback(Submission $submission, ?int $userId = null): bool;

    public function canViewScore(Submission $submission, ?int $userId = null): bool;

    public function getVisibilityStatus(Submission $submission, ?int $userId = null): array;
}
