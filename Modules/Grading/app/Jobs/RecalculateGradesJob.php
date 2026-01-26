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

class RecalculateGradesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    public function __construct(
        public int $questionId,
        public array $oldAnswerKey,
        public array $newAnswerKey,
        public ?int $instructorId = null
    ) {
        $this->onQueue('grading');
    }

    public function handle(): void
    {
        $question = Question::find($this->questionId);

        if (! $question) {
            Log::warning('RecalculateGradesJob: Question not found', [
                'question_id' => $this->questionId,
            ]);

            return;
        }

        if (! $question->canAutoGrade()) {
            Log::info('RecalculateGradesJob: Question is not auto-gradable, skipping', [
                'question_id' => $this->questionId,
            ]);

            return;
        }

        $strategy = GradingStrategyFactory::make($question->type);
        $affectedSubmissionIds = collect();
        $processedCount = 0;

        Answer::where('question_id', $this->questionId)
            ->where('is_auto_graded', true)
            ->with('submission')
            ->chunkById(100, function ($answers) use ($question, $strategy, &$affectedSubmissionIds, &$processedCount) {
                foreach ($answers as $answer) {
                    if (! $answer->submission) {
                        continue;
                    }

                    $oldScore = $answer->score;
                    $newScore = $strategy->grade($question, $answer);

                    if ($oldScore != $newScore) {
                        $answer->update(['score' => $newScore]);
                        $affectedSubmissionIds->push($answer->submission_id);
                    }
                    $processedCount++;
                }
            });

        if ($processedCount === 0) {
            Log::info('RecalculateGradesJob: No auto-graded answers found', [
                'question_id' => $this->questionId,
            ]);
            return;
        }

        $uniqueSubmissionIds = $affectedSubmissionIds->unique();

        // Process affected submissions in chunks as well to avoid memory issues
        // Since we only need to trigger recalculation, we can find them by ID
        if ($uniqueSubmissionIds->isNotEmpty()) {
            \Modules\Learning\Models\Submission::whereIn('id', $uniqueSubmissionIds)
                ->chunkById(100, function ($submissions) {
                    foreach ($submissions as $submission) {
                        $this->recalculateSubmissionScore($submission);
                    }
                });
        }

        Log::info('RecalculateGradesJob: Completed recalculation', [
            'question_id' => $this->questionId,
            'affected_answers' => $processedCount,
            'affected_submissions' => $uniqueSubmissionIds->count(),
        ]);
    }

    private function recalculateSubmissionScore($submission): void
    {
        $submission->load('answers.question');

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

        if ($submission->grade) {
            if (! $submission->grade->is_override) {
                $submission->grade->update(['score' => $newScore]);
            }
        }

        if (abs($oldScore - $newScore) >= 0.01) {
            GradeRecalculated::dispatch($submission, $oldScore, $newScore);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('RecalculateGradesJob: Failed to recalculate grades', [
            'question_id' => $this->questionId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
