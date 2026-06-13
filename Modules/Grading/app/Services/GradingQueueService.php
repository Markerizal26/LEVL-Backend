<?php

declare(strict_types=1);

namespace Modules\Grading\Services;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Modules\Auth\Models\User;
use Modules\Grading\Models\Grade;
use Modules\Learning\Enums\QuizGradingStatus;
use Modules\Learning\Enums\QuizQuestionType;
use Modules\Learning\Models\QuizSubmission;
use Modules\Learning\Models\Submission;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\Exceptions\InvalidFilterQuery;
use Spatie\QueryBuilder\QueryBuilder;

class GradingQueueService
{
    private const ALLOWED_FILTERS = ['status', 'workflow_state', 'user_id', 'course_slug', 'assignment_id', 'quiz_id', 'question_id', 'grading_status', 'date_from', 'date_to'];

    private const ASSIGNMENT_FILTERS = ['status', 'workflow_state', 'user_id', 'course_slug', 'assignment_id', 'date_from', 'date_to'];

    private const QUIZ_FILTERS = ['status', 'workflow_state', 'user_id', 'course_slug', 'quiz_id', 'question_id', 'grading_status', 'date_from', 'date_to'];

    private const QUIZ_ONLY_FILTERS = ['quiz_id', 'grading_status'];

    private const ASSIGNMENT_ONLY_FILTERS = ['assignment_id'];

    public function getGradingQueue(array $filters = [], ?int $actorId = null, bool $isInstructor = false): LengthAwarePaginator
    {
        $this->validateFilters((array) data_get($filters, 'filter', []));

        $perPage = max(1, min((int) ($filters['per_page'] ?? 15), 100));
        $page = max(1, (int) ($filters['page'] ?? 1));
        $search = trim((string) ($filters['search'] ?? ''));

        $filterData = (array) data_get($filters, 'filter', []);

        $hasQuizOnlyFilter = ! empty(array_intersect(array_keys($filterData), self::QUIZ_ONLY_FILTERS));
        $hasAssignmentOnlyFilter = ! empty(array_intersect(array_keys($filterData), self::ASSIGNMENT_ONLY_FILTERS));
        $questionId = isset($filterData['question_id']) ? (int) $filterData['question_id'] : null;

        $assignmentSubmissions = $hasQuizOnlyFilter ? collect() : $this->buildAssignmentQuery($filterData, $actorId, $isInstructor, $search)->get();
        $quizSubmissions = $hasAssignmentOnlyFilter
            ? collect()
            : $this->buildQuizQuery($filterData, $actorId, $isInstructor, $search)
                ->get()
                ->flatMap(fn (QuizSubmission $submission) => $this->expandQuizToEssayRows($submission, $questionId));

        $all = $assignmentSubmissions->concat($quizSubmissions)
            ->sortByDesc('submitted_at')
            ->values();

        return new LengthAwarePaginator($all->forPage($page, $perPage)->values(), $all->count(), $perPage, $page, [
            'path' => request()->url(),
            'query' => request()->query(),
        ]);
    }

    private function expandQuizToEssayRows(QuizSubmission $submission, ?int $questionId = null): Collection
    {
        return $submission->answers
            ->filter(fn ($answer) => $answer->question?->type?->value === QuizQuestionType::Essay->value)
            ->filter(fn ($answer) => $questionId === null || (int) $answer->quiz_question_id === $questionId)
            ->map(fn ($answer) => [
                'quiz_submission' => $submission,
                'essay_answer' => $answer,
                'submitted_at' => $submission->submitted_at,
            ])
            ->values();
    }

    public function getGradingStatusDetails(int $submissionId): array
    {
        $submission = Submission::with(['answers', 'grade'])->findOrFail($submissionId);

        $gradedCount = $submission->answers->filter(fn ($a) => $a->score !== null)->count();
        $totalCount = $submission->answers->count();

        $isComplete = $gradedCount === $totalCount;

        return [
            'submission_id' => $submission->id,
            'is_complete' => $isComplete,
            'graded_questions' => $gradedCount,
            'total_questions' => $totalCount,
            'can_finalize' => $isComplete,
            'can_release' => $isComplete && $submission->grade && ! $submission->grade->is_draft,
        ];
    }

    public function getGrade(int $submissionId): ?Grade
    {
        $submission = Submission::with([
            'grade.grader:id,name,email',
            'user:id,name,email',
        ])->findOrFail($submissionId);

        return $submission->grade;
    }

