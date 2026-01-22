<?php

declare(strict_types=1);

namespace Modules\Learning\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Modules\Learning\Contracts\Repositories\OverrideRepositoryInterface;
use Modules\Learning\Contracts\Repositories\SubmissionRepositoryInterface;
use Modules\Learning\Contracts\Services\AssignmentServiceInterface;
use Modules\Learning\DTOs\PrerequisiteCheckResult;
use Modules\Learning\Enums\AssignmentStatus;
use Modules\Learning\Enums\OverrideType;
use Modules\Learning\Events\OverrideGranted;
use Modules\Learning\Models\Assignment;
use Modules\Learning\Models\Override;
use Modules\Learning\Repositories\AssignmentRepository;
use Modules\Schemes\Contracts\Services\LessonServiceInterface;

class AssignmentService implements AssignmentServiceInterface
{
    public function __construct(
        private readonly AssignmentRepository $repository,
        private readonly SubmissionRepositoryInterface $submissionRepository,
        private readonly ?OverrideRepositoryInterface $overrideRepository = null,
        private readonly ?LessonServiceInterface $lessonService = null
    ) {}

    public function listByLesson(\Modules\Schemes\Models\Lesson $lesson, array $filters = [])
    {
        return $this->repository->listForLesson($lesson, $filters);
    }

    public function create(array $data, int $createdBy): Assignment
    {
        $assignment = $this->repository->create([
            'lesson_id' => $data['lesson_id'],
            'created_by' => $createdBy,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'submission_type' => $data['submission_type'] ?? 'text',
            'max_score' => $data['max_score'] ?? 100,
            'available_from' => $data['available_from'] ?? null,
            'deadline_at' => $data['deadline_at'] ?? null,
            'status' => $data['status'] ?? AssignmentStatus::Draft->value,
            'allow_resubmit' => data_get($data, 'allow_resubmit') !== null ? (bool) $data['allow_resubmit'] : null,
            'late_penalty_percent' => $data['late_penalty_percent'] ?? null,
        ]);

        return $assignment->fresh(['lesson', 'creator']);
    }

    public function update(Assignment $assignment, array $data): Assignment
    {
        $updated = $this->repository->update($assignment, [
            'title' => $data['title'] ?? $assignment->title,
            'description' => $data['description'] ?? $assignment->description,
            'submission_type' => $data['submission_type'] ?? $assignment->submission_type ?? 'text',
            'max_score' => $data['max_score'] ?? $assignment->max_score,
            'available_from' => $data['available_from'] ?? $assignment->available_from,
            'deadline_at' => $data['deadline_at'] ?? $assignment->deadline_at,
            'status' => $data['status'] ?? ($assignment->status?->value ?? AssignmentStatus::Draft->value),
            'allow_resubmit' => data_get($data, 'allow_resubmit') !== null ? (bool) $data['allow_resubmit'] : $assignment->allow_resubmit,
            'late_penalty_percent' => data_get($data, 'late_penalty_percent', $assignment->late_penalty_percent),
        ]);

        return $updated->fresh(['lesson', 'creator']);
    }

    public function publish(Assignment $assignment): Assignment
    {
        $wasDraft = $assignment->status === AssignmentStatus::Draft;
        $published = $this->repository->update($assignment, ['status' => AssignmentStatus::Published->value]);
        $freshAssignment = $published->fresh(['lesson', 'creator']);

        if ($wasDraft) {
            \Modules\Learning\Events\AssignmentPublished::dispatch($freshAssignment);
        }

        return $freshAssignment;
    }

    public function unpublish(Assignment $assignment): Assignment
    {
        $updated = $this->repository->update($assignment, ['status' => AssignmentStatus::Draft->value]);

        return $updated->fresh(['lesson', 'creator']);
    }

    public function delete(Assignment $assignment): bool
    {
        return $this->repository->delete($assignment);
    }

    public function getWithRelations(Assignment $assignment): Assignment
    {
        return $this->repository->findWithRelations($assignment);
    }

    public function getOverridesForAssignment(int $assignmentId): \Illuminate\Database\Eloquent\Collection
    {
        if ($this->overrideRepository === null) {
            return new \Illuminate\Database\Eloquent\Collection();
        }
        
        return $this->overrideRepository->getOverridesForAssignment($assignmentId);
    }

