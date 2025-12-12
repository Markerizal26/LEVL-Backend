<?php

namespace Modules\Forums\Repositories;

use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
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

    public function create(array $data): Thread
    {
        $thread = Thread::create($data);
        $thread->updateLastActivity();

        return $thread;
    }

    public function update(Model $model, array $attributes): Model
    {
        $model->update($attributes);

        return $model->fresh();
    }

    public function updateThread(Thread $thread, array $data): Thread
    {
        $thread->update($data);

        return $thread->fresh();
    }

    public function delete(Model $model): bool
    {
        return $model->delete();
    }

    public function deleteThread(Thread $thread, ?int $deletedBy = null): bool
    {
        if ($deletedBy) {
            $thread->deleted_by = $deletedBy;
            $thread->save();
        }

        return $thread->delete();
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
