<?php

namespace Modules\Forums\Contracts\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Auth\Models\User;
use Modules\Forums\Models\Reply;
use Modules\Forums\Models\Thread;

interface ForumServiceInterface
{
    public function getThreadsForScheme(int $schemeId, array $filters = []): LengthAwarePaginator;

    public function createThread(array $data, User $user): Thread;

    public function updateThread(Thread $thread, array $data): Thread;

    public function deleteThread(Thread $thread, User $user): bool;

    public function createReply(Thread $thread, array $data, User $user, ?int $parentId = null): Reply;

    public function updateReply(Reply $reply, array $data): Reply;

    public function deleteReply(Reply $reply, User $user): bool;

    public function searchThreads(string $query, int $schemeId): LengthAwarePaginator;
}
