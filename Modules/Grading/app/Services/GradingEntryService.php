<?php

declare(strict_types=1);

namespace Modules\Grading\Services;

use InvalidArgumentException;
use Modules\Grading\DTOs\SubmissionGradeDTO;
use Modules\Grading\Events\GradesReleased;
use Modules\Grading\Models\Grade;
use Modules\Grading\Services\Support\GradeCalculator;
use Modules\Learning\Enums\QuizGradingStatus;
use Modules\Learning\Enums\QuizSubmissionStatus;
use Modules\Learning\Enums\SubmissionState;
use Modules\Learning\Models\QuizSubmission;
use Modules\Learning\Models\Submission;

class GradingEntryService
{
    public function __construct(
        private readonly GradeCalculator $calculator,
        private readonly \Modules\Learning\Contracts\Repositories\AssignmentRepositoryInterface $assignmentRepository,
        private readonly \Modules\Grading\Services\Support\GradingActionProcessor $actionProcessor,
        private readonly \Modules\Grading\Services\Support\AutoGradingProcessor $autoGradingProcessor
    ) {}

    public function autoGrade(int $submissionId): void
    {
        $this->autoGradingProcessor->execute($submissionId);
    }

    public function manualGrade(SubmissionGradeDTO $data): Grade
    {
        $submission = Submission::with(['answers.question', 'assignment'])->findOrFail($data->submissionId);

        $globalScoreOverride = $data->scoreOverride;

        $this->actionProcessor->processAnswers($submission, $data->answers);

        if ($globalScoreOverride === null) {
            $submission->refresh();
            if ($submission->answers->contains(fn ($a) => $a->score === null)) {
                throw \Modules\Learning\Exceptions\SubmissionException::notAllowed(
                    __('messages.grading.incomplete_grading')
                );
            }

            $score = $this->calculator->calculateSubmissionScore($submission);
        } else {
            $score = $globalScoreOverride;
        }

        $submission->update(['score' => $score]);

        $grade = $this->actionProcessor->persistGrade(
            $submission,
            $score,
            $data->graderId,
            $data->feedback
        );

        $submission->transitionTo(SubmissionState::Graded, $data->graderId ?? auth('api')->id());

        return $grade;
    }

    public function releaseGrade(int $submissionId): void
    {
        $submission = Submission::with('grade')->findOrFail($submissionId);

        if (! $submission->grade) {
            throw new InvalidArgumentException(__('messages.grading.no_grade_exists'));
        }

        if ($submission->grade->is_draft) {
            throw new InvalidArgumentException(__('messages.grading.cannot_release_draft'));
        }

        if ($submission->state !== SubmissionState::Graded) {
            if ($submission->state !== SubmissionState::Graded && $submission->state !== SubmissionState::Released) {
                throw new InvalidArgumentException(__('messages.grading.submission_not_graded'));
            }
        }

        $submission->grade->release();
        $submission->transitionTo(SubmissionState::Released, (int) auth('api')->id());

        GradesReleased::dispatch(collect([$submission]), auth('api')->id());
    }

    public function saveDraftGrade(SubmissionGradeDTO $data): void
    {
        $submission = Submission::with(['answers.question', 'assignment', 'grade'])->findOrFail($data->submissionId);
        $this->actionProcessor->saveDraft($submission, $data->answers, $data->graderId, $data->scoreOverride, $data->feedback, $data->isAiAssisted, $data->aiSuggestedScore, $data->aiReasoning);
    }

    public function returnToQueue(int $submissionId): void
    {
        $submission = Submission::findOrFail($submissionId);
        $this->actionProcessor->returnToQueue($submission);
    }

    public function recalculateCourseGrade(int $studentId, int $courseId): ?float
    {
        if (! $courseId) {
            return null;
        }

        $assignments = $this->assignmentRepository->getFlattenedForCourse($courseId);
        $courseGrade = $this->calculator->calculateCourseScore($assignments, $studentId);

        Grade::updateOrCreate(
            [
                'source_type' => 'course',
                'source_id' => $courseId,
                'user_id' => $studentId,
            ],
            [
                'score' => $courseGrade,
                'max_score' => 100,
                'is_draft' => \DB::raw('false'),
                'graded_at' => now(),
            ]
        );

        return $courseGrade;
    }