    /**
     * Check if a student has completed all prerequisites for an assignment.
     * Takes into account any prerequisite overrides granted to the student.
     *
     * Requirements: 2.1, 2.2, 2.3, 2.4, 2.6, 24.1
     */
    public function checkPrerequisites(int $assignmentId, int $studentId): PrerequisiteCheckResult
    {
        $assignment = $this->repository->findWithPrerequisites($assignmentId);
        
        if (!$assignment) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException('Assignment not found');
        }

        if ($assignment->prerequisites->isEmpty()) {
            return PrerequisiteCheckResult::pass();
        }

        // Check if student has a prerequisite override (Requirement 24.1)
        if ($this->hasPrerequisiteOverride($assignmentId, $studentId)) {
            return PrerequisiteCheckResult::pass();
        }

        // Get prerequisites based on scope hierarchy
        $prerequisitesToCheck = $this->getPrerequisitesForScope($assignment);

        if ($prerequisitesToCheck->isEmpty()) {
            return PrerequisiteCheckResult::pass();
        }

        // Check which prerequisites are incomplete (excluding any with specific overrides)
        $incompletePrerequisites = $this->getIncompletePrerequisites(
            $prerequisitesToCheck,
            $studentId,
            $assignmentId
        );

        if ($incompletePrerequisites->isEmpty()) {
            return PrerequisiteCheckResult::pass();
        }

