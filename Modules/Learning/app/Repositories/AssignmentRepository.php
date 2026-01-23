<?php declare(strict_types=1);

namespace Modules\Learning\Repositories;

use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Modules\Learning\Contracts\Repositories\AssignmentRepositoryInterface;
use Modules\Learning\Models\Assignment;
use Modules\Schemes\Models\Lesson;

class AssignmentRepository extends BaseRepository implements AssignmentRepositoryInterface
{
    protected function model(): string
    {
        return Assignment::class;
    }

        protected const CACHE_TTL_ASSIGNMENT = 3600;

        protected const CACHE_PREFIX_ASSIGNMENT = 'assignment:';

        protected const CACHE_PREFIX_ASSIGNMENT_LIST = 'assignment_list:';

        protected const DEFAULT_EAGER_LOAD = [
        'creator:id,name,email',
        'questions',
    ];

        protected const DETAILED_EAGER_LOAD = [
        'creator:id,name,email',
        'questions',
        'prerequisites:id,title',
        'assignable',
    ];



    public function create(array $attributes): Assignment
    {
        $assignment = Assignment::create($attributes);

        
        if (isset($attributes['lesson_id'])) {
            $this->invalidateListCache('lesson', $attributes['lesson_id']);
        }

        
        if (isset($attributes['assignable_type'], $attributes['assignable_id'])) {
            $this->invalidateListCache('scope', $attributes['assignable_id'], $attributes['assignable_type']);
        }

        return $assignment;
    }

    public function findWithPrerequisites(int $id): ?Assignment
    {
        return Assignment::with('prerequisites')->find($id);
    }

    public function findWithRelations(Assignment $assignment): Assignment
    {
        return $assignment->loadMissing(['creator:id,name,email', 'lesson:id,title,slug', 'questions']);
    }

    public function attachPrerequisite(int $assignmentId, int $prerequisiteId): void
    {
        $assignment = Assignment::findOrFail($assignmentId);
        $assignment->prerequisites()->syncWithoutDetaching([$prerequisiteId]);
    }

    public function detachPrerequisite(int $assignmentId, int $prerequisiteId): void
    {
        $assignment = Assignment::findOrFail($assignmentId);
        $assignment->prerequisites()->detach($prerequisiteId);
    }

    public function findForDuplication(int $id): ?Assignment
    {
        return Assignment::with(['questions', 'prerequisites'])->find($id);
    }

    public function update(Model $model, array $attributes): Assignment
    {
        assert($model instanceof Assignment);
        
        $model->fill($attributes)->save();

        $this->invalidateAssignmentCache($model->id);

        if ($model->lesson_id) {
            $this->invalidateListCache('lesson', $model->lesson_id);
        }

        if ($model->assignable_type && $model->assignable_id) {
            $this->invalidateListCache('scope', $model->assignable_id, $model->assignable_type);
        }

        return $model;
    }

    public function delete(Model $model): bool
    {
        assert($model instanceof Assignment);
        
        $lessonId = $model->lesson_id;
        $assignableType = $model->assignable_type;
        $assignableId = $model->assignable_id;
        $assignmentId = $model->id;

        $result = $model->delete();

        if ($result) {
            
            $this->invalidateAssignmentCache($assignmentId);

            
            if ($lessonId) {
                $this->invalidateListCache('lesson', $lessonId);
            }

            
            if ($assignableType && $assignableId) {
                $this->invalidateListCache('scope', $assignableId, $assignableType);
            }
        }

        return $result;
    }

        public function find(int $id): ?Assignment
    {
        $cacheKey = $this->getAssignmentCacheKey($id);

        return Cache::remember($cacheKey, self::CACHE_TTL_ASSIGNMENT, function () use ($id) {
            return Assignment::query()
                ->where('id', $id)
                ->with(self::DEFAULT_EAGER_LOAD)
                ->first();
        });
    }

