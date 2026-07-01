<?php

declare(strict_types=1);

namespace Modules\Grading\Services\Support;

use Modules\Grading\Events\GradeCreated;
use Modules\Grading\Models\Grade;
use Modules\Learning\Exceptions\SubmissionException;
use Modules\Learning\Models\Submission;

class GradingActionProcessor
{
    public function __construct(private readonly GradeCalculator $calculator) {}

    public function processAnswers(Submission $submission, array $answersData): void
    {
        foreach ($answersData as $questionId => $gradeData) {
            $answer = $submission->answers->where('question_id', $questionId)->first();

            if (! $answer) {
                continue;
            }

            $question = $answer->question;

            if (! $question) {
                continue;
            }

            $score = $gradeData['score'] ?? 0;
            $maxScore = $question->max_score ?? 100;

            if ($score < 0 || $score > $maxScore) {
                throw SubmissionException::invalidScore(
                    __('messages.submissions.score_out_of_range', [
                        'question_id' => $questionId,
                        'max_score' => $maxScore,
                    ])
                );
            }

            $answer->update([
                'score' => $score,
                'feedback' => $gradeData['feedback'] ?? null,
                'is_auto_graded' => false,
            ]);
        }
    }

    public function persistGrade(
        Submission $submission,
        float $score,
        ?int $graderId,
        ?string $feedback
    ): Grade {
        $grade = Grade::updateOrCreate(
            ['submission_id' => $submission->id],
            [
                'source_type' => 'assignment',
                'source_id' => $submission->assignment_id,
                'user_id' => $submission->user_id,
                'graded_by' => $graderId ?? auth('api')->id(),
                'score' => $score,
                'max_score' => $submission->assignment->max_score ?? 100,
                'feedback' => $feedback,
                'is_draft' => \DB::raw('false'),
                'graded_at' => now(),
            ]
        );

        if ($graderId) {
            GradeCreated::dispatch($grade, $graderId);
        }

        return $grade;
    }

    public function saveDraft(Submission $submission, array $answersData, ?int $graderId, ?float $scoreOverride = null, ?string $feedback = null, bool $isAiAssisted = false, ?float $aiSuggestedScore = null, ?string $aiReasoning = null): void
    {
        if ($submission->grade && ! $submission->grade->is_draft) {
            throw new \InvalidArgumentException(__('messages.grading.cannot_draft_finalized'));
        }

        foreach ($answersData as $gradeData) {
            $questionId = (int) ($gradeData['question_id'] ?? 0);
            if ($questionId <= 0) {
                continue;
            }

            $answer = $submission->answers->where('question_id', $questionId)->first();
            if (! $answer) {
                continue;
            }

            $score = $gradeData['score'] ?? null;

            if ($score !== null) {
                $maxScore = $answer->question->max_score ?? 100;
                $this->calculator->assertValidScore((float) $score, (float) $maxScore);
            }

            $answer->update([
                'score' => $score,
                'feedback' => $gradeData['feedback'] ?? null,
            ]);
        }

        if ($scoreOverride !== null) {
            $maxScore = (float) ($submission->assignment?->max_score ?? 100);
            $this->calculator->assertValidScore($scoreOverride, $maxScore);
        }

        Grade::updateOrCreate(
            ['submission_id' => $submission->id],
            [
                'source_type' => 'assignment',
                'source_id' => $submission->assignment_id,
                'user_id' => $submission->user_id,
                'graded_by' => $graderId ?? auth('api')->id(),
                'score' => $scoreOverride,
                'max_score' => $submission->assignment?->max_score ?? 100,
                'feedback' => $feedback,
                'is_draft' => \DB::raw('true'),
                'is_ai_assisted' => $isAiAssisted,
                'ai_suggested_score' => $aiSuggestedScore,
                'ai_reasoning' => $aiReasoning,
            ]
        );
    }

    public function overrideGrade(Submission $submission, float $score, string $reason, int $instructorId): void
    {
        if (empty($reason)) {
            throw new \InvalidArgumentException(__('messages.grading.reason_required'));
        }

        $grade = Grade::where('submission_id', $submission->id)->firstOrFail();
        $oldScore = (float) $grade->score;

        $grade->override($score, $reason, $instructorId);
        $submission->update(['score' => $score]);

        \Modules\Grading\Events\GradeOverridden::dispatch(
            $grade,
            $oldScore,
            $score,
            $reason,
            $instructorId
        );
    }

    public function returnToQueue(Submission $submission): void
    {
        if ($submission->state !== \Modules\Learning\Enums\SubmissionState::Graded) {
            throw new \InvalidArgumentException(__('messages.grading.submission_not_graded'));
        }

        $submission->update(['state' => \Modules\Learning\Enums\SubmissionState::PendingManualGrading->value]);

        $grade = Grade::where('submission_id', $submission->id)->first();
        if ($grade) {
            $grade->update(['is_draft' => \DB::raw('true')]);
        }
    }
}
