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
    /**
     * Auto-grade a submission.
     */
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

        // Calculate and update submission score
        $score = $this->calculateScore($submissionId);
        $submission->update(['score' => $score]);

        // Transition state based on whether manual grading is needed
        $newState = $hasManualQuestions
            ? SubmissionState::PendingManualGrading
            : SubmissionState::AutoGraded;

        $submission->transitionTo($newState);

        // Check for new high score and trigger course grade recalculation (Requirements 22.4, 22.5)
        if (! $hasManualQuestions) {
            $this->checkAndDispatchNewHighScore($submission->fresh());
        }
    }

    /**
     * Manual grade a submission.
     *
     * @throws InvalidArgumentException if score is out of range or grading is incomplete
     */
    public function manualGrade(int $submissionId, array $grades, ?string $feedback = null): Grade
    {
        $submission = Submission::with(['answers.question', 'assignment'])->findOrFail($submissionId);

        // Validate and update answer scores
        foreach ($grades as $questionId => $gradeData) {
            $answer = $submission->answers->where('question_id', $questionId)->first();

            if (! $answer) {
                continue;
            }

            $question = $answer->question;
            $score = $gradeData['score'] ?? 0;

            // Validate score range (Requirements 12.1)
            $maxScore = $question->max_score ?? 100;
            if ($score < 0 || $score > $maxScore) {
                throw new InvalidArgumentException(
                    "Score for question {$questionId} must be between 0 and {$maxScore}"
                );
            }

            $answer->update([
                'score' => $score,
                'feedback' => $gradeData['feedback'] ?? null,
                'is_auto_graded' => false,
            ]);
        }

        // Validate all required questions have scores (Requirements 11.4, 11.5)
        if (! $this->validateGradingComplete($submissionId)) {
            throw new InvalidArgumentException(
                'Cannot finalize grading: not all required questions have been graded'
            );
        }

        // Calculate final score
        $score = $this->calculateScore($submissionId);
        $submission->update(['score' => $score]);

        // Create or update grade record
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

        // Dispatch event for audit logging (Requirements 20.2)
        $instructorId = auth('api')->id();
        if ($instructorId !== null) {
            GradeCreated::dispatch($grade, (int) $instructorId);
        }

        // Transition to graded state
        $submission->transitionTo(SubmissionState::Graded);

        // Check for new high score and trigger course grade recalculation (Requirements 22.4, 22.5)
        $this->checkAndDispatchNewHighScore($submission->fresh());

        return $grade;
    }

    /**
     * Save draft grades without finalizing.
     * Does not change submission state (Requirements 11.1, 11.2).
     */
    public function saveDraftGrade(int $submissionId, array $partialGrades): void
    {
        $submission = Submission::with(['answers.question', 'assignment'])->findOrFail($submissionId);

        // Update answer scores with validation
        foreach ($partialGrades as $questionId => $gradeData) {
            $answer = $submission->answers->where('question_id', $questionId)->first();

            if (! $answer) {
                continue;
            }

            $question = $answer->question;
            $score = $gradeData['score'] ?? null;

            // Validate score range if provided (Requirements 12.1)
            if ($score !== null) {
                $maxScore = $question->max_score ?? 100;
                if ($score < 0 || $score > $maxScore) {
                    throw new InvalidArgumentException(
                        "Score for question {$questionId} must be between 0 and {$maxScore}"
                    );
                }
            }

            $answer->update([
                'score' => $score,
                'feedback' => $gradeData['feedback'] ?? null,
            ]);
        }

        // Create or update draft grade record
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

    /**
     * Get draft grades for a submission (Requirements 11.3).
     */
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

    /**
     * Calculate weighted score for a submission.
     */
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

    /**
     * Recalculate grades after answer key change.
     * Dispatches a background job to handle the recalculation.
     * Manual grades are preserved during recalculation (Requirements 15.6).
     */
    public function recalculateAfterAnswerKeyChange(int $questionId): void
    {
        $question = Question::findOrFail($questionId);

        // Dispatch job to handle recalculation in background (Requirements 15.1, 15.2, 15.3)
        RecalculateGradesJob::dispatch(
            $questionId,
            $question->answer_key ?? [],
            $question->answer_key ?? [],
            auth('api')->id()
        );
    }

    /**
     * Override a grade with justification.
     */
    public function overrideGrade(int $submissionId, float $score, string $reason): void
    {
        if (empty($reason)) {
            throw new InvalidArgumentException('Override reason is required');
        }

        $submission = Submission::findOrFail($submissionId);
        $grade = Grade::where('submission_id', $submissionId)->firstOrFail();

        // Store old score for audit logging
        $oldScore = (float) $grade->score;

        $instructorId = auth('api')->id();
        $grade->override($score, $reason, $instructorId);

        $submission->update(['score' => $score]);

        // Dispatch event for audit logging (Requirements 20.4)
        if ($instructorId !== null) {
            GradeOverridden::dispatch($grade, $oldScore, $score, $reason, (int) $instructorId);
        }
    }

    /**
     * Get submissions pending manual grading with metadata (Requirements 10.1-10.4).
     */
    public function getGradingQueue(array $filters = []): Collection
    {
        $query = Submission::with([
            'user:id,name,email',
            'assignment:id,title,max_score',
            'answers' => function ($q) {
                $q->with(['question' => function ($q) {
                    $q->select('id', 'type', 'content', 'max_score');
                }]);
            },
        ])
            ->where('state', SubmissionState::PendingManualGrading->value)
            ->orderBy('submitted_at', 'asc'); // Oldest first (Requirements 10.2)

        // Apply filters (Requirements 10.3)
        if (isset($filters['assignment_id'])) {
            $query->where('assignment_id', $filters['assignment_id']);
        }

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['date_from'])) {
            $query->where('submitted_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('submitted_at', '<=', $filters['date_to']);
        }

        $submissions = $query->get();

        // Add metadata for each submission (Requirements 10.4)
        return $submissions->map(function ($submission) {
            $questionsRequiringGrading = $submission->answers
                ->filter(fn ($answer) => $answer->score === null && ! $answer->question?->canAutoGrade())
                ->map(fn ($answer) => [
                    'question_id' => $answer->question_id,
                    'question_type' => $answer->question?->type?->value,
                    'question_content' => $answer->question?->content,
                ])
                ->values();

            return [
                'submission_id' => $submission->id,
                'student_name' => $submission->user?->name,
                'student_email' => $submission->user?->email,
                'assignment_id' => $submission->assignment_id,
                'assignment_title' => $submission->assignment?->title,
                'submitted_at' => $submission->submitted_at,
                'is_late' => $submission->is_late,
                'questions_requiring_grading' => $questionsRequiringGrading,
                'total_questions' => $submission->answers->count(),
                'graded_questions' => $submission->answers->filter(fn ($a) => $a->score !== null)->count(),
            ];
        });
    }

    /**
     * Return a submission to the grading queue (Requirements 10.6).
     */
    public function returnToQueue(int $submissionId): void
    {
        $submission = Submission::findOrFail($submissionId);

        // Can only return from Graded state back to PendingManualGrading
        if ($submission->state !== SubmissionState::Graded) {
            throw new InvalidArgumentException(
                'Can only return graded submissions to the queue'
            );
        }

        // Update the state directly since this is a special case
        $submission->update(['state' => SubmissionState::PendingManualGrading->value]);

        // Mark the grade as draft again
        $grade = Grade::where('submission_id', $submissionId)->first();
        if ($grade) {
            $grade->update(['is_draft' => true]);
        }
    }

    /**
     * Validate that all required questions have been graded (Requirements 11.4, 11.5).
     */
    public function validateGradingComplete(int $submissionId): bool
    {
        $submission = Submission::with(['answers.question'])->findOrFail($submissionId);

        foreach ($submission->answers as $answer) {
            // All answers must have a score
            if ($answer->score === null) {
                return false;
            }
        }

        return true;
    }

    /**
     * Release a grade to make it visible to the student.
     *
     * Dispatches GradesReleased event to trigger notifications.
     * Requirements: 14.6 - WHEN instructor releases grades in deferred mode, THE System SHALL notify students
     */
    public function releaseGrade(int $submissionId): void
    {
        $submission = Submission::with('grade')->findOrFail($submissionId);

        if (! $submission->grade) {
            throw new InvalidArgumentException('No grade exists for this submission');
        }

        if ($submission->grade->is_draft) {
            throw new InvalidArgumentException('Cannot release a draft grade');
        }

        $submission->grade->release();
        $submission->transitionTo(SubmissionState::Released);

        // Dispatch event to trigger notifications (Requirements 14.6)
        GradesReleased::dispatch(collect([$submission]), auth('api')->id());
    }

    /**
     * Validate submissions before bulk grade release.
     *
     * Requirements: 26.5 - THE System SHALL validate bulk operations before execution and report any errors
     *
     * @param  array<int>  $submissionIds  Array of submission IDs to validate
     * @return array{valid: bool, errors: array<string>} Validation result with errors if any
     */
    public function validateBulkReleaseGrades(array $submissionIds): array
    {
        $errors = [];

        if (empty($submissionIds)) {
            return ['valid' => false, 'errors' => ['No submission IDs provided']];
        }

        $submissions = Submission::with('grade')
            ->whereIn('id', $submissionIds)
            ->get();

        // Check if all requested submissions were found
        $foundIds = $submissions->pluck('id')->toArray();
        $missingIds = array_diff($submissionIds, $foundIds);

        foreach ($missingIds as $missingId) {
            $errors[] = "Submission {$missingId} not found";
        }

        // Validate each submission
        foreach ($submissions as $submission) {
            if (! $submission->grade) {
                $errors[] = "Submission {$submission->id} has no grade to release";

                continue;
            }

            if ($submission->grade->is_draft) {
                $errors[] = "Submission {$submission->id} has a draft grade that cannot be released";

                continue;
            }

            // Check if already released
            if ($submission->state === SubmissionState::Released) {
                $errors[] = "Submission {$submission->id} is already released";
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Bulk release grades for multiple submissions.
     *
     * Dispatches GradesReleased event to trigger notifications for all released submissions.
     * Requirements: 14.6 - WHEN instructor releases grades in deferred mode, THE System SHALL notify students
     * Requirements: 26.2 - THE System SHALL support bulk grade release for submissions in deferred review mode
     * Requirements: 26.5 - THE System SHALL validate bulk operations before execution and report any errors
     *
     * @param  array<int>  $submissionIds  Array of submission IDs to release
     * @return array{success: int, failed: int, submissions: Collection<int, Submission>, errors: array<string>} Result with success/failure counts
     *
     * @throws InvalidArgumentException if validation fails for all submissions
     */
    public function bulkReleaseGrades(array $submissionIds): array
    {
        // Validate before execution (Requirements 26.5)
        $validation = $this->validateBulkReleaseGrades($submissionIds);

        if (! $validation['valid'] && count($validation['errors']) === count($submissionIds)) {
            throw new InvalidArgumentException(
                'Bulk release validation failed: '.implode('; ', $validation['errors'])
            );
        }

        $submissions = Submission::with('grade')
            ->whereIn('id', $submissionIds)
            ->get();

        $successCount = 0;
        $failedCount = 0;
        $errors = [];
        $releasedSubmissions = collect();

        // Release grades for valid submissions
        foreach ($submissions as $submission) {
            // Skip submissions that can't be released
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

            // Release the grade
            $submission->grade->release();
            $submission->transitionTo(SubmissionState::Released);
            $releasedSubmissions->push($submission);
            $successCount++;
        }

        // Add missing submission errors
        $foundIds = $submissions->pluck('id')->toArray();
        $missingIds = array_diff($submissionIds, $foundIds);
        foreach ($missingIds as $missingId) {
            $failedCount++;
            $errors[] = "Submission {$missingId} not found";
        }

        // Dispatch single event for all released submissions (Requirements 14.6)
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

    /**
     * Validate submissions before bulk feedback application.
     *
     * Requirements: 26.5 - THE System SHALL validate bulk operations before execution and report any errors
     *
     * @param  array<int>  $submissionIds  Array of submission IDs to validate
     * @return array{valid: bool, errors: array<string>} Validation result with errors if any
     */
    public function validateBulkApplyFeedback(array $submissionIds): array
    {
        $errors = [];

        if (empty($submissionIds)) {
            return ['valid' => false, 'errors' => ['No submission IDs provided']];
        }

        $submissions = Submission::whereIn('id', $submissionIds)->get();

        // Check if all requested submissions were found
        $foundIds = $submissions->pluck('id')->toArray();
        $missingIds = array_diff($submissionIds, $foundIds);

        foreach ($missingIds as $missingId) {
            $errors[] = "Submission {$missingId} not found";
        }

        // Validate each submission exists (basic validation)
        // Feedback can be applied to submissions in any state except in_progress
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

    /**
     * Bulk apply feedback to multiple submissions.
     *
     * Requirements: 26.4 - THE System SHALL support bulk feedback application to selected submissions
     * Requirements: 26.5 - THE System SHALL validate bulk operations before execution and report any errors
     *
     * @param  array<int>  $submissionIds  Array of submission IDs to apply feedback to
     * @param  string  $feedback  The feedback text to apply to all submissions
     * @return array{success: int, failed: int, submissions: Collection<int, Submission>, errors: array<string>} Result with success/failure counts
     *
     * @throws InvalidArgumentException if validation fails for all submissions or feedback is empty
     */
    public function bulkApplyFeedback(array $submissionIds, string $feedback): array
    {
        if (empty(trim($feedback))) {
            throw new InvalidArgumentException('Feedback cannot be empty');
        }

        // Validate before execution (Requirements 26.5)
        $validation = $this->validateBulkApplyFeedback($submissionIds);

        if (! $validation['valid'] && count($validation['errors']) === count($submissionIds)) {
            throw new InvalidArgumentException(
                'Bulk feedback validation failed: '.implode('; ', $validation['errors'])
            );
        }

        $submissions = Submission::with('grade')
            ->whereIn('id', $submissionIds)
            ->get();

        $successCount = 0;
        $failedCount = 0;
        $errors = [];
        $updatedSubmissions = collect();

        // Apply feedback to valid submissions
        foreach ($submissions as $submission) {
            // Skip submissions that can't receive feedback
            if ($submission->state === SubmissionState::InProgress) {
                $failedCount++;
                $errors[] = "Submission {$submission->id} is still in progress and cannot receive feedback";

                continue;
            }

            // Update or create grade record with feedback
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

        // Add missing submission errors
        $foundIds = $submissions->pluck('id')->toArray();
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

    /**
     * Recalculate course grade for a student.
     * Uses highest scores from all assignments in the course.
     * Requirements: 22.2, 22.5
     */
    public function recalculateCourseGrade(int $studentId, int $courseId): ?float
    {
        if (! $courseId) {
            return null;
        }

        $courseGrade = $this->calculateCourseGrade($studentId, $courseId);

        // Update or create course grade record
        $this->updateCourseGradeRecord($studentId, $courseId, $courseGrade);

        return $courseGrade;
    }

    /**
     * Calculate course grade using highest scores for each assignment.
     * Requirements: 22.2
     */
    public function calculateCourseGrade(int $studentId, int $courseId): float
    {
        // Get all assignments for the course (including those attached to lessons and units)
        $assignments = $this->getAssignmentsForCourse($courseId);

        if ($assignments->isEmpty()) {
            return 0.0;
        }

        $totalWeightedScore = 0.0;
        $totalWeight = 0.0;

        foreach ($assignments as $assignment) {
            // Get highest score submission for this assignment (Requirements 22.1, 22.2)
            $highestSubmission = Submission::query()
                ->where('assignment_id', $assignment->id)
                ->where('user_id', $studentId)
                ->whereNotNull('score')
                ->whereNotIn('state', [SubmissionState::InProgress->value])
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

    /**
     * Get all assignments for a course (including those attached to lessons and units).
     */
    private function getAssignmentsForCourse(int $courseId): \Illuminate\Support\Collection
    {
        $course = \Modules\Schemes\Models\Course::with(['units.lessons'])->find($courseId);

        if (! $course) {
            return collect();
        }

        $assignmentIds = collect();

        // Get assignments directly attached to the course
        $courseAssignments = \Modules\Learning\Models\Assignment::query()
            ->where('assignable_type', \Modules\Schemes\Models\Course::class)
            ->where('assignable_id', $courseId)
            ->pluck('id');
        $assignmentIds = $assignmentIds->merge($courseAssignments);

        // Get assignments attached to units
        foreach ($course->units as $unit) {
            $unitAssignments = \Modules\Learning\Models\Assignment::query()
                ->where('assignable_type', \Modules\Schemes\Models\Unit::class)
                ->where('assignable_id', $unit->id)
                ->pluck('id');
            $assignmentIds = $assignmentIds->merge($unitAssignments);

            // Get assignments attached to lessons
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

    /**
     * Update or create course grade record.
     */
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

    /**
     * Check if a submission is a new high score and dispatch event if so.
     * Requirements: 22.4, 22.5
     */
    private function checkAndDispatchNewHighScore(Submission $submission): void
    {
        if ($submission->score === null) {
            return;
        }

        // Get all other submissions for this student/assignment
        $otherSubmissions = Submission::query()
            ->where('assignment_id', $submission->assignment_id)
            ->where('user_id', $submission->user_id)
            ->where('id', '!=', $submission->id)
            ->whereNotNull('score')
            ->whereNotIn('state', [SubmissionState::InProgress->value])
            ->get();

        // Find the previous highest score
        $previousHighScore = $otherSubmissions->max('score');

        // If this submission's score is higher than all previous scores, dispatch event
        if ($previousHighScore === null || $submission->score > $previousHighScore) {
            \Modules\Learning\Events\NewHighScoreAchieved::dispatch(
                $submission,
                $previousHighScore,
                $submission->score
            );
        }
    }
}
