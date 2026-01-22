<?php

declare(strict_types=1);

namespace Modules\Common\Services;

use Illuminate\Support\Collection;
use Modules\Auth\Models\User;
use Modules\Common\Contracts\Repositories\AuditRepositoryInterface;
use Modules\Common\Contracts\Services\AuditServiceInterface;
use Modules\Common\Models\AuditLog;
use Modules\Grading\Models\Appeal;
use Modules\Grading\Models\Grade;
use Modules\Learning\Models\Question;
use Modules\Learning\Models\Submission;

/**
 * Service for comprehensive audit logging of all critical assessment and grading operations.
 *
 * This service provides immutable logging for compliance and dispute resolution.
 * All log entries are append-only and cannot be modified or deleted.
 *
 * Requirements: 20.1, 20.2, 20.3, 20.4, 20.5, 20.7
 */
class AssessmentAuditService implements AuditServiceInterface
{
    /**
     * Action constants for audit logging.
     */
    public const ACTION_SUBMISSION_CREATED = 'submission_created';

    public const ACTION_STATE_TRANSITION = 'state_transition';

    public const ACTION_GRADING = 'grading';

    public const ACTION_ANSWER_KEY_CHANGE = 'answer_key_change';

    public const ACTION_GRADE_OVERRIDE = 'grade_override';

    public const ACTION_APPEAL_DECISION = 'appeal_decision';

    public const ACTION_OVERRIDE_GRANT = 'override_grant';

    public function __construct(
        private readonly AuditRepositoryInterface $auditRepository
    ) {}

    /**
     * Log submission creation.
     *
     * Requirements: 20.1
     *
     * @param  Submission  $submission  The created submission
     */
    public function logSubmissionCreated(Submission $submission): void
    {
        $actor = $this->getActor($submission->user_id);

        AuditLog::logAction(
            action: self::ACTION_SUBMISSION_CREATED,
            subject: $submission,
            actor: $actor,
            context: [
                'assignment_id' => $submission->assignment_id,
                'student_id' => $submission->user_id,
                'attempt_number' => $submission->attempt_number,
                'state' => $submission->state ? $submission->state->value : 'in_progress',
                'is_late' => $submission->is_late ?? false,
                'submitted_at' => $submission->submitted_at ? $submission->submitted_at->toIso8601String() : null,
            ]
        );
    }

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
    public function logStateTransition(Submission $submission, string $oldState, string $newState, int $actorId): void
    {
        $actor = $this->getActor($actorId);

        AuditLog::logAction(
            action: self::ACTION_STATE_TRANSITION,
            subject: $submission,
            actor: $actor,
            context: [
                'assignment_id' => $submission->assignment_id,
                'student_id' => $submission->user_id,
                'old_state' => $oldState,
                'new_state' => $newState,
                'actor_id' => $actorId,
                'transitioned_at' => now()->toIso8601String(),
            ]
        );
    }

    /**
     * Log grading action.
     *
     * Requirements: 20.2
     *
     * @param  Grade  $grade  The grade being recorded
     * @param  int  $instructorId  The ID of the instructor performing the grading
     */
    public function logGrading(Grade $grade, int $instructorId): void
    {
        $actor = $this->getActor($instructorId);

        AuditLog::logAction(
            action: self::ACTION_GRADING,
            subject: $grade,
            actor: $actor,
            context: [
                'submission_id' => $grade->submission_id,
                'student_id' => $grade->user_id,
                'instructor_id' => $instructorId,
                'score' => (float) $grade->score,
                'max_score' => (float) ($grade->max_score ?? 100),
                'is_draft' => $grade->is_draft ?? false,
                'feedback' => $grade->feedback,
                'graded_at' => $grade->graded_at?->toIso8601String() ?? now()->toIso8601String(),
            ]
        );
    }

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
    public function logAnswerKeyChange(Question $question, array $oldKey, array $newKey, int $instructorId): void
    {
        $actor = $this->getActor($instructorId);

        AuditLog::logAction(
            action: self::ACTION_ANSWER_KEY_CHANGE,
            subject: $question,
            actor: $actor,
            context: [
                'assignment_id' => $question->assignment_id,
                'question_id' => $question->id,
                'question_type' => $question->type instanceof \Modules\Learning\Enums\QuestionType ? $question->type->value : $question->type,
                'instructor_id' => $instructorId,
                'old_answer_key' => $oldKey,
                'new_answer_key' => $newKey,
                'changed_at' => now()->toIso8601String(),
            ]
        );
    }

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
    public function logGradeOverride(Grade $grade, float $oldScore, float $newScore, string $reason, int $instructorId): void
    {
        $actor = $this->getActor($instructorId);

        AuditLog::logAction(
            action: self::ACTION_GRADE_OVERRIDE,
            subject: $grade,
            actor: $actor,
            context: [
                'submission_id' => $grade->submission_id,
                'student_id' => $grade->user_id,
                'instructor_id' => $instructorId,
                'old_score' => $oldScore,
                'new_score' => $newScore,
                'reason' => $reason,
                'overridden_at' => now()->toIso8601String(),
            ]
        );
    }

    /**
     * Log appeal decision.
     *
     * Requirements: 20.5
     *
     * @param  Appeal  $appeal  The appeal being decided
     * @param  string  $decision  The decision (approved/denied)
     * @param  int  $instructorId  The ID of the instructor making the decision
     */
    public function logAppealDecision(Appeal $appeal, string $decision, int $instructorId): void
    {
        $actor = $this->getActor($instructorId);

        AuditLog::logAction(
            action: self::ACTION_APPEAL_DECISION,
            subject: $appeal,
            actor: $actor,
            context: [
                'appeal_id' => $appeal->id,
                'submission_id' => $appeal->submission_id,
                'student_id' => $appeal->student_id,
                'instructor_id' => $instructorId,
                'decision' => $decision,
                'decision_reason' => $appeal->decision_reason,
                'appeal_reason' => $appeal->reason,
                'decided_at' => $appeal->decided_at?->toIso8601String() ?? now()->toIso8601String(),
            ]
        );
    }

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
    public function logOverrideGrant(int $assignmentId, int $studentId, string $overrideType, string $reason, int $instructorId): void
    {
        $actor = $this->getActor($instructorId);

        // Create a context array for the override
        AuditLog::logAction(
            action: self::ACTION_OVERRIDE_GRANT,
            subject: null, // No specific model subject for override grants
            actor: $actor,
            context: [
                'assignment_id' => $assignmentId,
                'student_id' => $studentId,
                'instructor_id' => $instructorId,
                'override_type' => $overrideType,
                'reason' => $reason,
                'granted_at' => now()->toIso8601String(),
            ]
        );
    }

    /**
     * Search and filter audit logs.
     *
     * Requirements: 20.7
     *
     * @param  array  $filters  Search filters (action, actor_id, subject_type, subject_id, date_range)
     * @return Collection<int, AuditLog> Collection of matching audit logs
     */
    public function search(array $filters): Collection
    {
        return $this->auditRepository->search($filters);
    }

    /**
     * Get the actor model for audit logging.
     *
     * @param  int|null  $userId  The user ID
     * @return User|null The user model or null
     */
    private function getActor(?int $userId): ?User
    {
        if ($userId === null) {
            return null;
        }

        return User::find($userId);
    }
}
