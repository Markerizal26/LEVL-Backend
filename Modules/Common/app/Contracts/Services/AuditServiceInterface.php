<?php

declare(strict_types=1);

namespace Modules\Common\Contracts\Services;

use Illuminate\Support\Collection;
use Modules\Common\Models\AuditLog;
use Modules\Grading\Models\Appeal;
use Modules\Grading\Models\Grade;
use Modules\Learning\Models\Question;
use Modules\Learning\Models\Submission;

/**
 * Interface for comprehensive audit logging of all critical operations.
 *
 * This service provides immutable logging for compliance and dispute resolution.
 *
 * Requirements: 20.1, 20.2, 20.3, 20.4, 20.5, 20.7
 */
interface AuditServiceInterface
{
    /**
     * Log submission creation.
     *
     * Requirements: 20.1
     *
     * @param  Submission  $submission  The created submission
     */
    public function logSubmissionCreated(Submission $submission): void;

    /**
     * Log submission state transition.
     *
     * Requirements: 20.1, 9.8
     *
     * @param  Submission  $submission  The submission being transitioned
     * @param  string  $oldState  The previous state
     * @param  string  $newState  The new state
     * @param  int  $actorId  The ID of the user performing the transition
     */
    public function logStateTransition(Submission $submission, string $oldState, string $newState, int $actorId): void;

    /**
     * Log grading action.
     *
     * Requirements: 20.2
     *
     * @param  Grade  $grade  The grade being recorded
     * @param  int  $instructorId  The ID of the instructor performing the grading
     */
    public function logGrading(Grade $grade, int $instructorId): void;

    /**
     * Log answer key change.
     *
     * Requirements: 20.3
     *
     * @param  Question  $question  The question with changed answer key
     * @param  array  $oldKey  The previous answer key
     * @param  array  $newKey  The new answer key
     * @param  int  $instructorId  The ID of the instructor making the change
     */
    public function logAnswerKeyChange(Question $question, array $oldKey, array $newKey, int $instructorId): void;

    /**
     * Log grade override.
     *
     * Requirements: 20.4
     *
     * @param  Grade  $grade  The grade being overridden
     * @param  float  $oldScore  The original score
     * @param  float  $newScore  The new override score
     * @param  string  $reason  The reason for the override
     * @param  int  $instructorId  The ID of the instructor performing the override
     */
    public function logGradeOverride(Grade $grade, float $oldScore, float $newScore, string $reason, int $instructorId): void;

    /**
     * Log appeal decision.
     *
     * Requirements: 20.5
     *
     * @param  Appeal  $appeal  The appeal being decided
     * @param  string  $decision  The decision (approved/denied)
     * @param  int  $instructorId  The ID of the instructor making the decision
     */
    public function logAppealDecision(Appeal $appeal, string $decision, int $instructorId): void;

    /**
     * Log instructor override grant (prerequisites, attempts, deadlines).
     *
     * Requirements: 24.5
     *
     * @param  int  $assignmentId  The assignment ID
     * @param  int  $studentId  The student ID
     * @param  string  $overrideType  The type of override (prerequisite, deadline, attempts)
     * @param  string  $reason  The reason for the override
     * @param  int  $instructorId  The ID of the instructor granting the override
     */
    public function logOverrideGrant(int $assignmentId, int $studentId, string $overrideType, string $reason, int $instructorId): void;

    /**
     * Search and filter audit logs.
     *
     * Requirements: 20.7
     *
     * @param  array  $filters  Search filters (action, actor_id, subject_type, subject_id, date_range)
     * @return Collection<int, AuditLog> Collection of matching audit logs
     */
    public function search(array $filters): Collection;
}
