<?php

declare(strict_types=1);

namespace Modules\Learning\Repositories;

use App\Repositories\BaseRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Modules\Learning\Contracts\Repositories\QuestionRepositoryInterface;
use Modules\Learning\Models\Question;

class QuestionRepository extends BaseRepository implements QuestionRepositoryInterface
{
    protected function model(): string
    {
        return Question::class;
    }

    /**
     * Cache TTL for question data (1 hour).
     * Requirements: 28.7
     */
    protected const CACHE_TTL_QUESTION = 3600;

    /**
     * Cache key prefix for question data.
     */
    protected const CACHE_PREFIX_QUESTION = 'question:';

    /**
     * Cache key prefix for question lists by assignment.
     */
    protected const CACHE_PREFIX_QUESTION_LIST = 'question_list:';

    /**
     * Default eager loading relationships for questions.
     * Prevents N+1 query problems when loading questions with related data.
     * Requirements: 28.5
     */
    protected const DEFAULT_EAGER_LOAD = [
        'assignment:id,title',
    ];

    /**
     * Extended eager loading for detailed question views.
     * Includes answers for complete question data.
     * Requirements: 28.5
     */
    protected const DETAILED_EAGER_LOAD = [
        'assignment:id,title,deadline_at',
        'answers.submission:id,user_id,submitted_at',
        'answers.submission.user:id,name,email',
    ];

    public function create(array $data): Question
    {
        $question = Question::create($data);

        // Invalidate list cache for the assignment
        if (isset($data['assignment_id'])) {
            $this->invalidateAssignmentQuestionsCache($data['assignment_id']);
        }

        return $question;
    }

    public function update(int $id, array $data): Question
    {
        $question = Question::findOrFail($id);
        $assignmentId = $question->assignment_id;

        $question->update($data);

        // Invalidate caches
        $this->invalidateQuestionCache($id);
        $this->invalidateAssignmentQuestionsCache($assignmentId);

        return $question->fresh()->load(self::DEFAULT_EAGER_LOAD);
    }

    public function delete(int $id): bool
    {
        $question = Question::find($id);

        if (! $question) {
            return false;
        }

        $assignmentId = $question->assignment_id;
        $result = Question::destroy($id) > 0;

        if ($result) {
            // Invalidate caches
            $this->invalidateQuestionCache($id);
            $this->invalidateAssignmentQuestionsCache($assignmentId);
        }

        return $result;
    }

    /**
     * Find a question by ID with eager loading and caching.
     * Requirements: 28.5, 28.7
     */
    public function find(int $id): ?Question
    {
        $cacheKey = $this->getQuestionCacheKey($id);

        return Cache::remember($cacheKey, self::CACHE_TTL_QUESTION, function () use ($id) {
            return Question::query()
                ->where('id', $id)
                ->with(self::DEFAULT_EAGER_LOAD)
                ->first();
        });
    }

    /**
     * Find a question with all related data for detailed view.
     * Includes answers with submissions for complete question data.
     * Note: Not cached due to dynamic answer data.
     * Requirements: 28.5
     */
    public function findWithDetails(int $id): ?Question
    {
        return Question::query()
            ->where('id', $id)
            ->with(self::DETAILED_EAGER_LOAD)
            ->first();
    }

    /**
     * Find all questions for an assignment with eager loading and caching.
     * Requirements: 28.5, 28.7
     */
    public function findByAssignment(int $assignmentId): Collection
    {
        $cacheKey = $this->getAssignmentQuestionsCacheKey($assignmentId);

        return Cache::remember($cacheKey, self::CACHE_TTL_QUESTION, function () use ($assignmentId) {
            return Question::where('assignment_id', $assignmentId)
                ->with(self::DEFAULT_EAGER_LOAD)
                ->ordered()
                ->get();
        });
    }

    /**
     * Find questions with answers for grading.
     * Note: Not cached due to dynamic answer data.
     * Requirements: 28.5
     */
    public function findByAssignmentWithAnswers(int $assignmentId): Collection
    {
        return Question::where('assignment_id', $assignmentId)
            ->with([
                'assignment:id,title',
                'answers' => function ($query) {
                    $query->with(['submission:id,user_id,state,submitted_at', 'submission.user:id,name,email']);
                },
            ])
            ->ordered()
            ->get();
    }

