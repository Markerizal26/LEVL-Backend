<?php

declare(strict_types=1);

namespace Modules\Learning\Contracts\Services;

use Modules\Learning\Models\Submission;

interface ReviewModeServiceInterface
{
    /**
     * Check if answers are visible for a submission based on review mode.
     */
    public function canViewAnswers(Submission $submission, ?int $userId = null): bool;

    /**
     * Check if feedback is visible for a submission based on review mode.
     */
    public function canViewFeedback(Submission $submission, ?int $userId = null): bool;

    /**
     * Check if score is visible for a submission.
     * Score is always visible regardless of review mode (Requirements 14.5).
     */
    public function canViewScore(Submission $submission, ?int $userId = null): bool;

    /**
     * Get the visibility status for a submission.
     */
    public function getVisibilityStatus(Submission $submission, ?int $userId = null): array;
}
