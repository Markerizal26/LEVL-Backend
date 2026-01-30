<?php

declare(strict_types=1);

namespace Modules\Auth\Repositories;

use App\Repositories\BaseRepository;
use Illuminate\Support\Collection;
use Modules\Auth\Models\User;

class BenchmarkRepository extends BaseRepository
{
    public function model(): string
    {
        return User::class;
    }

    /**
     * Retrieve 1000 users for benchmark.
     */
    public function get1000Users(): Collection
    {
        return $this->query()
            ->select(['id', 'name', 'username', 'email', 'created_at'])
            ->limit(1000)
            ->toBase()
            ->get();
    }

    /**
     * Batch insert 1000 users.
     */
    public function insert1000Users(array $data): bool
    {
        return $this->query()->insert($data);
    }

    /**
     * Truncate users table for benchmark cleanup.
     * WARNING: This deletes ALL users. Use with caution.
     */
    public function truncateUsers(): void
    {
        $this->query()->truncate();
    }
}
