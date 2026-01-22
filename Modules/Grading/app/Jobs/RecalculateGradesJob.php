<?php

declare(strict_types=1);

namespace Modules\Grading\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Grading\Events\GradeRecalculated;
use Modules\Grading\Strategies\GradingStrategyFactory;
use Modules\Learning\Models\Answer;
use Modules\Learning\Models\Question;

/**
 * Job to recalculate grades after an answer key change.
 *
 * This job identifies all affected submissions and recalculates
 * auto-graded questions while preserving manual grades.
 *
 * Requirements: 15.1, 15.2, 15.3, 15.6
 */
class RecalculateGradesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $questionId,
        public array $oldAnswerKey,
        public array $newAnswerKey,
        public ?int $instructorId = null
    ) {
        $this->onQueue('grading');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $question = Question::find($this->questionId);

        if (! $question) {
            Log::warning('RecalculateGradesJob: Question not found', [
                'question_id' => $this->questionId,
            ]);

            return;
        }

        // Only recalculate for auto-gradable questions (Requirements 15.2)
        if (! $question->canAutoGrade()) {
            Log::info('RecalculateGradesJob: Question is not auto-gradable, skipping', [
                'question_id' => $this->questionId,
            ]);

            return;
        }

        // Find all auto-graded answers for this question (Requirements 15.1, 15.6)
        // Only recalculate answers that were auto-graded, preserving manual grades
        $answers = Answer::where('question_id', $this->questionId)
            ->where('is_auto_graded', true)
            ->with('submission')
            ->get();

        if ($answers->isEmpty()) {
            Log::info('RecalculateGradesJob: No auto-graded answers found', [
                'question_id' => $this->questionId,
            ]);

            return;
        }

        $strategy = GradingStrategyFactory::make($question->type);
        $affectedSubmissions = collect();

        foreach ($answers as $answer) {
            if (! $answer->submission) {
                continue;
            }

            $oldScore = $answer->score;
            $newScore = $strategy->grade($question, $answer);

            // Only update if score changed
            if ($oldScore != $newScore) {
                $answer->update(['score' => $newScore]);
                $affectedSubmissions->push($answer->submission);
            }
        }

        // Recalculate submission scores for affected submissions (Requirements 15.3)
        $uniqueSubmissions = $affectedSubmissions->unique('id');

        foreach ($uniqueSubmissions as $submission) {
            $this->recalculateSubmissionScore($submission);
        }

        Log::info('RecalculateGradesJob: Completed recalculation', [
            'question_id' => $this->questionId,
            'affected_answers' => $answers->count(),
            'affected_submissions' => $uniqueSubmissions->count(),
        ]);
    }

    /**
     * Recalculate the total score for a submission.
     *
     * Dispatches GradeRecalculated event to trigger notifications.
     * Requirements: 15.5 - THE System SHALL notify affected students of grade changes
     */
    private function recalculateSubmissionScore($submission): void
    {
        $submission->load('answers.question');

        // Store old score for notification
        $oldScore = (float) ($submission->score ?? 0);

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

        $newScore = $totalWeight > 0 ? round($totalWeightedScore / $totalWeight, 2) : 0;

        $submission->update(['score' => $newScore]);

        // Update the grade record if it exists
        if ($submission->grade) {
            // Only update if not overridden (preserve manual overrides)
            if (! $submission->grade->is_override) {
                $submission->grade->update(['score' => $newScore]);
            }
        }

        // Dispatch event to trigger notification if score changed (Requirements 15.5)
        if (abs($oldScore - $newScore) >= 0.01) {
            GradeRecalculated::dispatch($submission, $oldScore, $newScore);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('RecalculateGradesJob: Failed to recalculate grades', [
            'question_id' => $this->questionId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
