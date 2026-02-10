<?php

declare(strict_types=1);

namespace Modules\Forums\Repositories;

use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Modules\Forums\Models\Reply;
use Modules\Forums\Models\Thread;
use Spatie\QueryBuilder\AllowedFilter;

class ThreadRepository extends BaseRepository
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
        $query = $this->query();

        $query = $this->applyFilters($query, $filters);
        $query = $this->applySorting($query, $filters);

        $perPage = $filters['per_page'] ?? $perPage;

        return $query->paginate($perPage);
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
        $query = Thread::query()
            ->withIsMentioned()
            ->where('course_id', $courseId)
            ->with(['author.media', 'replies']);

        $paginator = $this->filteredPaginate(
            $query,
            $filters,
            [
                AllowedFilter::exact('author_id'),
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

    public function getThreadsForScheme(int $schemeId, array $filters = []): LengthAwarePaginator
    {
        return $this->getThreadsByCourse($schemeId, $filters);
    }

    public function searchThreadsByCourse(string $searchQuery, int $courseId, array $filters = []): LengthAwarePaginator
    {
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
            ['last_activity_at', 'created_at', 'replies_count', 'views_count'],
            '-last_activity_at',
            $filters['per_page'] ?? 20
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
            ['last_activity_at', 'created_at', 'replies_count', 'views_count'],
            '-last_activity_at',
            $filters['per_page'] ?? 20
        );
    }

    public function getInstructorThreads(int $instructorId, array $filters = [], ?string $search = null): LengthAwarePaginator
    {
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
            ['last_activity_at', 'created_at', 'replies_count', 'views_count'],
            '-last_activity_at',
            $filters['per_page'] ?? 20
        );
    }

    public function getUserThreads(int $userId, array $filters = [], ?string $search = null): LengthAwarePaginator
    {
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

        $cacheKey = 'forums:trending:all:'.(auth()->id() ?? 0).':'.md5(json_encode([$filters, $search, $perPage]));
        $cached = Cache::get($cacheKey);
        if ($cached) {
            return $cached;
        }

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
        Cache::put($cacheKey, $paginator, now()->addMinutes(2));
        return $paginator;
    }

    public function getInstructorTrendingThreads(int $instructorId, array $filters = [], ?string $search = null): LengthAwarePaginator
    {
        $courseIds = \Modules\Schemes\Models\Course::where('instructor_id', $instructorId)
            ->pluck('id')
            ->toArray();
        $perPage = (int) ($filters['limit'] ?? $filters['per_page'] ?? 10);

        $cacheKey = 'forums:trending:instructor:'.$instructorId.':'.(auth()->id() ?? 0).':'.md5(json_encode([$filters, $search, $perPage]));
        $cached = Cache::get($cacheKey);
        if ($cached) {
            return $cached;
        }

        $query = Thread::query()
            ->withIsMentioned()
            ->whereIn('course_id', $courseIds)
            ->with(['author.media', 'course']);

        if ($search && ! empty(trim($search))) {
            $query->search($search);
        }

        $paginator = $this->filteredPaginate(
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
        Cache::put($cacheKey, $paginator, now()->addMinutes(2));
        return $paginator;
    }
}
