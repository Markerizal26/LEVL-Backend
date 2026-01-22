<?php

declare(strict_types=1);

namespace Modules\Learning\Repositories;

use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Laravel\Scout\Builder as ScoutBuilder;
use Modules\Auth\Models\User;
use Modules\Learning\Contracts\Repositories\SubmissionRepositoryInterface;
use Modules\Learning\Enums\SubmissionState;
use Modules\Learning\Models\Assignment;
use Modules\Learning\Models\Submission;

class SubmissionRepository extends BaseRepository implements SubmissionRepositoryInterface
{
    /**
     * Default eager loading relationships for submissions.
     * Prevents N+1 query problems when loading submissions with related data.
     * Requirements: 28.5
     */
    protected const DEFAULT_EAGER_LOAD = [
        'user:id,name,email',
        'assignment:id,title,deadline_at,tolerance_minutes,review_mode',
        'grade',
    ];

    /**
     * Extended eager loading for detailed submission views.
     * Includes answers with questions for complete submission data.
     * Requirements: 28.5
     */
    protected const DETAILED_EAGER_LOAD = [
        'user:id,name,email',
        'assignment:id,title,deadline_at,tolerance_minutes,review_mode',
        'answers.question',
        'grade.grader:id,name,email',
        'appeal.reviewer:id,name,email',
    ];

    protected function model(): string
    {
        return Submission::class;
    }

    protected array $allowedFilters = ['assignment_id', 'user_id', 'status'];

    protected array $allowedSorts = ['id', 'created_at', 'submitted_at', 'graded_at'];

    protected string $defaultSort = '-created_at';

    protected array $with = ['user', 'enrollment'];

