<?php

declare(strict_types=1);

namespace Modules\Grading\Services\Support;

use Modules\Grading\Strategies\GradingStrategyFactory;
use Modules\Learning\Enums\SubmissionState;
use Modules\Learning\Models\Submission;

class AutoGradingProcessor
{
    public function __construct(
        private readonly GradeCalculator $calculator
    ) {}

    public function execute(int $submissionId): void
    {
        $submission = Submission::with(['answers.question', 'assignment'])->findOrFail($submissionId);

        if (! $submission->assignment->isQuiz()) {
            $submission->transitionTo(SubmissionState::PendingManualGrading, $submission->user_id);

            return;
        }

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
                    'is_auto_graded' => 1,
                ]);
            } else {
                $hasManualQuestions = true;
            }
        }

        $score = $this->calculator->calculateSubmissionScore($submission);
        $submission->update(['score' => $score]);

        $newState = $hasManualQuestions
            ? SubmissionState::PendingManualGrading
            : SubmissionState::AutoGraded;

        $submission->transitionTo($newState, $submission->user_id);
    }
}
