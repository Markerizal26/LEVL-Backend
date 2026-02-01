<?php

declare(strict_types=1);

namespace Modules\Notifications\Contracts\Services;

use Illuminate\Support\Collection;

use Modules\Learning\Models\Submission;

/**
 * Interface for grading-related notification services.
 *
 * This service handles dispatching notifications for all grading events
 * including submission grading, grade releases, manual grading requirements,
 * appeals, and grade recalculations.
 *
 * @see Requirements 21.1: WHEN a submission is graded, THE System SHALL notify the student
 * @see Requirements 21.2: WHEN grades are released in deferred mode, THE System SHALL notify affected students
 * @see Requirements 21.3: WHEN a submission requires manual grading, THE System SHALL notify assigned instructors
 * @see Requirements 21.4: WHEN an appeal is submitted, THE System SHALL notify instructors
 * @see Requirements 21.5: WHEN an appeal is decided, THE System SHALL notify the student
 * @see Requirements 21.6: THE System SHALL support email and in-app notification channels
 */
interface GradingNotificationServiceInterface
{
    /**
     * Notify a student that their submission has been graded.
     *
     * @param  Submission  $submission  The graded submission
     *
     * @see Requirements 21.1
     */
    public function notifySubmissionGraded(Submission $submission): void;

    /**
     * Notify students that grades have been released for an assignment.
     *
     * Used when grades are released in deferred review mode.
     *
     * @param  Collection<int, Submission>  $submissions  Collection of submissions whose grades are being released
     *
     * @see Requirements 21.2
     */
    public function notifyGradesReleased(Collection $submissions): void;

    /**
     * Notify instructors that a submission requires manual grading.
     *
     * @param  Submission  $submission  The submission requiring manual grading
     *
     * @see Requirements 21.3
     */
    public function notifyManualGradingRequired(Submission $submission): void;

    /**
     * Notify a student that their grade has been recalculated.
     *
     * Used when answer keys are updated and grades are recalculated.
     *
     * @param  Submission  $submission  The submission with recalculated grade
     * @param  float  $oldScore  The previous score before recalculation
     * @param  float  $newScore  The new score after recalculation
     *
     * @see Requirements 15.5
     */
    public function notifyGradeRecalculated(Submission $submission, float $oldScore, float $newScore): void;
}