    public function manualGradeQuiz(QuizSubmission $submission, array $grades, ?int $graderId): QuizSubmission
    {
        $submission->load(['quiz.questions', 'answers.question']);

        foreach ($grades as $gradeData) {
            $questionId = (int) ($gradeData['question_id'] ?? 0);
            $answer = $submission->answers->first(fn ($item) => (int) $item->quiz_question_id === $questionId);

            if (! $answer || ! $answer->question) {
                continue;
            }

            $score = (float) ($gradeData['score'] ?? 0);
            $maxScore = (float) ($answer->question->max_score ?? $answer->question->weight ?? 100);

            $this->calculator->assertValidScore($score, $maxScore);

            $answer->update([
                'score' => $score,
                'feedback' => $gradeData['feedback'] ?? null,
                'is_auto_graded' => 0,
                'is_ai_assisted' => (bool) ($gradeData['is_ai_assisted'] ?? false),
                'ai_suggested_score' => $gradeData['ai_suggested_score'] ?? null,
                'ai_reasoning' => $gradeData['ai_reasoning'] ?? null,
            ]);
        }

        return $this->recalculateQuizSubmissionGrade($submission->refresh(), false);
    }

    public function saveDraftGradeQuiz(QuizSubmission $submission, array $grades, ?int $graderId): QuizSubmission
    {
        $submission->load(['quiz.questions', 'answers.question']);

        foreach ($grades as $gradeData) {
            $questionId = (int) ($gradeData['question_id'] ?? 0);
            $answer = $submission->answers->first(fn ($item) => (int) $item->quiz_question_id === $questionId);

            if (! $answer || ! $answer->question) {
                continue;
            }

            if (array_key_exists('score', $gradeData) && $gradeData['score'] !== null) {
                $score = (float) $gradeData['score'];
                $maxScore = (float) ($answer->question->max_score ?? $answer->question->weight ?? 100);

                $this->calculator->assertValidScore($score, $maxScore);
            }

            $answer->update([
                'score' => $gradeData['score'] ?? null,
                'feedback' => $gradeData['feedback'] ?? null,
                'is_auto_graded' => 0,
                'is_ai_assisted' => (bool) ($gradeData['is_ai_assisted'] ?? false),
                'ai_suggested_score' => $gradeData['ai_suggested_score'] ?? null,
                'ai_reasoning' => $gradeData['ai_reasoning'] ?? null,
            ]);
        }

        return $this->recalculateQuizSubmissionGrade($submission->refresh(), true);
    }

    public function finalizeQuizSubmission(QuizSubmission $submission): QuizSubmission
    {
        return $this->recalculateQuizSubmissionGrade($submission, false);
    }

    public function recalculateQuizScore(QuizSubmission $submission): QuizSubmission
    {
        $forceDraft = in_array(
            $submission->grading_status?->value,
            [QuizGradingStatus::PartiallyGraded->value],
            true
        );

        return $this->recalculateQuizSubmissionGrade($submission, $forceDraft);
    }

    private function recalculateQuizSubmissionGrade(QuizSubmission $submission, bool $forceDraft): QuizSubmission
    {
        $submission->load(['quiz.questions', 'answers.question']);

        $questions = $submission->quiz->questions;
        $answers = $submission->answers;

        $totalWeight = (float) $questions->sum(fn ($question) => (float) $question->weight);
        $earnedWeight = 0.0;
        $hasUngradedManual = false;

        foreach ($questions as $question) {
            $answer = $answers->first(fn ($item) => (int) $item->quiz_question_id === (int) $question->id);
            $score = $answer?->score;

            if (! $question->canAutoGrade() && $score === null) {
                $hasUngradedManual = true;
            }

            if ($score !== null) {
                $questionWeight = (float) $question->weight;

                if ($answer && ! $answer->is_auto_graded) {
                    $questionMaxScore = (float) ($question->max_score ?? $questionWeight);
                    $earnedWeight += $questionMaxScore > 0
                        ? ((float) $score / $questionMaxScore) * $questionWeight
                        : 0.0;
                } else {
                    $earnedWeight += (float) $score;
                }
            }
        }

        $quizMaxScore = (float) ($submission->quiz->max_score ?? 100);
        $calculatedScore = $totalWeight > 0
            ? round(($earnedWeight / $totalWeight) * $quizMaxScore, 2)
            : 0.0;

        $isFinal = ! $forceDraft && ! $hasUngradedManual;
        $gradingStatus = $isFinal ? QuizGradingStatus::Graded : QuizGradingStatus::PartiallyGraded;
        $status = $isFinal ? QuizSubmissionStatus::Graded : QuizSubmissionStatus::Submitted;

        $submission->update([
            'grading_status' => $gradingStatus->value,
            'status' => $status->value,
            'score' => $calculatedScore,
            'final_score' => $isFinal ? $calculatedScore : null,
        ]);

        return $submission->fresh(['user', 'quiz.unit.course', 'answers.question']);
    }
}
