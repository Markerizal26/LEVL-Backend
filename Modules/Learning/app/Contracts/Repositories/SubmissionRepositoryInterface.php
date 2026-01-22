<?php

declare(strict_types=1);

namespace Modules\Learning\Contracts\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\Auth\Models\User;
use Modules\Learning\Models\Assignment;
use Modules\Learning\Models\Submission;

interface SubmissionRepositoryInterface
{
    public function listForAssignment(Assignment $assignment, ?User $user = null, array $filters = []): Collection;

    public function findByUserAndAssignment(int $userId, int $assignmentId): ?Submission;

    public function create(array $attributes): Submission;

    public function update(Model $model, array $attributes): Model;

    public function delete(Model $model): bool;

    public function findHighestScore(int $studentId, int $assignmentId): ?Submission;

    public function findByStudentAndAssignment(int $studentId, int $assignmentId): Collection;

    public function countAttempts(int $studentId, int $assignmentId): int;

    public function getLastSubmissionTime(int $studentId, int $assignmentId): ?\Illuminate\Support\Carbon;

    public function search(string $query, array $filters = [], array $options = []): array;

    public function filterByState(string $state): Collection;

    public function filterByScoreRange(float $min, float $max): Collection;

    public function filterByDateRange(string $from, string $to): Collection;
}
