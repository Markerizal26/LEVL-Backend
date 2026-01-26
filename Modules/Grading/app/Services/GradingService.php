<?php

declare(strict_types=1);

namespace Modules\Grading\Services;

use Illuminate\Support\Collection;
use InvalidArgumentException;
use Modules\Grading\Contracts\Services\GradingServiceInterface;
use Modules\Grading\Events\GradeCreated;
use Modules\Grading\Events\GradeOverridden;
use Modules\Grading\Events\GradesReleased;
use Modules\Grading\Jobs\RecalculateGradesJob;
use Modules\Grading\Models\Grade;
use Modules\Grading\Strategies\GradingStrategyFactory;
use Modules\Learning\Enums\SubmissionState;
use Modules\Learning\Models\Answer;
use Modules\Learning\Models\Question;
use Modules\Learning\Models\Submission;

class GradingService implements GradingServiceInterface
{
    public function autoGrade(int $submissionId): void
    {
        $submission = Submission::with(['answers.question', 'assignment.questions'])->findOrFail($submissionId);

        $hasManualQuestions = false;

        foreach ($submission->answers as $answer) {
            $question = $answer->question;

            if (! $question) {
                continue;
            }

            $strategy = GradingStrategyFactory::make($question->type);

            if ($strategy->canAutoGrade()) {
                $score = $strategy->grade($question, $answer);
                $answer->update([
                    'score' => $score,
                    'is_auto_graded' => true,
                ]);
            } else {
                $hasManualQuestions = true;
            }
        }

        $score = $this->calculateScore($submissionId);
        $submission->update(['score' => $score]);

        $newState = $hasManualQuestions
            ? SubmissionState::PendingManualGrading
            : SubmissionState::AutoGraded;

        $submission->transitionTo($newState, $submission->user_id);
    }

    public function manualGrade(int $submissionId, array $grades, ?string $feedback = null): Grade
    {
        $submission = Submission::with(['answers.question', 'assignment'])->findOrFail($submissionId);
        
        $grades = collect($grades)->keyBy('question_id')->toArray();

        $globalScoreOverride = null;
        if (isset($grades['score'])) {
            $globalScoreOverride = (float) $grades['score'];
        }

        if ($globalScoreOverride !== null && $submission->is_late) {
            $assignmentPenalty = $submission->assignment->late_penalty_percent;
            $latePenaltyPercent = $assignmentPenalty !== null
                ? (int) $assignmentPenalty
                : (int) \Modules\Common\Models\SystemSetting::get('learning.late_penalty_percent', 0);
            
            if ($latePenaltyPercent > 0) {
                $penalty = ($globalScoreOverride * $latePenaltyPercent) / 100;
                $globalScoreOverride = max(0, $globalScoreOverride - $penalty);
            }
        }

        foreach ($grades as $questionId => $gradeData) {
            if ($questionId === 'score') {
                continue;
            }

            $answer = $submission->answers->where('question_id', $questionId)->first();

            if (! $answer) {
                continue;
            }

            $question = $answer->question;
            $score = $gradeData['score'] ?? 0;

            $maxScore = $question->max_score ?? 100;
            if ($score < 0 || $score > $maxScore) {
                throw \Modules\Learning\Exceptions\SubmissionException::invalidScore(
                    __('messages.submissions.score_out_of_range', [
                        'question_id' => $questionId,
                        'max_score' => $maxScore
                    ])
                );
            }

            $answer->update([
                'score' => $score,
                'feedback' => $gradeData['feedback'] ?? null,
                'is_auto_graded' => false,
            ]);
        }

        if ($globalScoreOverride === null) {
            if (! $this->validateGradingComplete($submissionId)) {
                throw \Modules\Learning\Exceptions\SubmissionException::notAllowed(
                    __('messages.grading.incomplete_grading')
                );
            }

            $score = $this->calculateScore($submissionId);
        } else {
            $score = $globalScoreOverride;
        }

        $submission->update(['score' => $score]);

        $grade = Grade::updateOrCreate(
            ['submission_id' => $submissionId],
            [
                'source_type' => 'assignment',
                'source_id' => $submission->assignment_id,
                'user_id' => $submission->user_id,
                'graded_by' => auth('api')->id(),
                'score' => $score,
                'max_score' => $submission->assignment->max_score ?? 100,
                'feedback' => $feedback,
                'is_draft' => false,
                'graded_at' => now(),
            ]
        );

        $instructorId = auth('api')->id();
        if ($instructorId !== null) {
            GradeCreated::dispatch($grade, (int) $instructorId);
        }

        $submission->transitionTo(SubmissionState::Graded, (int) auth('api')->id());

        return $grade;
    }