    /**
     * List submissions for an assignment with eager loading.
     * Requirements: 28.5
     */
    public function listForAssignment(Assignment $assignment, ?User $user = null, array $filters = []): Collection
    {
        $query = Submission::query()
            ->where('assignment_id', $assignment->id)
            ->with([
                'user:id,name,email',
                'enrollment:id,status',
                'files',
                'grade.grader:id,name,email',
                'answers.question:id,type,content,weight',
            ]);

        if ($user && $user->hasRole('Student')) {
            $query->where('user_id', $user->id);
        } elseif (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    public function create(array $attributes): Submission
    {
        return Submission::create($attributes);
    }

    public function update(Model|Submission $model, array $attributes): Model|Submission
    {
        $model->fill($attributes)->save();

        return $model;
    }

    public function updateSubmission(Submission $submission, array $attributes): Submission
    {
        $submission->fill($attributes)->save();

        return $submission;
    }

    public function delete(Model|Submission $model): bool
    {
        return $model->delete();
    }

    public function hasCompletedAssignment(int $assignmentId, int $studentId): bool
    {
        return Submission::where('assignment_id', $assignmentId)
            ->where('user_id', $studentId)
            ->where('status', \Modules\Learning\Enums\SubmissionStatus::Graded)
            ->exists();
    }

    /**
     * Get the latest committed submission with eager loading.
     * Requirements: 28.5
     */
    public function latestCommittedSubmission(Assignment $assignment, int $userId): ?Submission
    {
        return Submission::query()
            ->where('assignment_id', $assignment->id)
            ->where('user_id', $userId)
            ->whereIn('status', ['submitted', 'late', 'graded'])
            ->with(self::DEFAULT_EAGER_LOAD)
            ->latest('id')
            ->first();
    }

    /**
     * Find the highest scoring submission for a student on an assignment.
     * Requirements: 8.4, 22.1, 22.2, 28.5
     */
    public function findHighestScore(int $studentId, int $assignmentId): ?Submission
    {
        return Submission::query()
            ->where('assignment_id', $assignmentId)
            ->where('user_id', $studentId)
            ->whereNotNull('score')
            ->whereNotIn('state', [SubmissionState::InProgress->value])
            ->with(self::DEFAULT_EAGER_LOAD)
            ->orderByDesc('score')
            ->first();
    }

    /**
     * Find a submission by user and assignment with eager loading.
     * Requirements: 28.5
     */
    public function findByUserAndAssignment(int $userId, int $assignmentId): ?Submission
    {
        return Submission::query()
            ->where('assignment_id', $assignmentId)
            ->where('user_id', $userId)
            ->with(self::DEFAULT_EAGER_LOAD)
            ->first();
    }

    /**
     * Find all submissions for a student on an assignment with eager loading.
     * Requirements: 22.1, 28.5
     */
    public function findByStudentAndAssignment(int $studentId, int $assignmentId): Collection
    {
        return Submission::query()
            ->where('assignment_id', $assignmentId)
            ->where('user_id', $studentId)
            ->whereNotIn('state', [SubmissionState::InProgress->value])
            ->with(self::DEFAULT_EAGER_LOAD)
            ->orderByDesc('submitted_at')
            ->get();
    }

    /**
     * Find a submission with all related data for detailed view.
     * Includes answers with questions for complete submission data.
     * Requirements: 28.5
     */
    public function findWithDetails(int $submissionId): ?Submission
    {
        return Submission::query()
            ->where('id', $submissionId)
            ->with(self::DETAILED_EAGER_LOAD)
            ->first();
    }

    /**
     * Find a submission with answers and questions for grading.
     * Requirements: 28.5
     */
    public function findWithAnswers(int $submissionId): ?Submission
    {
        return Submission::query()
            ->where('id', $submissionId)
            ->with([
                'user:id,name,email',
                'assignment:id,title,deadline_at,tolerance_minutes,review_mode',
                'answers.question:id,type,content,options,answer_key,weight,max_score',
                'grade',
            ])
            ->first();
    }

    /**
     * Count attempts for a student on an assignment.
     * Requirements: 7.3
     */
    public function countAttempts(int $studentId, int $assignmentId): int
    {
        return Submission::query()
            ->where('assignment_id', $assignmentId)
            ->where('user_id', $studentId)
            ->whereNotIn('state', [SubmissionState::InProgress->value])
            ->count();
    }

    /**
     * Get the last submission time for a student on an assignment.
     * Requirements: 7.4
     */
    public function getLastSubmissionTime(int $studentId, int $assignmentId): ?Carbon
    {
        $submission = Submission::query()
            ->where('assignment_id', $assignmentId)
            ->where('user_id', $studentId)
            ->whereNotNull('submitted_at')
            ->orderByDesc('submitted_at')
            ->first();

        return $submission?->submitted_at;
    }

    /**
     * Find submissions pending manual grading with eager loading.
     * Requirements: 10.1, 28.5
     *
     * @param  array<string, mixed>  $filters  Optional filters for assignment_id, date range
     * @return Collection<int, Submission>
     */
    public function findPendingManualGrading(array $filters = []): Collection
    {
        $query = Submission::query()
            ->where('state', SubmissionState::PendingManualGrading->value)
            ->with([
                'user:id,name,email',
                'assignment:id,title,deadline_at',
                'answers' => function ($q) {
                    $q->whereNull('score')
                        ->orWhereHas('question', function ($q) {
                            $q->whereIn('type', ['essay', 'file_upload']);
                        });
                },
                'answers.question:id,type,content,weight',
            ])
            ->orderBy('submitted_at', 'asc');

        // Apply filters
        if (isset($filters['assignment_id'])) {
            $query->where('assignment_id', $filters['assignment_id']);
        }

        if (isset($filters['student_id'])) {
            $query->where('user_id', $filters['student_id']);
        }

        if (isset($filters['date_from'])) {
            $query->where('submitted_at', '>=', Carbon::parse($filters['date_from'])->startOfDay());
        }

        if (isset($filters['date_to'])) {
            $query->where('submitted_at', '<=', Carbon::parse($filters['date_to'])->endOfDay());
        }

        return $query->get();
    }

    /**
     * Search submissions using Laravel Scout with Meilisearch.
     *
     * Supports full-text search by student name or email, with additional filters
     * for state, score range, date range, and assignment.
     *
     * Requirements: 27.1, 27.2, 27.3, 27.4, 27.6, 28.5
     *
     * @param  string  $query  Search query for student name or email
     * @param  array<string, mixed>  $filters  Optional filters:
     *                                         - state: string (submission state)
     *                                         - score_min: float (minimum score)
     *                                         - score_max: float (maximum score)
     *                                         - date_from: string (start date Y-m-d)
     *                                         - date_to: string (end date Y-m-d)
     *                                         - assignment_id: int (filter by assignment)
     * @param  array<string, mixed>  $options  Optional pagination/sorting options:
     *                                         - page: int (page number, default 1)
     *                                         - per_page: int (items per page, default 15)
     *                                         - sort_by: string (field to sort by)
     *                                         - sort_direction: string (asc or desc)
     * @return array{
     *     data: Collection,
     *     total: int,
     *     per_page: int,
     *     current_page: int,
     *     last_page: int
     * }
     */
    public function search(string $query, array $filters = [], array $options = []): array
    {
        // Default options
        $page = (int) ($options['page'] ?? 1);
        $perPage = (int) ($options['per_page'] ?? 15);
        $sortBy = $options['sort_by'] ?? 'submitted_at';
        $sortDirection = strtolower($options['sort_direction'] ?? 'desc');

        // Validate sort direction
        if (! in_array($sortDirection, ['asc', 'desc'])) {
            $sortDirection = 'desc';
        }

        // Build Scout search query
        $searchBuilder = Submission::search($query);

        // Apply filters using Scout's where clauses
        $searchBuilder = $this->applyScoutFilters($searchBuilder, $filters);

        // Get paginated results from Scout
        $paginatedResults = $searchBuilder->paginate($perPage, 'page', $page);

        // Get the IDs from Scout results
        $ids = collect($paginatedResults->items())->pluck('id')->toArray();

        // If no results, return empty result set
        if (empty($ids)) {
            return [
                'data' => new Collection,
                'total' => 0,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => 1,
            ];
        }

        // Fetch full models with relationships using eager loading to prevent N+1 queries
        // Requirements: 28.5
        $submissions = Submission::query()
            ->whereIn('id', $ids)
            ->with([
                'user:id,name,email',
                'assignment:id,title,deadline_at',
                'grade.grader:id,name,email',
                'answers.question:id,type,content,weight',
            ])
            ->get()
            ->sortBy(function ($submission) use ($ids) {
                // Maintain the order from Scout results
                return array_search($submission->id, $ids);
            })
            ->values();

        // Apply database-level sorting if specified (overrides Scout order)
        if (isset($options['sort_by'])) {
            $submissions = $sortDirection === 'asc'
                ? $submissions->sortBy($sortBy)->values()
                : $submissions->sortByDesc($sortBy)->values();
        }

        return [
            'data' => $submissions,
            'total' => $paginatedResults->total(),
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => $paginatedResults->lastPage(),
        ];
    }

    /**
     * Apply filters to Scout search builder.
     *
     * @param  ScoutBuilder  $builder  The Scout search builder
     * @param  array<string, mixed>  $filters  The filters to apply
     */
    protected function applyScoutFilters(ScoutBuilder $builder, array $filters): ScoutBuilder
    {
        // Filter by state (Requirements: 27.2)
        if (isset($filters['state']) && $filters['state'] !== '') {
            $builder->where('state', $filters['state']);
        }

        // Filter by assignment_id
        if (isset($filters['assignment_id'])) {
            $builder->where('assignment_id', (int) $filters['assignment_id']);
        }

        // Filter by score range (Requirements: 27.3)
        // Note: Meilisearch supports numeric filtering with >= and <=
        if (isset($filters['score_min'])) {
            $builder->where('score', '>=', (float) $filters['score_min']);
        }

        if (isset($filters['score_max'])) {
            $builder->where('score', '<=', (float) $filters['score_max']);
        }

        // Filter by date range (Requirements: 27.4)
        // Convert dates to timestamps for Meilisearch filtering
        if (isset($filters['date_from'])) {
            $fromTimestamp = Carbon::parse($filters['date_from'])->startOfDay()->timestamp;
            $builder->where('submitted_at', '>=', $fromTimestamp);
        }

        if (isset($filters['date_to'])) {
            $toTimestamp = Carbon::parse($filters['date_to'])->endOfDay()->timestamp;
            $builder->where('submitted_at', '<=', $toTimestamp);
        }

        return $builder;
    }

    /**
     * Filter submissions by state with eager loading.
     *
     * Requirements: 27.2, 28.5
     *
     * @param  string  $state  The submission state to filter by
     * @return Collection<int, Submission>
     */
    public function filterByState(string $state): Collection
    {
        return Submission::query()
            ->where('state', $state)
            ->with([
                'user:id,name,email',
                'assignment:id,title,deadline_at',
                'grade.grader:id,name,email',
                'answers.question:id,type,content,weight',
            ])
            ->orderByDesc('submitted_at')
            ->get();
    }

    /**
     * Filter submissions by score range with eager loading.
     *
     * Requirements: 27.3, 28.5
     *
     * @param  float  $min  Minimum score (inclusive)
     * @param  float  $max  Maximum score (inclusive)
     * @return Collection<int, Submission>
     */
    public function filterByScoreRange(float $min, float $max): Collection
    {
        return Submission::query()
            ->whereNotNull('score')
            ->where('score', '>=', $min)
            ->where('score', '<=', $max)
            ->with([
                'user:id,name,email',
                'assignment:id,title,deadline_at',
                'grade.grader:id,name,email',
                'answers.question:id,type,content,weight',
            ])
            ->orderByDesc('submitted_at')
            ->get();
    }

    /**
     * Filter submissions by submission date range with eager loading.
     *
     * Requirements: 27.4, 28.5
     *
     * @param  string  $from  Start date (Y-m-d format)
     * @param  string  $to  End date (Y-m-d format)
     * @return Collection<int, Submission>
     */
    public function filterByDateRange(string $from, string $to): Collection
    {
        $fromDate = Carbon::parse($from)->startOfDay();
        $toDate = Carbon::parse($to)->endOfDay();

        return Submission::query()
            ->whereNotNull('submitted_at')
            ->whereBetween('submitted_at', [$fromDate, $toDate])
            ->with([
                'user:id,name,email',
                'assignment:id,title,deadline_at',
                'grade.grader:id,name,email',
                'answers.question:id,type,content,weight',
            ])
            ->orderByDesc('submitted_at')
            ->get();
    }
}