    public function getQuizEssayRow(QuizSubmission $submission, int $questionId): ?array
    {
        $submission->loadMissing([
            'user:id,name,email',
            'quiz:id,title,unit_id,order',
            'quiz.unit:id,order,course_id',
            'quiz.unit.course:id,slug,title,code',
            'answers.question',
        ]);

        $essayAnswer = $submission->answers
            ->first(fn ($answer) => (int) $answer->quiz_question_id === $questionId
                && $answer->question?->type?->value === QuizQuestionType::Essay->value);

        if ($essayAnswer === null) {
            return null;
        }

        return [
            'quiz_submission' => $submission,
            'essay_answer' => $essayAnswer,
            'submitted_at' => $submission->submitted_at,
        ];
    }

    public function getEssaysBatch(array $items, User $actor): array
    {
        $quizIds = [];
        $assignmentIds = [];

        foreach ($items as $item) {
            $submissionId = (int) ($item['submission_id'] ?? 0);
            $type = $item['type'] ?? null;

            if ($submissionId <= 0) {
                continue;
            }

            if ($type === 'quiz' || $type === null) {
                $quizIds[] = $submissionId;
            }

            if ($type === 'assignment' || $type === null) {
                $assignmentIds[] = $submissionId;
            }
        }

        $quizSubmissions = empty($quizIds) ? collect() : QuizSubmission::with([
            'user:id,name,email',
            'answers.question',
        ])->whereIn('id', array_unique($quizIds))->get()->keyBy('id');

        $assignmentSubmissions = empty($assignmentIds) ? collect() : Submission::with([
            'user:id,name,email',
            'assignment:id,unit_id,title,max_score,description',
            'assignment.unit:id,course_id',
            'assignment.unit.course:id',
            'grade',
        ])->whereIn('id', array_unique($assignmentIds))->get()->keyBy('id');

        return array_map(
            fn (array $item) => $this->resolveBatchEssay($item, $quizSubmissions, $assignmentSubmissions, $actor),
            $items
        );
    }

    private function resolveBatchEssay(array $item, Collection $quizSubmissions, Collection $assignmentSubmissions, User $actor): array
    {
        $submissionId = (int) ($item['submission_id'] ?? 0);
        $questionId = (int) ($item['question_id'] ?? 0);
        $type = $item['type'] ?? null;

        $notFound = [
            'submission_id' => $submissionId,
            'question_id' => $questionId,
            'type' => $type,
            'found' => false,
        ];

        if ($type === 'quiz' || $type === null) {
            $submission = $quizSubmissions->get($submissionId);

            if ($submission !== null) {
                $answer = $submission->answers->first(fn ($candidate) => (int) $candidate->quiz_question_id === $questionId
                    && $candidate->question?->type?->value === QuizQuestionType::Essay->value);

                if ($answer !== null) {
                    if (! Gate::forUser($actor)->allows('view', $submission)) {
                        return $notFound;
                    }

                    return [
                        'submission_id' => $submissionId,
                        'question_id' => $questionId,
                        'type' => 'quiz',
                        'found' => true,
                        'question_text' => $answer->question?->content,
                        'student_answer' => $answer->content,
                        'max_score' => $answer->question?->max_score,
                        'current_score' => $answer->score,
                        'is_ai_assisted' => (bool) $answer->is_ai_assisted,
                        'ai_suggested_score' => $answer->ai_suggested_score,
                    ];
                }
            }
        }

        if ($type === 'assignment' || $type === null) {
            $submission = $assignmentSubmissions->get($submissionId);

            if ($submission !== null) {
                if (! Gate::forUser($actor)->allows('grade', $submission)) {
                    return $notFound;
                }

                return [
                    'submission_id' => $submissionId,
                    'question_id' => $questionId > 0 ? $questionId : null,
                    'type' => 'assignment',
                    'found' => true,
                    'question_text' => $submission->assignment?->description,
                    'student_answer' => $submission->answer_text,
                    'max_score' => $submission->assignment?->max_score,
                    'current_score' => $submission->grade?->score ?? $submission->score,
                    'is_ai_assisted' => (bool) ($submission->grade?->is_ai_assisted ?? false),
                    'ai_suggested_score' => $submission->grade?->ai_suggested_score,
                ];
            }
        }

        return $notFound;
    }

    private function validateFilters(array $filterData): void
    {
        $unknown = array_diff(array_keys($filterData), self::ALLOWED_FILTERS);

        if (! empty($unknown)) {
            throw InvalidFilterQuery::filtersNotAllowed(
                Collection::make($unknown),
                Collection::make(self::ALLOWED_FILTERS)
            );
        }
    }