        return PrerequisiteCheckResult::fail($incompletePrerequisites);
    }

    /**
     * Check if a student has an active prerequisite override for an assignment.
     */
    private function hasPrerequisiteOverride(int $assignmentId, int $studentId): bool
    {
        if ($this->overrideRepository === null) {
            return false;
        }

        return $this->overrideRepository->hasActiveOverride(
            $assignmentId,
            $studentId,
            OverrideType::Prerequisite
        );
    }

    /**
     * Get the bypassed prerequisite IDs from an override.
     */
    private function getBypassedPrerequisiteIds(int $assignmentId, int $studentId): array
    {
        if ($this->overrideRepository === null) {
            return [];
        }

        $override = $this->overrideRepository->findActiveOverride(
            $assignmentId,
            $studentId,
            OverrideType::Prerequisite
        );

        if ($override === null) {
            return [];
        }

        return $override->getBypassedPrerequisites() ?? [];
    }

    /**
     * Get prerequisites based on assignment scope hierarchy.
     */
    private function getPrerequisitesForScope(Assignment $assignment): Collection
    {
        $prerequisites = $assignment->prerequisites;
        $scopeType = $assignment->scope_type;

        return match ($scopeType) {
            'lesson' => $this->filterPrerequisitesByLesson($prerequisites, $assignment),
            'unit' => $this->filterPrerequisitesByUnit($prerequisites, $assignment),
            'course' => $prerequisites, // Course scope checks all prerequisites
            default => $prerequisites,
        };
    }

    /**
     * Filter prerequisites to only those within the same lesson.
     */
    private function filterPrerequisitesByLesson(Collection $prerequisites, Assignment $assignment): Collection
    {
        $lessonId = $assignment->assignable_id ?? $assignment->lesson_id;

        return $prerequisites->filter(function ($prereq) use ($lessonId) {
            $prereqLessonId = $prereq->assignable_id ?? $prereq->lesson_id;

            return $prereqLessonId === $lessonId;
        });
    }

    /**
     * Filter prerequisites to those within the same unit (including its lessons).
     */
    private function filterPrerequisitesByUnit(Collection $prerequisites, Assignment $assignment): Collection
    {
        $unitId = $assignment->assignable_id;

        $lessonIds = [];
        if ($this->lessonService !== null) {
            $lessonIds = $this->lessonService->getRepository()
                ->query()
                ->where('unit_id', $unitId)
                ->pluck('id')
                ->toArray();
        }

        return $prerequisites->filter(function ($prereq) use ($unitId, $lessonIds) {
            if ($prereq->assignable_type === \Modules\Schemes\Models\Unit::class
                && $prereq->assignable_id === $unitId) {
                return true;
            }

            $prereqLessonId = $prereq->assignable_id ?? $prereq->lesson_id;

            return in_array($prereqLessonId, $lessonIds, true);
        });
    }

    /**
     * Get incomplete prerequisites for a student.
     * Excludes prerequisites that have been bypassed via override.
     */
    private function getIncompletePrerequisites(Collection $prerequisites, int $studentId, int $assignmentId): Collection
    {
        // Get any specifically bypassed prerequisites from override
        $bypassedIds = $this->getBypassedPrerequisiteIds($assignmentId, $studentId);

        return $prerequisites->filter(function ($prereq) use ($studentId, $bypassedIds) {
            // Skip if this prerequisite is specifically bypassed
            if (in_array($prereq->id, $bypassedIds, true)) {
                return false;
            }

            return ! $this->hasCompletedAssignment($prereq->id, $studentId);
        });
    }

    /**
     * Check if a student has completed an assignment.
     */
    private function hasCompletedAssignment(int $assignmentId, int $studentId): bool
    {
        return $this->submissionRepository->hasCompletedAssignment($assignmentId, $studentId);
    }

    /**
     * Check for circular prerequisite dependencies.
     */
    public function hasCircularDependency(int $assignmentId, int $prerequisiteId): bool
    {
        // Check if adding this prerequisite would create a cycle
        $visited = [];

        return $this->detectCycle($prerequisiteId, $assignmentId, $visited);
    }

    /**
     * Detect cycle in prerequisite graph using DFS.
     */
    private function detectCycle(int $currentId, int $targetId, array &$visited): bool
    {
        if ($currentId === $targetId) {
            return true;
        }

        if (in_array($currentId, $visited, true)) {
            return false;
        }

        $visited[] = $currentId;

        $assignment = $this->repository->findWithPrerequisites($currentId);

        if (! $assignment) {
            return false;
        }

        foreach ($assignment->prerequisites as $prereq) {
            if ($this->detectCycle($prereq->id, $targetId, $visited)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Add a prerequisite to an assignment.
     */
    public function addPrerequisite(int $assignmentId, int $prerequisiteId): void
    {
        if ($assignmentId === $prerequisiteId) {
            throw new \InvalidArgumentException('An assignment cannot be its own prerequisite');
        }

        if ($this->hasCircularDependency($assignmentId, $prerequisiteId)) {
            throw new \InvalidArgumentException('Adding this prerequisite would create a circular dependency');
        }

        $this->repository->attachPrerequisite($assignmentId, $prerequisiteId);
    }

    /**
     * Remove a prerequisite from an assignment.
     */
    public function removePrerequisite(int $assignmentId, int $prerequisiteId): void
    {
        $this->repository->detachPrerequisite($assignmentId, $prerequisiteId);
    }

    /**
     * Grant an override for a student on an assignment.
     * Allows instructors to override prerequisites, attempt limits, or deadlines.
     *
     * Requirements: 24.1, 24.2, 24.3, 24.4
     *
     * @param  int  $assignmentId  The assignment to grant override for
     * @param  int  $studentId  The student receiving the override
     * @param  string  $overrideType  Type of override: 'prerequisite', 'attempts', or 'deadline'
     * @param  string  $reason  Required reason for the override (Requirement 24.4)
     * @param  array  $value  Optional additional data for the override
     * @param  int|null  $grantorId  The instructor granting the override
     * @return Override The created override
     *
     * @throws \InvalidArgumentException If reason is empty or override type is invalid
     */
    public function grantOverride(
        int $assignmentId,
        int $studentId,
        string $overrideType,
        string $reason,
        array $value = [],
        int $grantorId
    ): Override {
        if (empty(trim($reason))) {
            throw new \InvalidArgumentException('A reason is required when granting an override.');
        }

        $type = OverrideType::tryFrom($overrideType);
        if ($type === null) {
            throw new \InvalidArgumentException(
                "Invalid override type '{$overrideType}'. Valid types are: ".implode(', ', OverrideType::values())
            );
        }

        $assignment = $this->repository->findByIdOrFail($assignmentId);

        $validatedValue = $this->validateOverrideValue($type, $value, $assignment);

        if ($this->overrideRepository === null) {
            throw new \RuntimeException('Override repository is not available.');
        }

        $override = $this->overrideRepository->create([
            'assignment_id' => $assignmentId,
            'student_id' => $studentId,
            'grantor_id' => $grantorId,
            'type' => $type->value,
            'reason' => trim($reason),
            'value' => $validatedValue,
            'granted_at' => Carbon::now(),
            'expires_at' => $validatedValue['expires_at'] ?? null,
        ]);

        // Dispatch event for audit logging (Requirements 24.5)
        OverrideGranted::dispatch($override, $grantorId);

        return $override;
    }

    /**
     * Validate and normalize override value based on type.
     */
    private function validateOverrideValue(OverrideType $type, array $value, Assignment $assignment): array
    {
        return match ($type) {
            OverrideType::Prerequisite => $this->validatePrerequisiteOverrideValue($value, $assignment),
            OverrideType::Attempts => $this->validateAttemptsOverrideValue($value),
            OverrideType::Deadline => $this->validateDeadlineOverrideValue($value, $assignment),
        };
    }

    /**
     * Validate prerequisite override value.
     * Can optionally specify which prerequisites to bypass.
     */
    private function validatePrerequisiteOverrideValue(array $value, Assignment $assignment): array
    {
        $bypassedPrerequisites = $value['bypassed_prerequisites'] ?? [];

        // If specific prerequisites are provided, validate they exist
        if (! empty($bypassedPrerequisites)) {
            $validPrerequisiteIds = $assignment->prerequisites()->pluck('id')->toArray();
            $invalidIds = array_diff($bypassedPrerequisites, $validPrerequisiteIds);

            if (! empty($invalidIds)) {
                throw new \InvalidArgumentException(
                    'Invalid prerequisite IDs: '.implode(', ', $invalidIds)
                );
            }
        }

        return [
            'bypassed_prerequisites' => $bypassedPrerequisites,
            'expires_at' => $value['expires_at'] ?? null,
        ];
    }

    /**
     * Validate attempts override value.
     * Requires additional_attempts to be specified.
     */
    private function validateAttemptsOverrideValue(array $value): array
    {
        $additionalAttempts = $value['additional_attempts'] ?? null;

        if ($additionalAttempts === null || ! is_int($additionalAttempts) || $additionalAttempts < 1) {
            throw new \InvalidArgumentException(
                'additional_attempts must be a positive integer when granting an attempts override.'
            );
        }

        return [
            'additional_attempts' => $additionalAttempts,
            'expires_at' => $value['expires_at'] ?? null,
        ];
    }

    /**
     * Validate deadline override value.
     * Requires extended_deadline to be specified.
     */
    private function validateDeadlineOverrideValue(array $value, Assignment $assignment): array
    {
        $extendedDeadline = $value['extended_deadline'] ?? null;

        if ($extendedDeadline === null) {
            throw new \InvalidArgumentException(
                'extended_deadline must be specified when granting a deadline override.'
            );
        }

        // Parse the deadline
        try {
            $deadline = Carbon::parse($extendedDeadline);
        } catch (\Exception $e) {
            throw new \InvalidArgumentException(
                'extended_deadline must be a valid date/time string.'
            );
        }

        // Ensure extended deadline is in the future
        if ($deadline->isPast()) {
            throw new \InvalidArgumentException(
                'extended_deadline must be in the future.'
            );
        }

        return [
            'extended_deadline' => $deadline->toIso8601String(),
            'expires_at' => $value['expires_at'] ?? null,
        ];
    }

    /**
     * Duplicate an assignment with all questions, settings, and configurations.
     * Does NOT copy submissions or grades.
     *
     * Requirements: 25.1, 25.2, 25.4
     *
     * @param  int  $assignmentId  The assignment to duplicate
     * @param  array|null  $overrides  Optional overrides for the duplicated assignment (e.g., new title, new deadline)
     * @return Assignment The newly created duplicated assignment
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException If assignment not found
     */
    public function duplicateAssignment(int $assignmentId, ?array $overrides = null): Assignment
    {
        // Load the original assignment with all related data
        $original = $this->repository->findForDuplication($assignmentId);
        
        if (!$original) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException('Assignment not found');
        }

        // Prepare assignment data for duplication (exclude id, timestamps, and submissions)
        $assignmentData = $this->prepareAssignmentDataForDuplication($original, $overrides);

        // Create the new assignment
        $newAssignment = $this->repository->create($assignmentData);

        // Copy all questions with their settings (Requirement 25.1)
        $this->duplicateQuestions($original, $newAssignment);

        // Copy prerequisite relationships (Requirement 25.1)
        $this->duplicatePrerequisites($original, $newAssignment);

        // Return the new assignment with all relationships loaded
        return $newAssignment->fresh(['questions', 'prerequisites', 'lesson', 'creator']);
    }

    /**
     * Prepare assignment data for duplication.
     * Copies all fields except id, created_at, updated_at.
     * Applies any overrides provided.
     */
    private function prepareAssignmentDataForDuplication(Assignment $original, ?array $overrides): array
    {
        // Fields to copy from the original assignment
        $data = [
            'lesson_id' => $original->lesson_id,
            'assignable_type' => $original->assignable_type,
            'assignable_id' => $original->assignable_id,
            'created_by' => $overrides['created_by'] ?? $original->created_by,
            'title' => $overrides['title'] ?? $original->title.' (Copy)',
            'description' => $overrides['description'] ?? $original->description,
            'type' => $original->type,
            'submission_type' => $original->submission_type?->value ?? $original->getRawOriginal('submission_type'),
            'max_score' => $overrides['max_score'] ?? $original->max_score,
            'available_from' => $overrides['available_from'] ?? $original->available_from,
            'deadline_at' => $overrides['deadline_at'] ?? $original->deadline_at,
            'tolerance_minutes' => $overrides['tolerance_minutes'] ?? $original->tolerance_minutes,
            'max_attempts' => $overrides['max_attempts'] ?? $original->max_attempts,
            'cooldown_minutes' => $overrides['cooldown_minutes'] ?? $original->cooldown_minutes,
            'retake_enabled' => $overrides['retake_enabled'] ?? $original->retake_enabled,
            'review_mode' => $overrides['review_mode'] ?? ($original->review_mode?->value ?? $original->getRawOriginal('review_mode')),
            'randomization_type' => $overrides['randomization_type'] ?? ($original->randomization_type?->value ?? $original->getRawOriginal('randomization_type')),
            'question_bank_count' => $overrides['question_bank_count'] ?? $original->question_bank_count,
            'status' => $overrides['status'] ?? AssignmentStatus::Draft->value, // Default to draft for duplicated assignments
            'allow_resubmit' => $overrides['allow_resubmit'] ?? $original->allow_resubmit,
            'late_penalty_percent' => $overrides['late_penalty_percent'] ?? $original->late_penalty_percent,
        ];

        return $data;
    }

    /**
     * Duplicate all questions from the original assignment to the new assignment.
     * Preserves question order and all settings (options, answer_key, weight, etc.).
     *
     * Requirement 25.1, 25.5
     */
    private function duplicateQuestions(Assignment $original, Assignment $newAssignment): void
    {
        foreach ($original->questions as $question) {
            $newAssignment->questions()->create([
                'type' => $question->type?->value ?? $question->getRawOriginal('type'),
                'content' => $question->content,
                'options' => $question->options,
                'answer_key' => $question->answer_key,
                'weight' => $question->weight,
                'order' => $question->order, // Preserve question order (Requirement 25.5)
                'max_score' => $question->max_score,
                'max_file_size' => $question->max_file_size,
                'allowed_file_types' => $question->allowed_file_types,
                'allow_multiple_files' => $question->allow_multiple_files,
            ]);
        }
    }

    /**
     * Duplicate prerequisite relationships from the original assignment to the new assignment.
     * The new assignment will have the same prerequisites as the original.
     *
     * Requirement 25.1
     */
    private function duplicatePrerequisites(Assignment $original, Assignment $newAssignment): void
    {
        if ($original->prerequisites->isEmpty()) {
            return;
        }

        // Get the IDs of all prerequisites
        $prerequisiteIds = $original->prerequisites->pluck('id')->toArray();

        // Attach the same prerequisites to the new assignment
        $newAssignment->prerequisites()->attach($prerequisiteIds);
    }
}
