<?php

declare(strict_types=1);

namespace Modules\Grading\Contracts\Repositories;

use Illuminate\Support\Collection;
use Modules\Grading\Models\Appeal;

interface AppealRepositoryInterface
{
    /**
     * Create a new appeal.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Appeal;

    /**
     * Update an existing appeal.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): Appeal;

    /**
     * Find an appeal by ID.
     */
    public function findById(int $id): ?Appeal;

    /**
     * Find pending appeals.
     *
     * @return Collection<int, Appeal>
     */
    public function findPending(): Collection;

    /**
     * Find an appeal by submission ID.
     */
    public function findBySubmission(int $submissionId): ?Appeal;

    /**
     * Find pending appeals for an instructor's assignments.
     *
     * @return Collection<int, Appeal>
     */
    public function findPendingForInstructor(int $instructorId): Collection;
}