    public function saveDraftGrade(int $submissionId, array $partialGrades): void
    {
        $submission = Submission::with(['answers.question', 'grade'])->findOrFail($submissionId);
        
        $partialGrades = collect($partialGrades)->keyBy('question_id')->toArray();

        if ($submission->grade && !$submission->grade->is_draft) {
            throw new \InvalidArgumentException(
                __('messages.grading.cannot_draft_finalized')
            );
        }

        foreach ($partialGrades as $questionId => $gradeData) {
            $answer = $submission->answers->where('question_id', $questionId)->first();

            if (! $answer) {
                continue;
            }

            $question = $answer->question;
            $score = $gradeData['score'] ?? null;

            if ($score !== null) {
                $maxScore = $question->max_score ?? 100;
                if ($score < 0 || $score > $maxScore) {
                    throw new InvalidArgumentException(
                        __('messages.grading.invalid_score')
                    );
                }
            }

            $answer->update([
                'score' => $score,
                'feedback' => $gradeData['feedback'] ?? null,
            ]);
        }

        Grade::updateOrCreate(
            ['submission_id' => $submissionId],
            [
                'source_type' => 'assignment',
                'source_id' => $submission->assignment_id,
                'user_id' => $submission->user_id,
                'graded_by' => auth('api')->id(),
                'is_draft' => true,
            ]
        );
    }

    public function getDraftGrade(int $submissionId): ?array
    {
        $submission = Submission::with(['answers.question', 'grade'])->findOrFail($submissionId);

        if (! $submission->grade || ! $submission->grade->is_draft) {
            return null;
        }

        $draftGrades = [];
        foreach ($submission->answers as $answer) {
            $draftGrades[$answer->question_id] = [
                'score' => $answer->score,
                'feedback' => $answer->feedback,
            ];
        }

        return [
            'submission_id' => $submissionId,
            'graded_by' => $submission->grade->graded_by,
            'grades' => $draftGrades,
            'overall_feedback' => $submission->grade->feedback,
        ];
    }

    public function calculateScore(int $submissionId): float
    {
        $submission = Submission::with(['answers.question'])->findOrFail($submissionId);

        $totalWeightedScore = 0;
        $totalWeight = 0;

        foreach ($submission->answers as $answer) {
            $question = $answer->question;

            if (! $question || $answer->score === null) {
                continue;
            }

            $weight = $question->weight ?? 1;
            $maxScore = $question->max_score ?? 100;
            $normalizedScore = ($answer->score / $maxScore) * 100;

            $totalWeightedScore += $normalizedScore * $weight;
            $totalWeight += $weight;
        }

        if ($totalWeight === 0) {
            return 0;
        }

        return round($totalWeightedScore / $totalWeight, 2);
    }

    public function recalculateAfterAnswerKeyChange(int $questionId): void
    {
        $question = Question::findOrFail($questionId);

        RecalculateGradesJob::dispatch(
            $questionId,
            $question->answer_key ?? [],
            $question->answer_key ?? [],
            auth('api')->id()
        );
    }

    public function overrideGrade(int $submissionId, float $score, string $reason): void
    {
        if (empty($reason)) {
            throw new InvalidArgumentException(__('messages.grading.reason_required'));
        }

        $submission = Submission::findOrFail($submissionId);
        $grade = Grade::where('submission_id', $submissionId)->firstOrFail();

        $oldScore = (float) $grade->score;

        $instructorId = auth('api')->id();
        $grade->override($score, $reason, $instructorId);

        $submission->update(['score' => $score]);

        if ($instructorId !== null) {
            GradeOverridden::dispatch($grade, $oldScore, $score, $reason, (int) $instructorId);
        }
    }

