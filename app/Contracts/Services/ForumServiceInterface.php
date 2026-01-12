<?php

namespace App\Contracts\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Auth\Models\User;
use Modules\Forums\Models\Reply;
use Modules\Forums\Models\Thread;

interface ForumServiceInterface
{
    /**
     * Create a new thread.
     *
     * @param  array  $data  Thread data including scheme_id, title, content
     * @param  User  $user  The authenticated user creating the thread
     * @return Thread The created thread
     *
     * @throws \Exception If thread creation fails
     */
    public function createThread(array $data, User $user): Thread;

    /**
     * Update an existing thread.
     *
     * @param  Thread  $thread  The thread to update
     * @param  array  $data  Update data including title and/or content
     * @return Thread The updated thread
     */
    public function updateThread(Thread $thread, array $data): Thread;

    /**
     * Delete a thread (soft delete).
     *
     * @param  Thread  $thread  The thread to delete
     * @param  User  $user  The user performing the deletion
     * @return bool True if deletion was successful
     */
    public function deleteThread(Thread $thread, User $user): bool;

    /**
     * Get threads for a scheme with sorting and filters.
     *
     * @param  int  $schemeId  The scheme ID
     * @param  array  $filters  Optional filters for sorting and filtering
     * @return LengthAwarePaginator Paginated threads
     */
    public function getThreadsForScheme(int $schemeId, array $filters = []): LengthAwarePaginator;

    /**
     * Search threads by query.
     *
     * @param  string  $query  The search query
     * @param  int  $schemeId  The scheme ID to search within
     * @return LengthAwarePaginator Paginated search results
     */
    public function searchThreads(string $query, int $schemeId): LengthAwarePaginator;

    /**
     * Get a thread with all details.
     *
     * @param  int  $threadId  The thread ID
     * @return Thread|null The thread with relations or null if not found
     */
    public function getThreadDetail(int $threadId): ?Thread;

    /**
     * Create a reply to a thread or another reply.
     *
     * @param  Thread  $thread  The thread to reply to
     * @param  array  $data  Reply data including content
     * @param  User  $user  The authenticated user creating the reply
     * @param  Reply|null  $parent  Optional parent reply for nested replies
     * @return Reply The created reply
     *
     * @throws \Exception If thread is closed or max depth exceeded
     */
    public function createReply(Thread $thread, array $data, User $user, ?int $parentId = null): Reply;

    /**
     * Update a reply.
     *
     * @param  Reply  $reply  The reply to update
     * @param  array  $data  Update data including content
     * @return Reply The updated reply
     */
    public function updateReply(Reply $reply, array $data): Reply;

    /**
     * Delete a reply (soft delete).
     *
     * @param  Reply  $reply  The reply to delete
     * @param  User  $user  The user performing the deletion
     * @return bool True if deletion was successful
     */
    public function deleteReply(Reply $reply, User $user): bool;
}
