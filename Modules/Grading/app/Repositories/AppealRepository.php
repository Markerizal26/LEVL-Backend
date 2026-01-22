<?php

declare(strict_types=1);

namespace Modules\Grading\Repositories;

use Illuminate\Support\Collection;
use Modules\Grading\Contracts\Repositories\AppealRepositoryInterface;
use Modules\Grading\Enums\AppealStatus;
use Modules\Grading\Models\Appeal;
use Modules\Learning\Models\Assignment;

class AppealRepository implements AppealRepositoryInterface
{
    /**
     * Default eager loading relationships for appeals.
     * Prevents N+1 query problems when loading appeals with related data.
     * Requirements: 28.5
     */
    protected const DEFAULT_EAGER_LOAD = [
        'submission.assignment:id,title,deadline_at',
        'submission.user:id,name,email',
        'student:id,name,email',
        'reviewer:id,name,email',
    ];

    /**
     * Extended eager loading for detailed appeal views.
     * Includes submission answers for complete appeal data.
     * Requirements: 28.5
     */
    protected const DETAILED_EAGER_LOAD = [
        'submission.assignment:id,title,deadline_at,tolerance_minutes',
        'submission.user:id,name,email',
        'submission.answers.question:id,type,content,weight',
        'submission.grade',
        'student:id,name,email',
        'reviewer:id,name,email',
    ];

    public function __construct(private readonly Appeal $model) {}

    /**
     * Create a new appeal with eager loading.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Appeal
    {
        /** @var Appeal */
        $appeal = $this->model->newQuery()->create($data);

        return $appeal->load(self::DEFAULT_EAGER_LOAD);
    }

    /**
     * Update an existing appeal with eager loading.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): Appeal
    {
        /** @var Appeal $appeal */
        $appeal = $this->model->newQuery()->findOrFail($id);
        $appeal->update($data);

        /** @var Appeal */
        return $appeal->fresh()->load(self::DEFAULT_EAGER_LOAD);
    }

    /**
     * Find an appeal by ID with eager loading.
     * Requirements: 28.5
     */
    public function findById(int $id): ?Appeal
    {
        return $this->model
            ->with(self::DEFAULT_EAGER_LOAD)
            ->find($id);
    }

    /**
     * Find an appeal by ID with all related data for detailed view.
     * Requirements: 28.5
     */
    public function findByIdWithDetails(int $id): ?Appeal
    {
        return $this->model
            ->with(self::DETAILED_EAGER_LOAD)
            ->find($id);
    }

    /**
     * Find pending appeals with eager loading.
     * Requirements: 28.5
     *
     * @return Collection<int, Appeal>
     */
    public function findPending(): Collection
    {
        return $this->model
            ->with(self::DEFAULT_EAGER_LOAD)
            ->where('status', AppealStatus::Pending)
            ->orderBy('submitted_at', 'asc')
            ->get();
    }

    /**
     * Find an appeal by submission ID with eager loading.
     * Requirements: 28.5
     */
    public function findBySubmission(int $submissionId): ?Appeal
    {
        return $this->model
            ->with(self::DEFAULT_EAGER_LOAD)
            ->where('submission_id', $submissionId)
            ->first();
    }

    /**
     * Find an appeal by submission ID with all related data.
     * Requirements: 28.5
     */
    public function findBySubmissionWithDetails(int $submissionId): ?Appeal
    {
        return $this->model
            ->with(self::DETAILED_EAGER_LOAD)
            ->where('submission_id', $submissionId)
            ->first();
    }

    /**
     * Find pending appeals for an instructor's assignments with eager loading.
     *
     * This finds all pending appeals for assignments created by the instructor.
     * Requirements: 28.5
     *
     * @return Collection<int, Appeal>
     */
    public function findPendingForInstructor(int $instructorId): Collection
    {
        // Get assignment IDs created by this instructor
        $assignmentIds = Assignment::where('created_by', $instructorId)->pluck('id');

        return $this->model
            ->with(self::DEFAULT_EAGER_LOAD)
            ->where('status', AppealStatus::Pending)
            ->whereHas('submission', function ($query) use ($assignmentIds) {
                $query->whereIn('assignment_id', $assignmentIds);
            })
            ->orderBy('submitted_at', 'asc')
            ->get();
    }

    /**
     * Find appeals by student with eager loading.
     * Requirements: 28.5
     *
     * @return Collection<int, Appeal>
     */
    public function findByStudent(int $studentId): Collection
    {
        return $this->model
            ->with(self::DEFAULT_EAGER_LOAD)
            ->where('student_id', $studentId)
            ->orderByDesc('submitted_at')
            ->get();
    }

    /**
     * Find appeals by reviewer with eager loading.
     * Requirements: 28.5
     *
     * @return Collection<int, Appeal>
     */
    public function findByReviewer(int $reviewerId): Collection
    {
        return $this->model
            ->with(self::DEFAULT_EAGER_LOAD)
            ->where('reviewer_id', $reviewerId)
            ->orderByDesc('decided_at')
            ->get();
    }

    /**
     * Find approved appeals with eager loading.
     * Requirements: 28.5
     *
     * @return Collection<int, Appeal>
     */
    public function findApproved(): Collection
    {
        return $this->model
            ->with(self::DEFAULT_EAGER_LOAD)
            ->where('status', AppealStatus::Approved)
            ->orderByDesc('decided_at')
            ->get();
    }

    /**
     * Find denied appeals with eager loading.
     * Requirements: 28.5
     *
     * @return Collection<int, Appeal>
     */
    public function findDenied(): Collection
    {
        return $this->model
            ->with(self::DEFAULT_EAGER_LOAD)
            ->where('status', AppealStatus::Denied)
            ->orderByDesc('decided_at')
            ->get();
    }
}
