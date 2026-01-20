<?php

namespace Modules\Forums\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Auth\Models\User;
use Modules\Forums\Contracts\Services\ForumServiceInterface as ModuleForumServiceInterface;
use Modules\Forums\Models\Reply;
use Modules\Forums\Models\Thread;
use Modules\Forums\Repositories\ReplyRepository;
use Modules\Forums\Repositories\ThreadRepository;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ForumService implements ModuleForumServiceInterface, \App\Contracts\Services\ForumServiceInterface
{
    protected ThreadRepository $threadRepository;

    protected ReplyRepository $replyRepository;

    public function __construct(
        ThreadRepository $threadRepository,
        ReplyRepository $replyRepository
    ) {
        $this->threadRepository = $threadRepository;
        $this->replyRepository = $replyRepository;
    }

    /**
     * Create a new thread.
     *
     * @throws \Exception
     */
    public function createThread(array $data, User $user): Thread
    {
        return DB::transaction(function () use ($data, $user) {
            $threadData = [
                'scheme_id' => $data['scheme_id'],
                'author_id' => $user->id,
                'title' => $data['title'],
                'content' => $data['content'],
                'last_activity_at' => now(),
            ];

            $thread = $this->threadRepository->create($threadData);

            // Fire event for notifications
            event(new \Modules\Forums\Events\ThreadCreated($thread));

            return $thread;
        });
    }

    /**
     * Update a thread.
     */
    public function updateThread(Thread $thread, array $data): Thread
    {
        $updateData = [];

        if (isset($data['title'])) {
            $updateData['title'] = $data['title'];
        }

        if (isset($data['content'])) {
            $updateData['content'] = $data['content'];
        }

        if (! empty($updateData)) {
            $updateData['edited_at'] = now();
        }

        return $this->threadRepository->update($thread, $updateData);
    }

    /**
     * Delete a thread (soft delete).
     */
    public function deleteThread(Thread $thread, User $user): bool
    {
        return $this->threadRepository->delete($thread, $user->id);
    }

    /**
     * Get threads for a scheme with sorting and filters.
     *
     * Supports:
     * - filter[scheme_id], filter[author_id], filter[status]
     * - sort: last_activity_at, created_at, replies_count, views_count (prefix with - for desc)
     * - include: author, replies, scheme
     */
    public function getThreadsForScheme(int $schemeId, array $filters = []): LengthAwarePaginator
    {
        $perPage = $filters['per_page'] ?? 15;

        $query = QueryBuilder::for(Thread::class)
            ->where('scheme_id', $schemeId)
            ->allowedFilters([
                AllowedFilter::exact('author_id'),
                AllowedFilter::exact('status'),
            ])
            ->allowedIncludes(['author', 'replies', 'scheme'])
            ->allowedSorts(['last_activity_at', 'created_at', 'replies_count', 'views_count'])
            ->defaultSort('-last_activity_at');

        return $query->paginate($perPage);
    }

    /**
     * Search threads by query.
     */
    public function searchThreads(string $query, int $schemeId): LengthAwarePaginator
    {
        return $this->threadRepository->searchThreads($query, $schemeId);
    }

    /**
     * Get a thread with all details.
     */
    public function getThreadDetail(int $threadId): ?Thread
    {
        $thread = $this->threadRepository->findWithRelations($threadId);

        if ($thread) {
            $thread->incrementViews();
        }

        return $thread;
    }

    /**
     * Create a reply to a thread or another reply.
     *
     * @throws \Exception
     */
    public function createReply(Thread $thread, array $data, User $user, ?int $parentId = null): Reply
    {
        // Check if thread is closed
        if ($thread->isClosed()) {
            throw new \Exception(__('messages.forums.cannot_reply_closed_thread'));
        }

        $parent = $parentId ? Reply::find($parentId) : null;

        // Check depth limit if replying to another reply
        if ($parent && ! $parent->canHaveChildren()) {
            throw new \Exception(__('messages.forums.max_reply_depth_exceeded'));
        }

        return DB::transaction(function () use ($thread, $data, $user, $parent) {
            $replyData = [
                'thread_id' => $thread->id,
                'parent_id' => $parent?->id,
                'author_id' => $user->id,
                'content' => $data['content'],
            ];

            $reply = $this->replyRepository->create($replyData);

            // Update thread's reply count and last activity
            $thread->increment('replies_count');
            $thread->updateLastActivity();

            // Fire event for notifications
            event(new \Modules\Forums\Events\ReplyCreated($reply));

            return $reply;
        });
    }

    /**
     * Update a reply.
     */
    public function updateReply(Reply $reply, array $data): Reply
    {
        $updateData = [];

        if (isset($data['content'])) {
            $updateData['content'] = $data['content'];
            $updateData['edited_at'] = now();
        }

        return $this->replyRepository->update($reply, $updateData);
    }

    /**
     * Delete a reply (soft delete).
     */
    public function deleteReply(Reply $reply, User $user): bool
    {
        return DB::transaction(function () use ($reply, $user) {
            $thread = $reply->thread;

            $result = $this->replyRepository->delete($reply, $user->id);

            if ($result) {
                // Decrement thread's reply count
                $thread->decrement('replies_count');
            }

            return $result;
        });
    }
}
