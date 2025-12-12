<?php

namespace Modules\Questions\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Questions\Contracts\Repositories\QuestionRepositoryInterface;
use Modules\Questions\Contracts\Services\QuestionServiceInterface;
use Modules\Questions\DTOs\CreateQuestionDTO;
use Modules\Questions\DTOs\UpdateQuestionDTO;
use Modules\Questions\Models\Question;

class QuestionService implements QuestionServiceInterface
{
    public function __construct(
        private readonly QuestionRepositoryInterface $repository
    ) {}

    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginate($filters, $perPage);
    }

    public function find(int $id): ?Question
    {
        return $this->repository->findById($id);
    }

    public function create(CreateQuestionDTO $dto, int $userId): Question
    {
        return DB::transaction(function () use ($dto, $userId) {
            $data = $dto->toModelArray();
            $data['created_by'] = $userId;

            $question = $this->repository->create($data);

            if (in_array($dto->type, ['multiple_choice', 'true_false']) && ! empty($dto->options)) {
                $this->syncOptions($question, $dto->options);
            }

            return $question->load(['category', 'creator', 'options']);
        });
    }

    public function update(int $id, UpdateQuestionDTO $dto): Question
    {
        return DB::transaction(function () use ($id, $dto) {
            $question = $this->repository->findById($id);

            if (! $question) {
                throw new \Exception('Question not found');
            }

            $data = $dto->toModelArray();
            $question = $this->repository->update($question, $data);

            if (! ($dto->options instanceof \Spatie\LaravelData\Optional) && ! is_null($dto->options)) {
                if (in_array($question->type->value, ['multiple_choice', 'true_false'])) {
                    $this->syncOptions($question, $dto->options);
                }
            }

            return $question->load(['category', 'creator', 'options']);
        });
    }

    public function delete(int $id): bool
    {
        $question = $this->repository->findById($id);

        if (! $question) {
            throw new \Exception('Question not found');
        }

        return $this->repository->delete($question);
    }

    public function getRandomQuestions(array $filters = [], int $count = 10): mixed
    {
        return $this->repository->getRandomQuestions($filters, $count);
    }

    public function incrementUsage(int $id): void
    {
        $question = $this->repository->findById($id);

        if ($question) {
            $this->repository->incrementUsage($question);
        }
    }

    private function syncOptions(Question $question, array $options): void
    {
        $question->options()->delete();

        foreach ($options as $index => $option) {
            $question->options()->create([
                'option_key' => $option['option_key'] ?? chr(65 + $index),
                'option_text' => $option['option_text'],
                'is_correct' => $option['is_correct'] ?? false,
                'order' => $option['order'] ?? $index,
            ]);
        }
    }
}
