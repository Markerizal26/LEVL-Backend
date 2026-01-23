<?php declare(strict_types=1);

namespace Modules\Learning\Contracts\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\Learning\Models\Assignment;
use Modules\Schemes\Models\Lesson;

interface AssignmentRepositoryInterface
{


    public function create(array $attributes): Assignment;

    public function update(Model $model, array $attributes): Assignment;

    public function delete(Model $model): bool;

    public function find(int $id): ?Assignment;

    public function findWithQuestions(int $id): ?Assignment;

    public function findByScope(string $scopeType, int $scopeId): Collection;

    public function duplicate(int $id): Assignment;

    public function invalidateAssignmentCache(int $id): void;
    
    public function invalidateListCache(string $type, int $id, ?string $scopeType = null): void;
}
