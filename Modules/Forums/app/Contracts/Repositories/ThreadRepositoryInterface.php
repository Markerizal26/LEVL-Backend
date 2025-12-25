<?php

namespace Modules\Forums\Contracts\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Forums\Models\Thread;

interface ThreadRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function paginateByCourse(int $courseId, array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function find(int $id): ?Thread;

    public function findWithReplies(int $id): ?Thread;

    public function create(array $data): Thread;

    public function update(Thread $thread, array $data): Thread;

    public function delete(Thread $thread): bool;

    public function pin(Thread $thread): Thread;

    public function unpin(Thread $thread): Thread;

    public function lock(Thread $thread): Thread;

    public function unlock(Thread $thread): Thread;
}
