<?php

declare(strict_types=1);

namespace Modules\Auth\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\Auth\Repositories\BenchmarkRepository;

class BenchmarkService
{
    public function __construct(
        private readonly BenchmarkRepository $repository
    ) {}

    public function getBenchmarkUsers(): Collection
    {
        return $this->repository->get1000Users();
    }

    public function createBenchmarkUsers(): bool
    {
        $users = [];
        $password = Hash::make('password');
        $now = now();

        for ($i = 0; $i < 1000; $i++) {
            $users[] = [
                'name' => 'Benchmark User '.Str::random(10),
                'username' => 'bench_'.Str::random(10).'_'.$i,
                'email' => 'bench_'.Str::random(10).'_'.$i.'@example.com',
                'password' => $password,
                'email_verified_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        return $this->repository->insert1000Users($users);
    }

    public function cleanupDatabase(): void
    {
        $this->repository->truncateUsers();
    }
}
