<?php

namespace Modules\Questions\Contracts\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Questions\Models\Question;

interface QuestionRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function findById(int $id): ?Question;

    public function create(array $data): Question;

    public function update(Question $question, array $data): Question;

    public function delete(Question $question): bool;

    public function getByType(string $type, int $limit = 10): mixed;

    public function getByDifficulty(string $difficulty, int $limit = 10): mixed;

    public function getRandomQuestions(array $filters = [], int $count = 10): mixed;

    public function incrementUsage(Question $question): void;
}