    public function getGradingQueue(array $filters = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $perPage = (int) ($filters['per_page'] ?? 15);

        $query = \Spatie\QueryBuilder\QueryBuilder::for(Submission::class)
            ->with([
                'user:id,name,email',
                'assignment:id,title,max_score',
                'answers.question'
            ])
            ->allowedFilters([
                \Spatie\QueryBuilder\AllowedFilter::exact('assignment_id'),
                \Spatie\QueryBuilder\AllowedFilter::exact('user_id'),
                \Spatie\QueryBuilder\AllowedFilter::exact('state'),
                \Spatie\QueryBuilder\AllowedFilter::callback('date_from', fn ($q, $v) => $q->where('submitted_at', '>=', $v)),
                \Spatie\QueryBuilder\AllowedFilter::callback('date_to', fn ($q, $v) => $q->where('submitted_at', '<=', $v)),
            ])
            ->allowedSorts(['submitted_at', 'created_at']);

        // Default filter untuk state = pending_manual_grading jika tidak ada filter state
        $request = new \Illuminate\Http\Request($filters);
        if (!$request->has('filter.state')) {
            $query->where('state', SubmissionState::PendingManualGrading->value);
        }

        return $query
            ->defaultSort('submitted_at')
            ->paginate($perPage)
            ->appends($filters);
    }

    public function returnToQueue(int $submissionId): void
    {
        $submission = Submission::findOrFail($submissionId);

        if ($submission->state !== SubmissionState::Graded) {
            throw new InvalidArgumentException(__('messages.grading.submission_not_graded'));
        }

        $submission->update(['state' => SubmissionState::PendingManualGrading->value]);

        $grade = Grade::where('submission_id', $submissionId)->first();
        if ($grade) {
            $grade->update(['is_draft' => true]);
        }
    }

    public function validateGradingComplete(int $submissionId): bool
    {
        $submission = Submission::with(['answers.question'])->findOrFail($submissionId);
        $questionSet = $submission->question_set;

        foreach ($submission->answers as $answer) {
            if (! empty($questionSet) && ! in_array($answer->question_id, $questionSet)) {
                continue;
            }

            if ($answer->score === null) {
                return false;
            }
        }

        return true;
    }

    public function getGradingStatusDetails(int $submissionId): array
    {
        $isComplete = $this->validateGradingComplete($submissionId);
        $submission = Submission::with(['answers.question', 'grade'])->findOrFail($submissionId);

        $gradedCount = $submission->answers->filter(fn ($a) => $a->score !== null)->count();
        $totalCount = $submission->answers->count();

        return [
            'submission_id' => $submission->id,
            'is_complete' => $isComplete,
            'graded_questions' => $gradedCount,
            'total_questions' => $totalCount,
            'can_finalize' => $isComplete,
            'can_release' => $isComplete && $submission->grade && ! $submission->grade->is_draft,
        ];
    }

    public function releaseGrade(int $submissionId): void
    {
        $submission = Submission::with('grade')->findOrFail($submissionId);

        if (!$submission->grade) {
            throw new \InvalidArgumentException(__('messages.grading.no_grade_exists'));
        }

        if ($submission->grade->is_draft) {
            throw new \InvalidArgumentException(__('messages.grading.cannot_release_draft'));
        }

        if ($submission->state !== SubmissionState::Graded) {
            throw new \InvalidArgumentException(__('messages.grading.submission_not_graded'));
        }

        $submission->grade->release();
        $submission->transitionTo(SubmissionState::Released, (int) auth('api')->id());

        GradesReleased::dispatch(collect([$submission]), auth('api')->id());
    }