    private function buildAssignmentQuery(array $filterData, ?int $actorId = null, bool $isInstructor = false, string $search = ''): QueryBuilder
    {
        $relevant = array_intersect_key($filterData, array_flip(self::ASSIGNMENT_FILTERS));
        $request = new Request(['filter' => $relevant]);

        $query = QueryBuilder::for(Submission::class, $request)
            ->with([
                'user:id,name,email',
                'assignment:id,title,max_score,submission_type,unit_id,order',
                'assignment.unit:id,order,course_id',
                'assignment.unit.course:id,slug,title,code',
                'media',
            ])
            ->allowedFilters([
                AllowedFilter::exact('status'),
                AllowedFilter::callback('workflow_state', fn ($q, $v) => $q->where('state', $v)),
                AllowedFilter::exact('user_id'),
                AllowedFilter::callback('course_slug', function ($q, $v) {
                    $q->whereHas('assignment.unit.course', fn ($courseQuery) => $courseQuery->where('slug', $v));
                }),
                AllowedFilter::exact('assignment_id'),
                AllowedFilter::callback('date_from', fn ($q, $v) => $q->where('submitted_at', '>=', $v)),
                AllowedFilter::callback('date_to', fn ($q, $v) => $q->where('submitted_at', '<=', $v)),
            ]);

        if ($isInstructor && $actorId) {
            $query->whereHas('assignment.unit.course', $this->courseInstructorScope($actorId));
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', fn ($userQuery) => $userQuery->search($search))
                    ->orWhereHas('assignment', fn ($assignmentQuery) => $assignmentQuery->search($search))
                    ->orWhereHas('assignment.unit.course', fn ($courseQuery) => $courseQuery->search($search));
            });
        }

        return $query;
    }

    private function buildQuizQuery(array $filterData, ?int $actorId = null, bool $isInstructor = false, string $search = ''): QueryBuilder
    {
        $relevant = array_intersect_key($filterData, array_flip(self::QUIZ_FILTERS));
        $request = new Request(['filter' => $relevant]);

        $query = QueryBuilder::for(QuizSubmission::class, $request)
            ->with([
                'user:id,name,email',
                'quiz:id,title,unit_id,order',
                'quiz.unit:id,order,course_id',
                'quiz.unit.course:id,slug,title,code',
                'answers.question',
            ])
            ->allowedFilters([
                AllowedFilter::exact('status'),
                AllowedFilter::callback('workflow_state', function ($q, $v) {
                    $values = is_array($v) ? $v : [$v];
                    $validValues = array_values(array_intersect($values, QuizGradingStatus::values()));

                    if ($validValues === []) {
                        $q->whereRaw('1 = 0');

                        return;
                    }

                    if (count($validValues) === 1) {
                        $q->where('grading_status', $validValues[0]);

                        return;
                    }

                    $q->whereIn('grading_status', $validValues);
                }),
                AllowedFilter::exact('user_id'),
                AllowedFilter::callback('course_slug', function ($q, $v) {
                    $q->whereHas('quiz.unit.course', fn ($courseQuery) => $courseQuery->where('slug', $v));
                }),
                AllowedFilter::exact('quiz_id'),
                AllowedFilter::callback('question_id', function ($q, $v) {
                    $q->whereHas('answers', fn ($answerQuery) => $answerQuery->where('quiz_question_id', (int) $v));
                }),
                AllowedFilter::callback('grading_status', function ($q, $v) {
                    $values = is_array($v) ? $v : [$v];
                    $validValues = array_values(array_intersect($values, QuizGradingStatus::values()));

                    if ($validValues === []) {
                        $q->whereRaw('1 = 0');

                        return;
                    }

                    if (count($validValues) === 1) {
                        $q->where('grading_status', $validValues[0]);

                        return;
                    }

                    $q->whereIn('grading_status', $validValues);
                }),
                AllowedFilter::callback('date_from', fn ($q, $v) => $q->where('submitted_at', '>=', $v)),
                AllowedFilter::callback('date_to', fn ($q, $v) => $q->where('submitted_at', '<=', $v)),
            ]);

        if ($isInstructor && $actorId) {
            $query->whereHas('quiz.unit.course', $this->courseInstructorScope($actorId));
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', fn ($userQuery) => $userQuery->search($search))
                    ->orWhereHas('quiz', fn ($quizQuery) => $quizQuery->search($search))
                    ->orWhereHas('quiz.unit.course', fn ($courseQuery) => $courseQuery->search($search));
            });
        }

        return $query;
    }

    private function courseInstructorScope(int $actorId): Closure
    {
        return function ($courseQuery) use ($actorId) {
            $courseQuery->where(function ($q) use ($actorId) {
                $q->where('instructor_id', $actorId)
                    ->orWhereHas('instructors', fn ($iq) => $iq->whereKey($actorId));
            });
        };
    }
}