        public function findWithQuestions(int $id): ?Assignment
    {
        $cacheKey = $this->getAssignmentCacheKey($id, 'with_questions');

        return Cache::remember($cacheKey, self::CACHE_TTL_ASSIGNMENT, function () use ($id) {
            return Assignment::query()
                ->where('id', $id)
                ->with([
                    'creator:id,name,email',
                    'questions' => function ($query) {
                        $query->ordered();
                    },
                    'assignable',
                ])
                ->first();
        });
    }

        public function findWithDetails(int $id): ?Assignment
    {
        return Assignment::query()
            ->where('id', $id)
            ->with([
                'creator:id,name,email',
                'questions' => function ($query) {
                    $query->ordered();
                },
                'prerequisites:id,title',
                'assignable',
                'submissions' => function ($query) {
                    $query->with(['user:id,name,email', 'grade'])
                        ->orderByDesc('submitted_at')
                        ->limit(100);
                },
            ])
            ->first();
    }

        public function findByScope(string $scopeType, int $scopeId): Collection
    {
        $cacheKey = $this->getListCacheKey('scope', $scopeId, ['type' => $scopeType]);

        return Cache::remember($cacheKey, self::CACHE_TTL_ASSIGNMENT, function () use ($scopeType, $scopeId) {
            return Assignment::query()
                ->where('assignable_type', $scopeType)
                ->where('assignable_id', $scopeId)
                ->with(self::DEFAULT_EAGER_LOAD)
                ->orderBy('created_at', 'desc')
                ->get();
        });
    }

        public function duplicate(int $id): Assignment
    {
        $original = Assignment::query()
            ->where('id', $id)
            ->with(['questions'])
            ->firstOrFail();

        
        $newAssignment = $original->replicate(['id', 'created_at', 'updated_at']);
        $newAssignment->title = $original->title.' (Copy)';
        $newAssignment->save();

        
        foreach ($original->questions as $question) {
            $newQuestion = $question->replicate(['id', 'created_at', 'updated_at']);
            $newQuestion->assignment_id = $newAssignment->id;
            $newQuestion->save();
        }

        
        return $newAssignment->load(self::DEFAULT_EAGER_LOAD);
    }

        public function findWithPendingSubmissions(): Collection
    {
        return Assignment::query()
            ->whereHas('submissions', function ($query) {
                $query->where('state', 'pending_manual_grading');
            })
            ->with([
                'creator:id,name,email',
                'submissions' => function ($query) {
                    $query->where('state', 'pending_manual_grading')
                        ->with(['user:id,name,email', 'answers.question:id,type,content,weight'])
                        ->orderBy('submitted_at', 'asc');
                },
            ])
            ->get();
    }

        protected function getAssignmentCacheKey(int $id, string $suffix = ''): string
    {
        $key = self::CACHE_PREFIX_ASSIGNMENT.$id;

        return $suffix ? "{$key}:{$suffix}" : $key;
    }

        protected function getListCacheKey(string $type, int $id, array $filters = []): string
    {
        $filterHash = ! empty($filters) ? ':'.md5(serialize($filters)) : '';

        return self::CACHE_PREFIX_ASSIGNMENT_LIST."{$type}:{$id}{$filterHash}";
    }

        public function invalidateAssignmentCache(int $id): void
    {
        Cache::forget($this->getAssignmentCacheKey($id));
        Cache::forget($this->getAssignmentCacheKey($id, 'with_questions'));
    }

        public function invalidateListCache(string $type, int $id, ?string $scopeType = null): void
    {
        
        
        $baseKey = self::CACHE_PREFIX_ASSIGNMENT_LIST."{$type}:{$id}";

        
        Cache::forget($baseKey);

        
        if ($scopeType !== null) {
            $scopeKey = self::CACHE_PREFIX_ASSIGNMENT_LIST."scope:{$id}:".md5(serialize(['type' => $scopeType]));
            Cache::forget($scopeKey);
        }
    }

        public function clearAllCaches(): void
    {
        
        
        
    }
}