    public function validateBulkReleaseGrades(array $submissionIds): array
    {
        $errors = [];

        if (empty($submissionIds)) {
            return ['valid' => false, 'errors' => ['No submission IDs provided']];
        }

        $submissions = Submission::with('grade')->whereIn('id', $submissionIds)->get();
        $foundIds = $submissions->pluck('id')->toArray();
        $missingIds = array_diff($submissionIds, $foundIds);

        foreach ($missingIds as $missingId) {
            $errors[] = "Submission {$missingId} not found";
        }

        foreach ($submissions as $submission) {
            if (! $submission->grade) {
                $errors[] = "Submission {$submission->id} has no grade to release";
                continue;
            }

            if ($submission->grade->is_draft) {
                $errors[] = "Submission {$submission->id} has a draft grade that cannot be released";
                continue;
            }

            if ($submission->state === SubmissionState::Released) {
                $errors[] = "Submission {$submission->id} is already released";
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    public function handleBulkRelease(array $submissionIds, int $userId, bool $async = false): array
    {
        if ($async) {
            $validation = $this->validateBulkReleaseGrades($submissionIds);
            if (! $validation['valid'] && count($validation['errors']) === count($submissionIds)) {
                 throw new InvalidArgumentException('Bulk release validation failed: '.implode('; ', $validation['errors']));
            }
            BulkReleaseGradesJob::dispatch($submissionIds, $userId);
            return ['async' => true, 'count' => count($submissionIds)];
        }
        return array_merge(['async' => false], $this->bulkReleaseGrades($submissionIds));
    }

    public function bulkReleaseGrades(array $submissionIds): array
    {
        $validation = $this->validateBulkReleaseGrades($submissionIds);

        if (! $validation['valid'] && count($validation['errors']) === count($submissionIds)) {
            throw new InvalidArgumentException(__('messages.grading.bulk_validation_failed', ['errors' => implode('; ', $validation['errors'])]));
        }

        $successCount = 0;
        $failedCount = 0;
        $errors = [];
        $releasedSubmissions = collect();
        $foundIds = [];

        Submission::with('grade')
            ->whereIn('id', $submissionIds)
            ->chunkById(100, function ($submissions) use (&$successCount, &$failedCount, &$errors, &$releasedSubmissions, &$foundIds) {
                foreach ($submissions as $submission) {
                    $foundIds[] = $submission->id;
                    
                    if (! $submission->grade) {
                        $failedCount++;
                        $errors[] = "Submission {$submission->id} has no grade to release";
                        continue;
                    }

                    if ($submission->grade->is_draft) {
                        $failedCount++;
                        $errors[] = "Submission {$submission->id} has a draft grade that cannot be released";
                        continue;
                    }

                    if ($submission->state === SubmissionState::Released) {
                        $failedCount++;
                        $errors[] = "Submission {$submission->id} is already released";
                        continue;
                    }

                    $submission->grade->release();
                    $submission->transitionTo(SubmissionState::Released, (int) auth('api')->id());
                    $releasedSubmissions->push($submission);
                    $successCount++;
                }
            });

        $missingIds = array_diff($submissionIds, $foundIds);
        foreach ($missingIds as $missingId) {
            $failedCount++;
            $errors[] = "Submission {$missingId} not found";
        }

        if ($releasedSubmissions->isNotEmpty()) {
            GradesReleased::dispatch($releasedSubmissions, auth('api')->id());
        }

        return [
            'success' => $successCount,
            'failed' => $failedCount,
            'submissions' => $releasedSubmissions,
            'errors' => $errors,
        ];
    }

    public function validateBulkApplyFeedback(array $submissionIds): array
    {
        $errors = [];

        if (empty($submissionIds)) {
            return ['valid' => false, 'errors' => ['No submission IDs provided']];
        }

        $submissions = Submission::whereIn('id', $submissionIds)->get();
        $foundIds = $submissions->pluck('id')->toArray();
        $missingIds = array_diff($submissionIds, $foundIds);

        foreach ($missingIds as $missingId) {
            $errors[] = "Submission {$missingId} not found";
        }

        foreach ($submissions as $submission) {
            if ($submission->state === SubmissionState::InProgress) {
                $errors[] = "Submission {$submission->id} is still in progress and cannot receive feedback";
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    public function handleBulkFeedback(array $submissionIds, string $feedback, int $userId, bool $async = false): array
    {
        if ($async) {
            $validation = $this->validateBulkApplyFeedback($submissionIds);
             if (! $validation['valid'] && count($validation['errors']) === count($submissionIds)) {
                throw new InvalidArgumentException(__('messages.grading.bulk_validation_failed', ['errors' => implode('; ', $validation['errors'])]));
            }
            \Modules\Grading\Jobs\BulkApplyFeedbackJob::dispatch($submissionIds, $feedback, $userId);
            return ['async' => true, 'count' => count($submissionIds)];
        }
        return array_merge(['async' => false], $this->bulkApplyFeedback($submissionIds, $feedback));
    }

    public function bulkApplyFeedback(array $submissionIds, string $feedback): array
    {
        if (empty(trim($feedback))) {
            throw new InvalidArgumentException(__('messages.grading.feedback_required'));
        }

        $validation = $this->validateBulkApplyFeedback($submissionIds);

        if (! $validation['valid'] && count($validation['errors']) === count($submissionIds)) {
            throw new InvalidArgumentException(__('messages.grading.bulk_validation_failed', ['errors' => implode('; ', $validation['errors'])]));
        }

        $successCount = 0;
        $failedCount = 0;
        $errors = [];
        $updatedSubmissions = collect();
        $foundIds = [];

        Submission::with('grade')
            ->whereIn('id', $submissionIds)
            ->chunkById(100, function ($submissions) use (&$successCount, &$failedCount, &$errors, &$updatedSubmissions, &$foundIds, $feedback) {
                foreach ($submissions as $submission) {
                    $foundIds[] = $submission->id;

                    if ($submission->state === SubmissionState::InProgress) {
                        $failedCount++;
                        $errors[] = "Submission {$submission->id} is still in progress and cannot receive feedback";
                        continue;
                    }

                    Grade::updateOrCreate(
                        ['submission_id' => $submission->id],
                        [
                            'source_type' => 'assignment',
                            'source_id' => $submission->assignment_id,
                            'user_id' => $submission->user_id,
                            'feedback' => $feedback,
                        ]
                    );

                    $updatedSubmissions->push($submission);
                    $successCount++;
                }
            });

        $missingIds = array_diff($submissionIds, $foundIds);
        foreach ($missingIds as $missingId) {
            $failedCount++;
            $errors[] = "Submission {$missingId} not found";
        }

        return [
            'success' => $successCount,
            'failed' => $failedCount,
            'submissions' => $updatedSubmissions,
            'errors' => $errors,
        ];
    }

    public function recalculateCourseGrade(int $studentId, int $courseId): ?float
    {
        if (! $courseId) {
            return null;
        }

        $courseGrade = $this->calculateCourseGrade($studentId, $courseId);
        $this->updateCourseGradeRecord($studentId, $courseId, $courseGrade);

        return $courseGrade;
    }

    public function calculateCourseGrade(int $studentId, int $courseId): float
    {
        $assignments = $this->getAssignmentsForCourse($courseId);

        if ($assignments->isEmpty()) {
            return 0.0;
        }

        $totalWeightedScore = 0.0;
        $totalWeight = 0.0;

        foreach ($assignments as $assignment) {
            $highestSubmission = Submission::query()
                ->where('assignment_id', $assignment->id)
                ->where('user_id', $studentId)
                ->whereNotNull('score')
                ->whereNotIn('state', [SubmissionState::InProgress->value])
                ->whereHas('grade', fn ($q) => $q->where('is_draft', false))
                ->orderByDesc('score')
                ->first();

            if ($highestSubmission && $highestSubmission->score !== null) {
                $weight = $assignment->max_score ?? 100;
                $normalizedScore = ($highestSubmission->score / 100) * $weight;
                $totalWeightedScore += $normalizedScore;
                $totalWeight += $weight;
            }
        }

        if ($totalWeight === 0.0) {
            return 0.0;
        }

        return round(($totalWeightedScore / $totalWeight) * 100, 2);
    }

    private function getAssignmentsForCourse(int $courseId): \Illuminate\Support\Collection
    {
        $course = \Modules\Schemes\Models\Course::with(['units.lessons'])->find($courseId);

        if (! $course) {
            return collect();
        }

        $assignmentIds = collect();

        $courseAssignments = \Modules\Learning\Models\Assignment::query()
            ->where('assignable_type', \Modules\Schemes\Models\Course::class)
            ->where('assignable_id', $courseId)
            ->pluck('id');
        $assignmentIds = $assignmentIds->merge($courseAssignments);

        foreach ($course->units as $unit) {
            $unitAssignments = \Modules\Learning\Models\Assignment::query()
                ->where('assignable_type', \Modules\Schemes\Models\Unit::class)
                ->where('assignable_id', $unit->id)
                ->pluck('id');
            $assignmentIds = $assignmentIds->merge($unitAssignments);

            foreach ($unit->lessons as $lesson) {
                $lessonAssignments = \Modules\Learning\Models\Assignment::query()
                    ->where(function ($q) use ($lesson) {
                        $q->where('assignable_type', \Modules\Schemes\Models\Lesson::class)
                            ->where('assignable_id', $lesson->id);
                    })
                    ->orWhere('lesson_id', $lesson->id)
                    ->pluck('id');
                $assignmentIds = $assignmentIds->merge($lessonAssignments);
            }
        }

        return \Modules\Learning\Models\Assignment::whereIn('id', $assignmentIds->unique())->get();
    }

    private function updateCourseGradeRecord(int $studentId, int $courseId, float $grade): void
    {
        Grade::updateOrCreate(
            [
                'source_type' => 'course',
                'source_id' => $courseId,
                'user_id' => $studentId,
            ],
            [
                'score' => $grade,
                'max_score' => 100,
                'is_draft' => false,
                'graded_at' => now(),
            ]
        );
    }


}
