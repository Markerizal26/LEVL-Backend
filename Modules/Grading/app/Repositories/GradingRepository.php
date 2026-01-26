<?php

declare(strict_types=1);

namespace Modules\Grading\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Modules\Grading\Contracts\Repositories\GradingRepositoryInterface;
use Modules\Grading\Models\Grade;

class GradingRepository implements GradingRepositoryInterface
{
    protected const DEFAULT_EAGER_LOAD = [
        'submission.user:id,name,email',
        'submission.assignment:id,title',
        'grader:id,name,email',
    ];

    protected const DETAILED_EAGER_LOAD = [
        'submission.user:id,name,email',
        'submission.assignment:id,title,deadline_at,review_mode',
        'submission.answers.question:id,type,content,weight,max_score',
        'grader:id,name,email',
    ];

    protected const CACHE_TTL = 3600;

    public function __construct(private readonly Grade $model) {}

    public function findById(int $id): ?Grade
    {
        return \Illuminate\Support\Facades\Cache::tags(['grades'])
            ->remember("grade:{$id}", self::CACHE_TTL, function () use ($id) {
                return $this->model
                    ->with(self::DEFAULT_EAGER_LOAD)
                    ->find($id);
            });
    }

    public function findByIdWithDetails(int $id): ?Grade
    {
        return \Illuminate\Support\Facades\Cache::tags(['grades'])
            ->remember("grade:{$id}:details", self::CACHE_TTL, function () use ($id) {
                return $this->model
                    ->with(self::DETAILED_EAGER_LOAD)
                    ->find($id);
            });
    }

    public function findBySubmission(int $submissionId): ?Grade
    {
        return \Illuminate\Support\Facades\Cache::tags(['grades'])
            ->remember("grade:submission:{$submissionId}", self::CACHE_TTL, function () use ($submissionId) {
                return $this->model
                    ->where('submission_id', $submissionId)
                    ->with(self::DEFAULT_EAGER_LOAD)
                    ->first();
            });
    }

    public function findBySubmissionWithDetails(int $submissionId): ?Grade
    {
        return \Illuminate\Support\Facades\Cache::tags(['grades'])
            ->remember("grade:submission:{$submissionId}:details", self::CACHE_TTL, function () use ($submissionId) {
                return $this->model
                    ->where('submission_id', $submissionId)
                    ->with(self::DETAILED_EAGER_LOAD)
                    ->first();
            });
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        // Pagination is hard to cache effectively per page without stale data, skipping for now
        // or short TTL if needed.
        return $this->model
            ->with(self::DEFAULT_EAGER_LOAD)
            ->orderByDesc('graded_at')
            ->paginate($perPage);
    }

    public function create(array $data): Grade
    {
        $grade = $this->model->create($data);
        
        $this->invalidateCache($grade);

        return $grade->load(self::DEFAULT_EAGER_LOAD);
    }

    public function update(Grade $grade, array $data): Grade
    {
        $grade->update($data);
        
        $this->invalidateCache($grade);

        return $grade->fresh()->load(self::DEFAULT_EAGER_LOAD);
    }

    public function delete(Grade $grade): bool
    {
        $this->invalidateCache($grade);
        return $grade->delete();
    }

    public function findPendingManualGrading(array $filters = []): Collection
    {
        // Complex query with filters, keeping live for now or short cache
        return \Spatie\QueryBuilder\QueryBuilder::for($this->model, new \Illuminate\Http\Request($filters))
            ->whereHas('submission', function ($q) {
                $q->where('state', 'pending_manual_grading');
            })
            ->allowedFilters([
                \Spatie\QueryBuilder\AllowedFilter::callback('assignment_id', function ($query, $value) {
                    $query->whereHas('submission', function ($q) use ($value) {
                        $q->where('assignment_id', $value);
                    });
                }),
                \Spatie\QueryBuilder\AllowedFilter::callback('student_id', function ($query, $value) {
                    $query->whereHas('submission', function ($q) use ($value) {
                        $q->where('user_id', $value);
                    });
                }),
            ])
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
            ->defaultSort('created_at')
            ->get();
    }

    public function saveDraft(int $submissionId, array $data): void
    {
        $grade = $this->model->where('submission_id', $submissionId)->first();

        if ($grade) {
            $grade->update(array_merge($data, ['is_draft' => true]));
            $this->invalidateCache($grade);
        } else {
            $grade = $this->model->create(array_merge($data, [
                'submission_id' => $submissionId,
                'is_draft' => true,
            ]));
            $this->invalidateCache($grade);
        }
    }

    public function findByUser(int $userId): Collection
    {
        return \Illuminate\Support\Facades\Cache::tags(['grades'])
            ->remember("grades:user:{$userId}", self::CACHE_TTL, function () use ($userId) {
                return $this->model
                    ->where('user_id', $userId)
                    ->with(self::DEFAULT_EAGER_LOAD)
                    ->orderByDesc('graded_at')
                    ->get();
            });
    }

    public function findByGrader(int $graderId): Collection
    {
        return $this->model
            ->where('graded_by', $graderId)
            ->with(self::DEFAULT_EAGER_LOAD)
            ->orderByDesc('graded_at')
            ->get();
    }

    public function findReleased(): Collection
    {
        return $this->model
            ->whereNotNull('released_at')
            ->with(self::DEFAULT_EAGER_LOAD)
            ->orderByDesc('released_at')
            ->get();
    }

    public function findUnreleased(): Collection
    {
        return $this->model
            ->whereNull('released_at')
            ->where('is_draft', false)
            ->with(self::DEFAULT_EAGER_LOAD)
            ->orderByDesc('graded_at')
            ->get();
    }

    public function findOverridden(): Collection
    {
        return $this->model
            ->where('is_override', true)
            ->with(self::DEFAULT_EAGER_LOAD)
            ->orderByDesc('graded_at')
            ->get();
    }

    /**
     * Invalidate all relevant caches for a grade.
     */
    protected function invalidateCache(Grade $grade): void
    {
        $cache = \Illuminate\Support\Facades\Cache::tags(['grades']);

        $cache->forget("grade:{$grade->id}");
        $cache->forget("grade:{$grade->id}:details");
        
        if ($grade->submission_id) {
            $cache->forget("grade:submission:{$grade->submission_id}");
            $cache->forget("grade:submission:{$grade->submission_id}:details");
        }
        
        if ($grade->user_id) {
            $cache->forget("grades:user:{$grade->user_id}");
        } elseif ($grade->submission) {
            // Try to load relation if not set on model
             $cache->forget("grades:user:{$grade->submission->user_id}");
        }
    }
}
