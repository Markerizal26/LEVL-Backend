<?php

declare(strict_types=1);

namespace Modules\Notifications\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

use Modules\Learning\Models\Submission;
use Modules\Notifications\Contracts\Services\GradingNotificationServiceInterface;

class GradingNotificationService implements GradingNotificationServiceInterface
{
    /**
     * Notify a student that their submission has been graded.
     */
    public function notifySubmissionGraded(Submission $submission): void
    {
        // TODO: Implement actual notification logic (email/database)
        Log::info(__('notifications.grading.submission_graded', [
            'id' => $submission->id,
            'user_id' => $submission->user_id,
        ]));
    }

    /**
     * Notify students that grades have been released for an assignment.
     */
    public function notifyGradesReleased(Collection $submissions): void
    {
        // TODO: Implement actual notification logic
        Log::info(__('notifications.grading.grades_released', [
            'count' => $submissions->count(),
        ]));
    }

    /**
     * Notify instructors that a submission requires manual grading.
     */
    public function notifyManualGradingRequired(Submission $submission): void
    {
        // TODO: Implement actual notification logic
        Log::info(__('notifications.grading.manual_grading_required', [
            'id' => $submission->id,
        ]));
    }

    /**
     * Notify a student that their grade has been recalculated.
     */
    public function notifyGradeRecalculated(Submission $submission, float $oldScore, float $newScore): void
    {
        // TODO: Implement actual notification logic
        Log::info(__('notifications.grading.grade_recalculated', [
            'id' => $submission->id,
            'old_score' => $oldScore,
            'new_score' => $newScore,
        ]));
    }
}
