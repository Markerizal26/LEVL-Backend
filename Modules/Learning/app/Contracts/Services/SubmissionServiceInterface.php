<?php

declare(strict_types=1);

namespace Modules\Learning\Contracts\Services;

use Illuminate\Support\Collection;
use Modules\Auth\Models\User;
use Modules\Learning\Enums\OverrideType;
use Modules\Learning\Models\Assignment;
use Modules\Learning\Models\Submission;

interface SubmissionServiceInterface
{
    public function listForAssignment(Assignment $assignment, User $user, array $filters = []): Collection;

    public function listByAssignment(Assignment $assignment, array $filters = []);

    public function create(Assignment $assignment, int $userId, array $data): Submission;

    public function update(Submission $submission, array $data): Submission;

    public function delete(Submission $submission): bool;

    public function grade(Submission $submission, int $score, int $gradedBy, ?string $feedback = null): Submission;

    public function getHighestScoreSubmission(int $assignmentId, int $studentId): ?Submission;

    public function startSubmission(int $assignmentId, int $studentId): Submission;

    public function submitAnswers(int $submissionId, array $answers): Submission;

    public function checkAttemptLimits(Assignment $assignment, int $studentId): array;

    public function checkAttemptLimitsWithOverride(Assignment $assignment, int $studentId): array;

    public function checkCooldownPeriod(Assignment $assignment, int $studentId): array;

    public function checkDeadlineWithOverride(Assignment $assignment, int $studentId): bool;

    public function hasActiveOverride(int $assignmentId, int $studentId, OverrideType $type): bool;

    public function getSubmissionsWithHighestMarked(int $assignmentId, int $studentId): Collection;

    public function searchSubmissions(string $query, array $filters = [], array $options = []): array;
}