    /**
     * Find random questions from a bank with eager loading and caching.
     * Note: Cached by seed to ensure reproducibility.
     * Requirements: 28.5, 28.7
     */
    public function findRandomFromBank(int $assignmentId, int $count, int $seed): Collection
    {
        $cacheKey = $this->getRandomQuestionsCacheKey($assignmentId, $count, $seed);

        return Cache::remember($cacheKey, self::CACHE_TTL_QUESTION, function () use ($assignmentId, $count, $seed) {
            // Set seed for reproducible randomization
            srand($seed);

            $questions = Question::where('assignment_id', $assignmentId)
                ->with(self::DEFAULT_EAGER_LOAD)
                ->get();

            if ($questions->count() <= $count) {
                return $questions->shuffle($seed);
            }

            return $questions->shuffle($seed)->take($count);
        });
    }

    public function reorder(int $assignmentId, array $questionIds): void
    {
        foreach ($questionIds as $order => $questionId) {
            Question::where('id', $questionId)
                ->where('assignment_id', $assignmentId)
                ->update(['order' => $order]);
        }

        // Invalidate caches after reordering
        $this->invalidateAssignmentQuestionsCache($assignmentId);

        // Invalidate individual question caches
        foreach ($questionIds as $questionId) {
            $this->invalidateQuestionCache($questionId);
        }
    }

    /**
     * Find questions that need manual grading for a submission with caching.
     * Requirements: 28.5, 28.7
     */
    public function findManualGradingQuestions(int $assignmentId): Collection
    {
        $cacheKey = $this->getAssignmentQuestionsCacheKey($assignmentId, 'manual');

        return Cache::remember($cacheKey, self::CACHE_TTL_QUESTION, function () use ($assignmentId) {
            return Question::where('assignment_id', $assignmentId)
                ->whereIn('type', ['essay', 'file_upload'])
                ->with(self::DEFAULT_EAGER_LOAD)
                ->ordered()
                ->get();
        });
    }

    /**
     * Find questions that can be auto-graded with caching.
     * Requirements: 28.5, 28.7
     */
    public function findAutoGradableQuestions(int $assignmentId): Collection
    {
        $cacheKey = $this->getAssignmentQuestionsCacheKey($assignmentId, 'auto');

        return Cache::remember($cacheKey, self::CACHE_TTL_QUESTION, function () use ($assignmentId) {
            return Question::where('assignment_id', $assignmentId)
                ->whereIn('type', ['multiple_choice', 'checkbox'])
                ->with(self::DEFAULT_EAGER_LOAD)
                ->ordered()
                ->get();
        });
    }

    /**
     * Generate cache key for a single question.
     * Requirements: 28.7
     */
    protected function getQuestionCacheKey(int $id): string
    {
        return self::CACHE_PREFIX_QUESTION.$id;
    }

    /**
     * Generate cache key for questions by assignment.
     * Requirements: 28.7
     */
    protected function getAssignmentQuestionsCacheKey(int $assignmentId, string $suffix = ''): string
    {
        $key = self::CACHE_PREFIX_QUESTION_LIST."assignment:{$assignmentId}";

        return $suffix ? "{$key}:{$suffix}" : $key;
    }

    /**
     * Generate cache key for random questions from bank.
     * Requirements: 28.7
     */
    protected function getRandomQuestionsCacheKey(int $assignmentId, int $count, int $seed): string
    {
        return self::CACHE_PREFIX_QUESTION_LIST."random:{$assignmentId}:{$count}:{$seed}";
    }

    /**
     * Invalidate cache for a single question.
     * Requirements: 28.7
     */
    public function invalidateQuestionCache(int $id): void
    {
        Cache::forget($this->getQuestionCacheKey($id));
    }

    /**
     * Invalidate all question caches for an assignment.
     * Requirements: 28.7
     */
    public function invalidateAssignmentQuestionsCache(int $assignmentId): void
    {
        // Invalidate main list cache
        Cache::forget($this->getAssignmentQuestionsCacheKey($assignmentId));

        // Invalidate filtered caches
        Cache::forget($this->getAssignmentQuestionsCacheKey($assignmentId, 'manual'));
        Cache::forget($this->getAssignmentQuestionsCacheKey($assignmentId, 'auto'));

        // Note: Random question caches will expire via TTL
        // For production with Redis, use cache tags for more efficient invalidation
    }
}
