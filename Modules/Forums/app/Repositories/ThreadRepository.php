<?php

namespace Modules\Forums\Repositories;

use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Forums\Models\Thread;

class ThreadRepository extends BaseRepository
{
    protected function model(): string
    {
        return Thread::class;
    }

    protected array $allowedFilters = ['pinned', 'resolved', 'closed', 'scheme_id'];

    protected array $allowedSorts = ['id', 'created_at', 'last_activity_at', 'is_pinned'];

    protected string $defaultSort = '-last_activity_at';

    protected array $with = ['author'];

    // ========================================================================
    // Interface Methods - These satisfy ThreadRepositoryInterface
    // ========================================================================

    /**
     * Paginate threads with filters.
     * Satisfies both BaseRepository and ThreadRepositoryInterface signatures.
     */
    public function paginate(array $filters = [], int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = $this->query();
        
        $query = $this->applyFilters($query, $filters);
        $query = $this->applySorting($query, $filters);
        
        $perPage = $filters['per_page'] ?? $perPage;
        return $query->paginate($perPage);
    }

    public function paginateByCourse(int $courseId, array $filters = [], int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return $this->getThreadsForScheme($courseId, $filters);
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

    // ========================================================================
    // Additional Helper Methods (Not in interface, for internal use)
    // ========================================================================

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
        $thread->is_locked = true;
        $thread->save();
        return $thread;
    }

    public function unlock(Thread $thread): Thread
    {
        $thread->is_locked = false;
        $thread->save();
        return $thread;
    }

    // ========================================================================
    // Additional Helper Methods (Not in interface, for internal use)
    // ========================================================================

    public function getThreadsForScheme(int $schemeId, array $filters = []): LengthAwarePaginator
    {
        $query = Thread::forScheme($schemeId)
            ->with(['author', 'replies'])
            ->withCount('replies');

        if (isset($filters['pinned']) && $filters['pinned']) {
            $query->pinned();
        }

        if (isset($filters['resolved']) && $filters['resolved']) {
            $query->resolved();
        }

        if (isset($filters['closed'])) {
            if ($filters['closed']) {
                $query->closed();
            } else {
                $query->open();
            }
        }

        $query->orderBy('is_pinned', 'desc')
            ->orderBy('last_activity_at', 'desc');

        return $query->paginate($filters['per_page'] ?? 20);
    }

    public function searchThreads(string $searchQuery, int $schemeId, int $perPage = 20): LengthAwarePaginator
    {
        return Thread::forScheme($schemeId)
            ->whereRaw('MATCH(title, content) AGAINST(? IN NATURAL LANGUAGE MODE)', [$searchQuery])
            ->with(['author', 'replies'])
            ->withCount('replies')
            ->orderBy('is_pinned', 'desc')
            ->orderBy('last_activity_at', 'desc')
            ->paginate($perPage);
    }

    public function findWithRelations(int $threadId): ?Thread
    {
        return Thread::with(['author', 'scheme', 'replies.author', 'replies.children'])
            ->withCount('replies')
            ->find($threadId);
    }

    public function getPinnedThreads(int $schemeId): Collection
    {
        return Thread::forScheme($schemeId)
            ->pinned()
            ->with(['author'])
            ->withCount('replies')
            ->orderBy('last_activity_at', 'desc')
            ->get();
    }
}
