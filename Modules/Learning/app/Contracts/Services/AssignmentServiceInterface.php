<?php

declare(strict_types=1);

namespace Modules\Learning\Contracts\Services;

use Modules\Learning\DTOs\PrerequisiteCheckResult;
use Modules\Learning\Models\Assignment;
use Modules\Learning\Models\Override;
use Modules\Schemes\Models\Lesson;

interface AssignmentServiceInterface
{
    public function list(\Modules\Schemes\Models\Course $course, array $filters = []);

    public function listByLesson(Lesson $lesson, array $filters = []);

    public function listByUnit(\Modules\Schemes\Models\Unit $unit, array $filters = []);

    public function listByCourse(\Modules\Schemes\Models\Course $course, array $filters = []);

    public function create(array $data, int $createdBy): Assignment;

    public function update(Assignment $assignment, array $data): Assignment;

    public function publish(Assignment $assignment): Assignment;

    public function unpublish(Assignment $assignment): Assignment;

    public function delete(Assignment $assignment): bool;

    public function checkPrerequisites(int $assignmentId, int $studentId): PrerequisiteCheckResult;

    public function grantOverride(
        int $assignmentId,
        int $studentId,
        string $overrideType,
        string $reason,
        array $value = [],
        ?int $grantorId = null
    ): Override;

    public function duplicateAssignment(int $assignmentId, ?array $overrides = null): Assignment;
}
