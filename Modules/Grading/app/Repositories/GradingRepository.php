<?php

namespace Modules\Grading\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Modules\Grading\Contracts\Repositories\GradingRepositoryInterface;
use Modules\Grading\Models\Grade;

class GradingRepository implements GradingRepositoryInterface
{
    /**
     * Default eager loading relationships for grades.
     * Prevents N+1 query problems when loading grades with related data.
     * Requirements: 28.5
     */
    protected const DEFAULT_EAGER_LOAD = [
        'submission.user:id,name,email',
        'submission.assignment:id,title',
        'grader:id,name,email',
    ];

    /**
     * Extended eager loading for detailed grade views.
     * Includes submission answers with questions for complete grade data.
     * Requirements: 28.5
     */
    protected const DETAILED_EAGER_LOAD = [
        'submission.user:id,name,email',
        'submission.assignment:id,title,deadline_at,review_mode',
        'submission.answers.question:id,type,content,weight,max_score',
        'grader:id,name,email',
    ];

    public function __construct(private readonly Grade $model) {}

    /**
     * Find a grade by ID with eager loading.
     * Requirements: 28.5
     */
    public function findById(int $id): ?Grade
    {
        return $this->model
            ->with(self::DEFAULT_EAGER_LOAD)
            ->find($id);
    }

    /**
     * Find a grade by ID with all related data for detailed view.
     * Requirements: 28.5
     */
    public function findByIdWithDetails(int $id): ?Grade
    {
        return $this->model
            ->with(self::DETAILED_EAGER_LOAD)
            ->find($id);
    }

    /**
     * Find a grade by submission ID with eager loading.
     * Requirements: 28.5
     */
    public function findBySubmission(int $submissionId): ?Grade
    {
        return $this->model
            ->where('submission_id', $submissionId)
            ->with(self::DEFAULT_EAGER_LOAD)
            ->first();
    }

    /**
     * Find a grade by submission ID with all related data.
     * Requirements: 28.5
     */
    public function findBySubmissionWithDetails(int $submissionId): ?Grade
    {
        return $this->model
            ->where('submission_id', $submissionId)
            ->with(self::DETAILED_EAGER_LOAD)
            ->first();
    }

    /**
     * Paginate grades with eager loading.
     * Requirements: 28.5
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->with(self::DEFAULT_EAGER_LOAD)
            ->orderByDesc('graded_at')
            ->paginate($perPage);
    }

    public function create(array $data): Grade
    {
        $grade = $this->model->create($data);

        return $grade->load(self::DEFAULT_EAGER_LOAD);
    }

    public function update(Grade $grade, array $data): Grade
    {
        $grade->update($data);

        return $grade->fresh()->load(self::DEFAULT_EAGER_LOAD);
    }

    public function delete(Grade $grade): bool
    {
        return $grade->delete();
    }

    /**
     * Find submissions pending manual grading with eager loading.
     * Requirements: 10.1, 28.5
     *
     * @param  array<string, mixed>  $filters  Optional filters for assignment_id, date range
     * @return Collection<int, Grade>
     */
    public function findPendingManualGrading(array $filters = []): Collection
    {
        $query = $this->model
            ->whereHas('submission', function ($q) {
                $q->where('state', 'pending_manual_grading');
            })
            ->with([
                'submission.user:id,name,email',
                'submission.assignment:id,title,deadline_at',
                'submission.answers' => function ($q) {
                    $q->whereNull('score')
                        ->orWhereHas('question', function ($q) {
                            $q->whereIn('type', ['essay', 'file_upload']);
                        });
                },
                'submission.answers.question:id,type,content,weight',
                'grader:id,name,email',
            ])
            ->orderBy('created_at', 'asc');

        // Apply filters
        if (isset($filters['assignment_id'])) {
            $query->whereHas('submission', function ($q) use ($filters) {
                $q->where('assignment_id', $filters['assignment_id']);
            });
        }

        if (isset($filters['student_id'])) {
            $query->whereHas('submission', function ($q) use ($filters) {
                $q->where('user_id', $filters['student_id']);
            });
        }

        return $query->get();
    }

    /**
     * Save draft grades for a submission.
     * Requirements: 11.1, 11.2, 28.5
     */
    public function saveDraft(int $submissionId, array $data): void
    {
        $grade = $this->model->where('submission_id', $submissionId)->first();

        if ($grade) {
            $grade->update(array_merge($data, ['is_draft' => true]));
        } else {
            $this->model->create(array_merge($data, [
                'submission_id' => $submissionId,
                'is_draft' => true,
            ]));
        }
    }

    /**
     * Find grades by user with eager loading.
     * Requirements: 28.5
     */
    public function findByUser(int $userId): Collection
    {
        return $this->model
            ->where('user_id', $userId)
            ->with(self::DEFAULT_EAGER_LOAD)
            ->orderByDesc('graded_at')
            ->get();
    }

    /**
     * Find grades by grader with eager loading.
     * Requirements: 28.5
     */
    public function findByGrader(int $graderId): Collection
    {
        return $this->model
            ->where('graded_by', $graderId)
            ->with(self::DEFAULT_EAGER_LOAD)
            ->orderByDesc('graded_at')
            ->get();
    }

    /**
     * Find released grades with eager loading.
     * Requirements: 28.5
     */
    public function findReleased(): Collection
    {
        return $this->model
            ->whereNotNull('released_at')
            ->with(self::DEFAULT_EAGER_LOAD)
            ->orderByDesc('released_at')
            ->get();
    }

    /**
     * Find unreleased grades with eager loading.
     * Requirements: 28.5
     */
    public function findUnreleased(): Collection
    {
        return $this->model
            ->whereNull('released_at')
            ->where('is_draft', false)
            ->with(self::DEFAULT_EAGER_LOAD)
            ->orderByDesc('graded_at')
            ->get();
    }

    /**
     * Find overridden grades with eager loading.
     * Requirements: 28.5
     */
    public function findOverridden(): Collection
    {
        return $this->model
            ->where('is_override', true)
            ->with(self::DEFAULT_EAGER_LOAD)
            ->orderByDesc('graded_at')
            ->get();
    }
}
