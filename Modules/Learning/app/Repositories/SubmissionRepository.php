<?php

namespace Modules\Learning\Repositories;

use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\Auth\Models\User;
use Modules\Learning\Models\Assignment;
use Modules\Learning\Models\Submission;

class SubmissionRepository extends BaseRepository
{
    protected function model(): string
    {
        return Submission::class;
    }

    protected array $allowedFilters = ['assignment_id', 'user_id', 'status'];

    protected array $allowedSorts = ['id', 'created_at', 'submitted_at', 'graded_at'];

    protected string $defaultSort = '-created_at';

    protected array $with = ['user', 'enrollment'];

    public function listForAssignment(Assignment $assignment, User $user, array $filters = []): Collection
    {
        $query = Submission::query()
            ->where('assignment_id', $assignment->id)
            ->with(['user:id,name,email', 'enrollment:id,status', 'files', 'grade']);

        if ($user->hasRole('Student')) {
            $query->where('user_id', $user->id);
        } elseif (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    public function create(array $attributes): Submission
    {
        return Submission::create($attributes);
    }

    public function update(Model $model, array $attributes): Model
    {
        $model->fill($attributes)->save();

        return $model;
    }

    public function updateSubmission(Submission $submission, array $attributes): Submission
    {
        $submission->fill($attributes)->save();

        return $submission;
    }

    public function delete(Model $model): bool
    {
        return $model->delete();
    }

    public function latestCommittedSubmission(Assignment $assignment, int $userId): ?Submission
    {
        return Submission::query()
            ->where('assignment_id', $assignment->id)
            ->where('user_id', $userId)
            ->whereIn('status', ['submitted', 'late', 'graded'])
            ->latest('id')
            ->first();
    }
}
