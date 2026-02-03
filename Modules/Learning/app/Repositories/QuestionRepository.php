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

        protected const CACHE_TTL_QUESTION = 3600;

        protected const CACHE_PREFIX_QUESTION = 'question:';

        protected const CACHE_PREFIX_QUESTION_LIST = 'question_list:';

        protected const DEFAULT_EAGER_LOAD = [
        'assignment:id,title',
    ];

        protected const DETAILED_EAGER_LOAD = [
        'assignment:id,title,deadline_at',
        'answers.submission:id,user_id,submitted_at',
        'answers.submission.user:id,name,email',
    ];

    public function create(array $data): Question
    {
        $question = Question::create($data);

        
        if (isset($data['assignment_id'])) {
            $this->invalidateAssignmentQuestionsCache($data['assignment_id']);
        }

        return $question;
    }

    public function updateQuestion(int $id, array $data): Question
    {
        $question = Question::findOrFail($id);
        $assignmentId = $question->assignment_id;

        $question->update($data);

        
        $this->invalidateQuestionCache($id);
        $this->invalidateAssignmentQuestionsCache($assignmentId);

        return $question->fresh()->load(self::DEFAULT_EAGER_LOAD);
    }

    public function deleteQuestion(int $id): bool
    {
        $question = Question::find($id);

        if (! $question) {
            return false;
        }

        $assignmentId = $question->assignment_id;
        $result = Question::destroy($id) > 0;

        if ($result) {
            
            $this->invalidateQuestionCache($id);
            $this->invalidateAssignmentQuestionsCache($assignmentId);
        }

        return $result;
    }

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

        public function findWithDetails(int $id): ?Question
    {
        return Question::query()
            ->where('id', $id)
            ->with(self::DETAILED_EAGER_LOAD)
            ->first();
    }

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

        public function findRandomFromBank(int $assignmentId, int $count, int $seed): Collection
    {
        $cacheKey = $this->getRandomQuestionsCacheKey($assignmentId, $count, $seed);

        return Cache::remember($cacheKey, self::CACHE_TTL_QUESTION, function () use ($assignmentId, $count, $seed) {
            
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

    public function searchByAssignment(int $assignmentId, string $query): Collection
    {
        if (trim($query) === '') {
            return $this->findByAssignment($assignmentId);
        }

        $ids = Question::search($query)
            ->query(fn ($q) => $q->where('assignment_id', $assignmentId))
            ->keys()
            ->toArray();

        if (empty($ids)) {
            return new Collection();
        }

        return Question::whereIn('id', $ids)
            ->where('assignment_id', $assignmentId)
            ->with(self::DEFAULT_EAGER_LOAD)
            ->ordered()
            ->get();
    }

    public function reorder(int $assignmentId, array $questionIds): void
    {
        foreach ($questionIds as $order => $questionId) {
            Question::where('id', $questionId)
                ->where('assignment_id', $assignmentId)
                ->update(['order' => $order]);
        }

        
        $this->invalidateAssignmentQuestionsCache($assignmentId);

        
        foreach ($questionIds as $questionId) {
            $this->invalidateQuestionCache($questionId);
        }
    }

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

        protected function getQuestionCacheKey(int $id): string
    {
        return self::CACHE_PREFIX_QUESTION.$id;
    }

        protected function getAssignmentQuestionsCacheKey(int $assignmentId, string $suffix = ''): string
    {
        $key = self::CACHE_PREFIX_QUESTION_LIST."assignment:{$assignmentId}";

        return $suffix ? "{$key}:{$suffix}" : $key;
    }

        protected function getRandomQuestionsCacheKey(int $assignmentId, int $count, int $seed): string
    {
        return self::CACHE_PREFIX_QUESTION_LIST."random:{$assignmentId}:{$count}:{$seed}";
    }

        public function invalidateQuestionCache(int $id): void
    {
        Cache::forget($this->getQuestionCacheKey($id));
    }

        public function invalidateAssignmentQuestionsCache(int $assignmentId): void
    {
        
        Cache::forget($this->getAssignmentQuestionsCacheKey($assignmentId));

        
        Cache::forget($this->getAssignmentQuestionsCacheKey($assignmentId, 'manual'));
        Cache::forget($this->getAssignmentQuestionsCacheKey($assignmentId, 'auto'));

        
        
    }
}
