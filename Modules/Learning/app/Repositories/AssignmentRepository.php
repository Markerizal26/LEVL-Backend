<?php

declare(strict_types=1);

namespace Modules\Learning\Repositories;

use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
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

    /**
     * Cache TTL for assignment configurations (1 hour).
     * Requirements: 28.7
     */
    protected const CACHE_TTL_ASSIGNMENT = 3600;

    /**
     * Cache key prefix for assignment data.
     */
    protected const CACHE_PREFIX_ASSIGNMENT = 'assignment:';

    /**
     * Cache key prefix for assignment lists.
     */
    protected const CACHE_PREFIX_ASSIGNMENT_LIST = 'assignment_list:';

    /**
     * Default eager loading relationships for assignments.
     * Prevents N+1 query problems when loading assignments with related data.
     * Requirements: 28.5
     */
    protected const DEFAULT_EAGER_LOAD = [
        'creator:id,name,email',
        'questions',
    ];

    /**
     * Extended eager loading for detailed assignment views.
     * Includes submissions and prerequisites for complete assignment data.
     * Requirements: 28.5
     */
    protected const DETAILED_EAGER_LOAD = [
        'creator:id,name,email',
        'questions',
        'prerequisites:id,title',
        'assignable',
    ];

    /**
     * List assignments for a lesson with eager loading and caching.
     * Requirements: 28.5, 28.7, 28.10
     */
    public function listForLesson(Lesson $lesson, array $filters = []): Collection
    {
        $cacheKey = $this->getListCacheKey('lesson', $lesson->id, $filters);

        return Cache::remember($cacheKey, self::CACHE_TTL_ASSIGNMENT, function () use ($lesson, $filters) {
            $query = Assignment::query()
                ->where('lesson_id', $lesson->id)
                ->with([
                    'creator:id,name,email',
                    'lesson:id,title,slug',
                    'questions:id,assignment_id,type,content,weight,order',
                ]);

            $status = $filters['status'] ?? ($filters['filter']['status'] ?? null);
            if ($status) {
                $query->where('status', $status);
            }

            // Add default limit to prevent unbounded queries
            $limit = $filters['limit'] ?? 100;

            return $query->orderBy('created_at', 'desc')->limit($limit)->get();
        });
    }

    public function create(array $attributes): Assignment
    {
        $assignment = Assignment::create($attributes);

        // Invalidate list cache for the lesson
        if (isset($attributes['lesson_id'])) {
            $this->invalidateListCache('lesson', $attributes['lesson_id']);
        }

        // Invalidate scope cache if polymorphic relationship is set
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

    public function update(Assignment $assignment, array $attributes): Assignment
    {
        $assignment->fill($attributes)->save();

        // Invalidate assignment cache
        $this->invalidateAssignmentCache($assignment->id);

        // Invalidate list cache for the lesson
        $this->invalidateListCache('lesson', $assignment->lesson_id);

        // Invalidate scope cache if polymorphic relationship exists
        if ($assignment->assignable_type && $assignment->assignable_id) {
            $this->invalidateListCache('scope', $assignment->assignable_id, $assignment->assignable_type);
        }

        return $assignment;
    }

    public function delete(Assignment $assignment): bool
    {
        $lessonId = $assignment->lesson_id;
        $assignableType = $assignment->assignable_type;
        $assignableId = $assignment->assignable_id;
        $assignmentId = $assignment->id;

        $result = $assignment->delete();

        if ($result) {
            // Invalidate assignment cache
            $this->invalidateAssignmentCache($assignmentId);

            // Invalidate list cache for the lesson
            $this->invalidateListCache('lesson', $lessonId);

            // Invalidate scope cache if polymorphic relationship existed
            if ($assignableType && $assignableId) {
                $this->invalidateListCache('scope', $assignableId, $assignableType);
            }
        }

        return $result;
    }

    /**
     * Find an assignment by ID with eager loading and caching.
     * Requirements: 28.5, 28.7
     */
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

    /**
     * Find an assignment with all questions for detailed view with caching.
     * Requirements: 28.5, 28.7
     */
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

    /**
     * Find an assignment with all related data for detailed view.
     * Includes questions, prerequisites, and submissions.
     * Note: Not cached due to dynamic submission data.
     * Requirements: 28.5
     */
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

    /**
     * Find assignments by scope (polymorphic) with eager loading and caching.
     * Requirements: 28.5, 28.7, 28.10
     */
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

    /**
     * Duplicate an assignment with all questions and settings.
     * Requirements: 25.1, 25.2, 25.4, 28.5
     */
    public function duplicate(int $id): Assignment
    {
        $original = Assignment::query()
            ->where('id', $id)
            ->with(['questions'])
            ->firstOrFail();

        // Create new assignment with same attributes
        $newAssignment = $original->replicate(['id', 'created_at', 'updated_at']);
        $newAssignment->title = $original->title.' (Copy)';
        $newAssignment->save();

        // Duplicate questions
        foreach ($original->questions as $question) {
            $newQuestion = $question->replicate(['id', 'created_at', 'updated_at']);
            $newQuestion->assignment_id = $newAssignment->id;
            $newQuestion->save();
        }

        // Load relationships for the new assignment
        return $newAssignment->load(self::DEFAULT_EAGER_LOAD);
    }

    /**
     * Find assignments with submissions for grading queue.
     * Note: Not cached due to dynamic submission state data.
     * Requirements: 10.1, 28.5
     */
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

    /**
     * Generate cache key for a single assignment.
     * Requirements: 28.7
     */
    protected function getAssignmentCacheKey(int $id, string $suffix = ''): string
    {
        $key = self::CACHE_PREFIX_ASSIGNMENT.$id;

        return $suffix ? "{$key}:{$suffix}" : $key;
    }

    /**
     * Generate cache key for assignment lists.
     * Requirements: 28.7, 28.10
     */
    protected function getListCacheKey(string $type, int $id, array $filters = []): string
    {
        $filterHash = ! empty($filters) ? ':'.md5(serialize($filters)) : '';

        return self::CACHE_PREFIX_ASSIGNMENT_LIST."{$type}:{$id}{$filterHash}";
    }

    /**
     * Invalidate cache for a single assignment.
     * Requirements: 28.7
     */
    public function invalidateAssignmentCache(int $id): void
    {
        Cache::forget($this->getAssignmentCacheKey($id));
        Cache::forget($this->getAssignmentCacheKey($id, 'with_questions'));
    }

    /**
     * Invalidate list cache for a specific scope.
     * Uses pattern-based invalidation for flexibility.
     * Requirements: 28.7, 28.10
     */
    public function invalidateListCache(string $type, int $id, ?string $scopeType = null): void
    {
        // For simple cache stores, we invalidate known patterns
        // For Redis with tags, this could use cache tags instead
        $baseKey = self::CACHE_PREFIX_ASSIGNMENT_LIST."{$type}:{$id}";

        // Invalidate the base key (no filters)
        Cache::forget($baseKey);

        // If scope type is provided, invalidate that specific combination
        if ($scopeType !== null) {
            $scopeKey = self::CACHE_PREFIX_ASSIGNMENT_LIST."scope:{$id}:".md5(serialize(['type' => $scopeType]));
            Cache::forget($scopeKey);
        }
    }

    /**
     * Clear all assignment-related caches.
     * Useful for bulk operations or system maintenance.
     * Requirements: 28.7
     */
    public function clearAllCaches(): void
    {
        // This is a simplified implementation
        // For production with Redis, use cache tags: Cache::tags(['assignments'])->flush()
        // For now, we rely on TTL expiration for bulk cache clearing
    }
}
