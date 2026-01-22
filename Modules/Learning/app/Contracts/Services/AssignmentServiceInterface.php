<?php

declare(strict_types=1);

namespace Modules\Learning\Contracts\Services;

use Modules\Learning\DTOs\PrerequisiteCheckResult;
use Modules\Learning\Models\Assignment;
use Modules\Learning\Models\Override;
use Modules\Schemes\Models\Lesson;

interface AssignmentServiceInterface
{
    public function listByLesson(Lesson $lesson, array $filters = []);

    public function create(array $data, int $createdBy): Assignment;

    public function update(Assignment $assignment, array $data): Assignment;

    public function publish(Assignment $assignment): Assignment;

    public function unpublish(Assignment $assignment): Assignment;

    public function delete(Assignment $assignment): bool;

    /**
     * Check if a student has completed all prerequisites for an assignment.
     * Takes into account any prerequisite overrides granted to the student.
     *
     * @param  int  $assignmentId  The assignment to check prerequisites for
     * @param  int  $studentId  The student to check
     * @return PrerequisiteCheckResult Result indicating if prerequisites are met
     */
    public function checkPrerequisites(int $assignmentId, int $studentId): PrerequisiteCheckResult;

    /**
     * Grant an override for a student on an assignment.
     * Allows instructors to override prerequisites, attempt limits, or deadlines.
     *
     * Requirements: 24.1, 24.2, 24.3, 24.4
     *
     * @param  int  $assignmentId  The assignment to grant override for
     * @param  int  $studentId  The student receiving the override
     * @param  string  $overrideType  Type of override: 'prerequisite', 'attempts', or 'deadline'
     * @param  string  $reason  Required reason for the override
     * @param  array  $value  Optional additional data for the override (e.g., extended deadline, additional attempts)
     * @param  int|null  $grantorId  The instructor granting the override (defaults to authenticated user)
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
        ?int $grantorId = null
    ): Override;

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
    public function duplicateAssignment(int $assignmentId, ?array $overrides = null): Assignment;
}
