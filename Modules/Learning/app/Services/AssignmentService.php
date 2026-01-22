<?php

declare(strict_types=1);

namespace Modules\Learning\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Learning\Contracts\Repositories\OverrideRepositoryInterface;
use Modules\Learning\Contracts\Repositories\SubmissionRepositoryInterface;
use Modules\Learning\Contracts\Services\AssignmentServiceInterface;
use Modules\Learning\DTOs\PrerequisiteCheckResult;
use Modules\Learning\Enums\AssignmentStatus;
use Modules\Learning\Enums\OverrideType;
use Modules\Learning\Events\OverrideGranted;
use Modules\Learning\Exceptions\AssignmentException;
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
        return DB::transaction(function () use ($data, $createdBy) {
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
        });
    }

    public function update(Assignment $assignment, array $data): Assignment
    {
        return DB::transaction(function () use ($assignment, $data) {
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
        });
    }

    public function publish(Assignment $assignment): Assignment
    {
        return DB::transaction(function () use ($assignment) {
            $wasDraft = $assignment->status === AssignmentStatus::Draft;
            $published = $this->repository->update($assignment, ['status' => AssignmentStatus::Published->value]);
            $freshAssignment = $published->fresh(['lesson', 'creator']);

            if ($wasDraft) {
                \Modules\Learning\Events\AssignmentPublished::dispatch($freshAssignment);
            }

            return $freshAssignment;
        });
    }

    public function unpublish(Assignment $assignment): Assignment
    {
        return DB::transaction(function () use ($assignment) {
            $updated = $this->repository->update($assignment, ['status' => AssignmentStatus::Draft->value]);

            return $updated->fresh(['lesson', 'creator']);
        });
    }

    public function delete(Assignment $assignment): bool
    {
        return DB::transaction(fn() => $this->repository->delete($assignment));
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

        public function checkPrerequisites(int $assignmentId, int $studentId): PrerequisiteCheckResult
    {
        $assignment = $this->repository->findWithPrerequisites($assignmentId);
        
        if (!$assignment) {
            throw AssignmentException::notFound();
        }

        if ($assignment->prerequisites->isEmpty()) {
            return PrerequisiteCheckResult::pass();
        }

        if ($this->hasPrerequisiteOverride($assignmentId, $studentId)) {
            return PrerequisiteCheckResult::pass();
        }

        $prerequisitesToCheck = $this->getPrerequisitesForScope($assignment);

        if ($prerequisitesToCheck->isEmpty()) {
            return PrerequisiteCheckResult::pass();
        }

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

        private function getPrerequisitesForScope(Assignment $assignment): Collection
    {
        $prerequisites = $assignment->prerequisites;
        $scopeType = $assignment->scope_type;

        return match ($scopeType) {
            'lesson' => $this->filterPrerequisitesByLesson($prerequisites, $assignment),
            'unit' => $this->filterPrerequisitesByUnit($prerequisites, $assignment),
            'course' => $prerequisites, 
            default => $prerequisites,
        };
    }

        private function filterPrerequisitesByLesson(Collection $prerequisites, Assignment $assignment): Collection
    {
        $lessonId = $assignment->assignable_id ?? $assignment->lesson_id;

        return $prerequisites->filter(function ($prereq) use ($lessonId) {
            $prereqLessonId = $prereq->assignable_id ?? $prereq->lesson_id;

            return $prereqLessonId === $lessonId;
        });
    }

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

        private function getIncompletePrerequisites(Collection $prerequisites, int $studentId, int $assignmentId): Collection
    {
        $bypassedIds = $this->getBypassedPrerequisiteIds($assignmentId, $studentId);

        return $prerequisites->filter(function ($prereq) use ($studentId, $bypassedIds) {
            if (in_array($prereq->id, $bypassedIds, true)) {
                return false;
            }

            return ! $this->hasCompletedAssignment($prereq->id, $studentId);
        });
    }

        private function hasCompletedAssignment(int $assignmentId, int $studentId): bool
    {
        return $this->submissionRepository->hasCompletedAssignment($assignmentId, $studentId);
    }

        public function hasCircularDependency(int $assignmentId, int $prerequisiteId): bool
    {
        $visited = [];

        return $this->detectCycle($prerequisiteId, $assignmentId, $visited);
    }

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

        public function addPrerequisite(int $assignmentId, int $prerequisiteId): void
    {
        if ($assignmentId === $prerequisiteId) {
            throw new \InvalidArgumentException(__('messages.assignments.cannot_be_own_prerequisite'));
        }

        if ($this->hasCircularDependency($assignmentId, $prerequisiteId)) {
            throw new \InvalidArgumentException(__('messages.assignments.circular_dependency'));
        }

        $this->repository->attachPrerequisite($assignmentId, $prerequisiteId);
    }

        public function removePrerequisite(int $assignmentId, int $prerequisiteId): void
    {
        $this->repository->detachPrerequisite($assignmentId, $prerequisiteId);
    }

        public function grantOverride(
        int $assignmentId,
        int $studentId,
        string $overrideType,
        string $reason,
        array $value = [],
        ?int $grantorId = null
    ): Override {
        if (empty(trim($reason))) {
            throw new \InvalidArgumentException(__('messages.assignments.override_reason_required'));
        }

        $type = OverrideType::tryFrom($overrideType);
        if ($type === null) {
            throw new \InvalidArgumentException(
                __('messages.assignments.invalid_override_type', ['type' => $overrideType, 'valid_types' => implode(', ', OverrideType::values())])
            );
        }

        $assignment = $this->repository->findByIdOrFail($assignmentId);

        $validatedValue = $this->validateOverrideValue($type, $value, $assignment);

        if ($this->overrideRepository === null) {
            throw new \RuntimeException(__('messages.assignments.override_repository_unavailable'));
        }

        return DB::transaction(function () use ($assignmentId, $studentId, $grantorId, $type, $reason, $validatedValue) {
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

            OverrideGranted::dispatch($override, $grantorId);

            return $override->load(['student', 'grantor']);
        });
    }

        private function validateOverrideValue(OverrideType $type, array $value, Assignment $assignment): array
    {
        return match ($type) {
            OverrideType::Prerequisite => $this->validatePrerequisiteOverrideValue($value, $assignment),
            OverrideType::Attempts => $this->validateAttemptsOverrideValue($value),
            OverrideType::Deadline => $this->validateDeadlineOverrideValue($value, $assignment),
        };
    }

        private function validatePrerequisiteOverrideValue(array $value, Assignment $assignment): array
    {
        $bypassedPrerequisites = $value['bypassed_prerequisites'] ?? [];

        if (! empty($bypassedPrerequisites)) {
            $validPrerequisiteIds = $assignment->prerequisites()->pluck('id')->toArray();
            $invalidIds = array_diff($bypassedPrerequisites, $validPrerequisiteIds);

            if (! empty($invalidIds)) {
                throw new \InvalidArgumentException(
                    __('messages.assignments.invalid_prerequisites_list', ['ids' => implode(', ', $invalidIds)])
                );
            }
        }

        return [
            'bypassed_prerequisites' => $bypassedPrerequisites,
            'expires_at' => $value['expires_at'] ?? null,
        ];
    }

        private function validateAttemptsOverrideValue(array $value): array
    {
        $additionalAttempts = $value['additional_attempts'] ?? null;

        if ($additionalAttempts === null || ! is_int($additionalAttempts) || $additionalAttempts < 1) {
            throw new \InvalidArgumentException(
                __('messages.assignments.invalid_additional_attempts')
            );
        }

        return [
            'additional_attempts' => $additionalAttempts,
            'expires_at' => $value['expires_at'] ?? null,
        ];
    }

        private function validateDeadlineOverrideValue(array $value, Assignment $assignment): array
    {
        $extendedDeadline = $value['extended_deadline'] ?? null;

        if ($extendedDeadline === null) {
            throw new \InvalidArgumentException(
                __('messages.assignments.deadline_extension_required')
            );
        }

        try {
            $deadline = Carbon::parse($extendedDeadline);
        } catch (\Exception $e) {
            throw new \InvalidArgumentException(
                __('messages.assignments.deadline_extension_invalid_format')
            );
        }

        if ($deadline->isPast()) {
            throw new \InvalidArgumentException(
                __('messages.assignments.invalid_deadline_extension')
            );
        }

        return [
            'extended_deadline' => $deadline->toIso8601String(),
            'expires_at' => $value['expires_at'] ?? null,
        ];
    }

        public function duplicateAssignment(int $assignmentId, ?array $overrides = null): Assignment
    {
        return DB::transaction(function () use ($assignmentId, $overrides) {
            $original = $this->repository->findForDuplication($assignmentId);
            
            if (!$original) {
                throw AssignmentException::notFound();
            }

            $assignmentData = $this->prepareAssignmentDataForDuplication($original, $overrides);

            $newAssignment = $this->repository->create($assignmentData);

            $this->duplicateQuestions($original, $newAssignment);

            $this->duplicatePrerequisites($original, $newAssignment);

            return $newAssignment->fresh(['questions', 'prerequisites', 'lesson', 'creator']);
        });
    }

        private function prepareAssignmentDataForDuplication(Assignment $original, ?array $overrides): array
    {
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
            'status' => $overrides['status'] ?? AssignmentStatus::Draft->value, 
            'allow_resubmit' => $overrides['allow_resubmit'] ?? $original->allow_resubmit,
            'late_penalty_percent' => $overrides['late_penalty_percent'] ?? $original->late_penalty_percent,
        ];

        return $data;
    }

        private function duplicateQuestions(Assignment $original, Assignment $newAssignment): void
    {
        foreach ($original->questions as $question) {
            $newAssignment->questions()->create([
                'type' => $question->type?->value ?? $question->getRawOriginal('type'),
                'content' => $question->content,
                'options' => $question->options,
                'answer_key' => $question->answer_key,
                'weight' => $question->weight,
                'order' => $question->order, 
                'max_score' => $question->max_score,
                'max_file_size' => $question->max_file_size,
                'allowed_file_types' => $question->allowed_file_types,
                'allow_multiple_files' => $question->allow_multiple_files,
            ]);
        }
    }

        private function duplicatePrerequisites(Assignment $original, Assignment $newAssignment): void
    {
        if ($original->prerequisites->isEmpty()) {
            return;
        }

        $prerequisiteIds = $original->prerequisites->pluck('id')->toArray();

        $newAssignment->prerequisites()->attach($prerequisiteIds);
    }
}
