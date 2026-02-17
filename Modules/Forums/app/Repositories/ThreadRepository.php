<?php

declare(strict_types=1);

namespace Modules\Forums\Repositories;

use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Modules\Forums\Contracts\Repositories\ThreadRepositoryInterface;
use Modules\Forums\Models\Reply;
use Modules\Forums\Models\Thread;
use Spatie\QueryBuilder\AllowedFilter;

class ThreadRepository extends BaseRepository implements ThreadRepositoryInterface
{
    protected function model(): string
    {
        return Thread::class;
    }

    public function query(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::query()->withIsMentioned();
    }

    protected array $allowedFilters = ['author_id', 'pinned', 'resolved', 'closed', 'is_mentioned'];

    protected array $allowedSorts = ['id', 'created_at', 'last_activity_at', 'is_pinned', 'views_count', 'replies_count'];

    protected string $defaultSort = '-last_activity_at';

    protected array $with = ['author.media'];

    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return cache()->tags(['forums', 'threads'])->remember(
            "forums:threads:paginate:{$perPage}:" . request('page', 1) . ":" . md5(json_encode($filters)),
            300,
            function () use ($filters, $perPage) {
                $query = $this->query();

                $query = $this->applyFilters($query, $filters);
                $query = $this->applySorting($query, $filters);

                $perPage = $filters['per_page'] ?? $perPage;
                $perPage = max(1, min($perPage, 100));

                return $query->paginate($perPage);
            }
        );
    }

    public function paginateByCourse(int $courseId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->getThreadsByCourse($courseId, $filters);
    }

    public function find(int $id): ?Thread
    {
        return Thread::find($id);
    }

    public function findWithReplies(int $id): ?Thread
    {
        return $this->findWithRelations($id);
    }

    public function create(array $data): Thread
    {
        $thread = Thread::create($data);
        $thread->updateLastActivity();

        return $thread;
    }

    public function pin(Thread $thread): Thread
    {
        $thread->is_pinned = true;
        $thread->save();

        return $thread;
    }

    public function unpin(Thread $thread): Thread
    {
        $thread->is_pinned = false;
        $thread->save();

        return $thread;
    }

    public function lock(Thread $thread): Thread
    {
        $thread->is_closed = true;
        $thread->save();

        return $thread;
    }

    public function unlock(Thread $thread): Thread
    {
        $thread->is_closed = false;
        $thread->save();

        return $thread;
    }

    public function getThreadsByCourse(int $courseId, array $filters = []): LengthAwarePaginator
    {
        $perPage = max(1, min((int) ($filters['per_page'] ?? 20), 100));
        return cache()->tags(['forums', 'threads', "course:{$courseId}"])->remember(
            "forums:threads:course:{$courseId}:{$perPage}:" . request('page', 1) . ":" . md5(json_encode($filters)),
            300,
            function () use ($courseId, $filters) {
                $query = Thread::query()
                    ->withIsMentioned()
                    ->where('course_id', $courseId)
                    ->with(['author.media', 'replies']);

                return $this->filteredPaginate(
                    $query,
                    $filters,
                    [
                        AllowedFilter::exact('author_id'),
                        AllowedFilter::scope('pinned'),
                        AllowedFilter::scope('resolved'),
                        AllowedFilter::scope('closed'),
                        AllowedFilter::scope('is_mentioned'),
                    ],
                    ['last_activity_at', 'created_at', 'replies_count', 'views_count', 'is_pinned'],
                    '-is_pinned,-last_activity_at',
                    $filters['per_page'] ?? 20
                );
            }
        );
    }

    public function getThreadsForScheme(int $schemeId, array $filters = []): LengthAwarePaginator
    {
        return $this->getThreadsByCourse($schemeId, $filters);
    }

    public function searchThreadsByCourse(string $searchQuery, int $courseId, array $filters = []): LengthAwarePaginator
    {
        $perPage = max(1, min((int) ($filters['per_page'] ?? 20), 100));
        return cache()->tags(['forums', 'threads', "course:{$courseId}"])->remember(
            "forums:threads:search:course:{$courseId}:{$searchQuery}:{$perPage}:" . request('page', 1) . ":" . md5(json_encode($filters)),
            300,
            function () use ($searchQuery, $courseId, $filters) {
                $query = Thread::query()
                    ->withIsMentioned()
                    ->where('course_id', $courseId)
                    ->with(['author.media', 'replies']);

                if (! empty(trim($searchQuery))) {
                    $query->where(function ($subQuery) use ($searchQuery) {
                        $subQuery->search($searchQuery)
                            ->orWhereHas('replies', function ($replyQuery) use ($searchQuery) {
                                $replyQuery->search($searchQuery);
                            });
                    });
                }

                return $this->filteredPaginate(
                    $query,
                    $filters,
                    [
                        AllowedFilter::exact('author_id'),
                        AllowedFilter::scope('pinned'),
                        AllowedFilter::scope('resolved'),
                        AllowedFilter::scope('closed'),
                        AllowedFilter::scope('is_mentioned'),
                    ],
                    ['last_activity_at', 'created_at', 'replies_count', 'views_count', 'is_pinned'],
                    '-is_pinned,-last_activity_at',
                    $filters['per_page'] ?? 20
                );
            }
        );
    }

    public function searchThreads(string $searchQuery, int $schemeId, array $filters = []): LengthAwarePaginator
    {
        return $this->searchThreadsByCourse($searchQuery, $schemeId, $filters);
    }

    public function findWithRelations(int $threadId): ?Thread
    {
        return Thread::withIsMentioned()
            ->with(['author.media', 'course', 'replies.author.media', 'replies.children', 'replies.media'])
            ->find($threadId);
    }

    public function getPinnedThreads(int $courseId): Collection
    {
        return Thread::query()
            ->withIsMentioned()
            ->where('course_id', $courseId)
            ->pinned()
            ->with(['author.media'])
            ->orderBy('last_activity_at', 'desc')
            ->get();
    }

    public function getAllThreads(array $filters = [], ?string $search = null): LengthAwarePaginator
    {
        $perPage = max(1, min((int) ($filters['per_page'] ?? 20), 100));
        return cache()->tags(['forums', 'threads'])->remember(
            "forums:threads:all:{$perPage}:" . request('page', 1) . ":" . md5(json_encode([$filters, $search])),
            300,
            function () use ($filters, $search) {
                $query = Thread::query()
                    ->withIsMentioned()
                    ->with(['author.media', 'course']);

                if ($search && ! empty(trim($search))) {
                    $query->search($search);
                }

                return $this->filteredPaginate(
                    $query,
                    $filters,
                    [
                        AllowedFilter::exact('author_id'),
                        AllowedFilter::scope('pinned'),
                        AllowedFilter::scope('resolved'),
                        AllowedFilter::scope('closed'),
                        AllowedFilter::scope('is_mentioned'),
                    ],
                    ['last_activity_at', 'created_at', 'replies_count', 'views_count', 'is_pinned'],
                    '-is_pinned,-last_activity_at',
                    $filters['per_page'] ?? 20
                );
            }
        );
    }

    public function getInstructorThreads(int $instructorId, array $filters = [], ?string $search = null): LengthAwarePaginator
    {
        $perPage = max(1, min((int) ($filters['per_page'] ?? 20), 100));
        return cache()->tags(['forums', 'threads', "instructor:{$instructorId}"])->remember(
            "forums:threads:instructor:{$instructorId}:{$perPage}:" . request('page', 1) . ":" . md5(json_encode([$filters, $search])),
            300,
            function () use ($instructorId, $filters, $search) {
                $courseIds = \Modules\Schemes\Models\Course::where('instructor_id', $instructorId)
                    ->pluck('id')
                    ->toArray();

                $query = Thread::query()
                    ->withIsMentioned()
                    ->whereIn('course_id', $courseIds)
                    ->with(['author.media', 'course']);

                if ($search && ! empty(trim($search))) {
                    $query->search($search);
                }

                return $this->filteredPaginate(
                    $query,
                    $filters,
                    [
                        AllowedFilter::exact('author_id'),
                        AllowedFilter::scope('pinned'),
                        AllowedFilter::scope('resolved'),
                        AllowedFilter::scope('closed'),
                        AllowedFilter::scope('is_mentioned'),
                    ],
                    ['last_activity_at', 'created_at', 'replies_count', 'views_count', 'is_pinned'],
                    '-is_pinned,-last_activity_at',
                    $filters['per_page'] ?? 20
                );
            }
        );
    }

    public function getUserThreads(int $userId, array $filters = [], ?string $search = null): LengthAwarePaginator
    {
        $perPage = max(1, min((int) ($filters['per_page'] ?? 20), 100));
        return cache()->tags(['forums', 'threads', "user:{$userId}"])->remember(
            "forums:threads:user:{$userId}:{$perPage}:" . request('page', 1) . ":" . md5(json_encode([$filters, $search])),
            300,
            function () use ($userId, $filters, $search) {
                $query = Thread::query()
                    ->withIsMentioned()
                    ->where('author_id', $userId)
                    ->with(['author.media', 'course']);

                if ($search && ! empty(trim($search))) {
                    $query->search($search);
                }

                return $this->filteredPaginate(
                    $query,
                    $filters,
                    [
                        AllowedFilter::scope('pinned'),
                        AllowedFilter::scope('resolved'),
                        AllowedFilter::scope('closed'),
                        AllowedFilter::scope('is_mentioned'),
                    ],
                    ['last_activity_at', 'created_at', 'replies_count', 'views_count'],
                    '-last_activity_at',
                    $filters['per_page'] ?? 20
                );
            }
        );
    }

    public function getRecentThreads(int $limit = 10): Collection
    {
        return Thread::query()
            ->withIsMentioned()
            ->with(['author.media', 'course'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public function getInstructorRecentThreads(int $instructorId, int $limit = 10): Collection
    {
        $courseIds = \Modules\Schemes\Models\Course::where('instructor_id', $instructorId)
            ->pluck('id')
            ->toArray();

        return Thread::query()
            ->withIsMentioned()
            ->whereIn('course_id', $courseIds)
            ->with(['author.media', 'course'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public function getTrendingThreads(array $filters = [], ?string $search = null): LengthAwarePaginator
    {
        $perPage = (int) ($filters['limit'] ?? $filters['per_page'] ?? 10);
        $perPage = max(1, min($perPage, 100));
        $userId = auth()->id() ?? 0;

        return cache()->tags(['forums', 'threads', 'trending'])->remember(
            'forums:trending:all:'.$userId.':'.md5(json_encode([$filters, $search, $perPage])),
            120, // 2 minutes
            function () use ($filters, $search, $perPage) {
                $query = Thread::query()
                    ->withIsMentioned()
                    ->with(['author.media', 'course']);

                if ($search && ! empty(trim($search))) {
                    $query->search($search);
                }

                return $this->filteredPaginate(
                    $query,
                    $filters,
                    [
                        AllowedFilter::callback('period', function ($query, $value): void {
                            $period = (string) $value;
                            $startDate = match($period) {
                                '24hours' => now()->subDay(),
                                '7days' => now()->subDays(7),
                                '30days' => now()->subDays(30),
                                '90days' => now()->subDays(90),
                                default => now()->subDays(7),
                            };
                            $query->where('created_at', '>=', $startDate);
                        }),
                    ],
                    ['replies_count', 'views_count', 'created_at'],
                    '-replies_count',
                    $perPage
                );
            }
        );
    }

    public function getInstructorTrendingThreads(int $instructorId, array $filters = [], ?string $search = null): LengthAwarePaginator
    {
        $perPage = (int) ($filters['limit'] ?? $filters['per_page'] ?? 10);
        $perPage = max(1, min($perPage, 100));
        $userId = auth()->id() ?? 0;

        return cache()->tags(['forums', 'threads', 'trending', "instructor:{$instructorId}"])->remember(
            'forums:trending:instructor:'.$instructorId.':'.$userId.':'.md5(json_encode([$filters, $search, $perPage])),
            120, // 2 minutes
            function () use ($instructorId, $filters, $search, $perPage) {
                $courseIds = \Modules\Schemes\Models\Course::where('instructor_id', $instructorId)
                    ->pluck('id')
                    ->toArray();

                $query = Thread::query()
                    ->withIsMentioned()
                    ->whereIn('course_id', $courseIds)
                    ->with(['author.media', 'course']);

                if ($search && ! empty(trim($search))) {
                    $query->search($search);
                }

                return $this->filteredPaginate(
                    $query,
                    $filters,
                    [
                        AllowedFilter::callback('period', function ($query, $value): void {
                            $period = (string) $value;
                            $startDate = match($period) {
                                '24hours' => now()->subDay(),
                                '7days' => now()->subDays(7),
                                '30days' => now()->subDays(30),
                                '90days' => now()->subDays(90),
                                default => now()->subDays(7),
                            };
                            $query->where('created_at', '>=', $startDate);
                        }),
                    ],
                    ['replies_count', 'views_count', 'created_at'],
                    '-replies_count',
                    $perPage
                );
            }
        );
    }
}
