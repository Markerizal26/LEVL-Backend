<?php

namespace Modules\Questions\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Questions\Contracts\Repositories\QuestionRepositoryInterface;
use Modules\Questions\Models\Question;

class QuestionRepository implements QuestionRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Question::query()->with(['category', 'creator', 'options']);

        if (! empty($filters['search'])) {
            $query->search($filters['search']);
        }

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (! empty($filters['difficulty'])) {
            $query->where('difficulty', $filters['difficulty']);
        }

        if (! empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['created_by'])) {
            $query->where('created_by', $filters['created_by']);
        }

        if (! empty($filters['sort_by'])) {
            $direction = $filters['sort_direction'] ?? 'asc';
            $query->orderBy($filters['sort_by'], $direction);
        } else {
            $query->latest();
        }

        return $query->paginate($perPage);
    }

    public function findById(int $id): ?Question
    {
        return Question::with(['category', 'creator', 'options'])->find($id);
    }

    public function create(array $data): Question
    {
        return Question::create($data);
    }

    public function update(Question $question, array $data): Question
    {
        $question->update($data);

        return $question->fresh(['category', 'creator', 'options']);
    }

    public function delete(Question $question): bool
    {
        return $question->delete();
    }

    public function getByType(string $type, int $limit = 10): mixed
    {
        return Question::with(['options'])
            ->where('type', $type)
            ->active()
            ->limit($limit)
            ->get();
    }

    public function getByDifficulty(string $difficulty, int $limit = 10): mixed
    {
        return Question::with(['options'])
            ->where('difficulty', $difficulty)
            ->active()
            ->limit($limit)
            ->get();
    }

    public function getRandomQuestions(array $filters = [], int $count = 10): mixed
    {
        $query = Question::with(['options'])->active();

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (! empty($filters['difficulty'])) {
            $query->where('difficulty', $filters['difficulty']);
        }

        if (! empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        return $query->inRandomOrder()->limit($count)->get();
    }

    public function incrementUsage(Question $question): void
    {
        $question->incrementUsage();
    }
}
