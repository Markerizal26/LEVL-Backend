<?php

namespace Modules\Questions\Contracts\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Questions\DTOs\CreateQuestionDTO;
use Modules\Questions\DTOs\UpdateQuestionDTO;
use Modules\Questions\Models\Question;

interface QuestionServiceInterface
{
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function find(int $id): ?Question;

    public function create(CreateQuestionDTO $dto, int $userId): Question;

    public function update(int $id, UpdateQuestionDTO $dto): Question;

    public function delete(int $id): bool;

    public function getRandomQuestions(array $filters = [], int $count = 10): mixed;

    public function incrementUsage(int $id): void;
}
