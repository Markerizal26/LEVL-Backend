<?php

declare(strict_types=1);

namespace Modules\Learning\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Modules\Grading\Contracts\Services\GradingServiceInterface;
use Modules\Learning\Events\NewHighScoreAchieved;

class RecalculateCourseGradeOnNewHighScore implements ShouldQueue
{
    public function __construct(
        private readonly GradingServiceInterface $gradingService
    ) {}

    public function handle(NewHighScoreAchieved $event): void
    {
        $submission = $event->submission;

        Log::info('RecalculateCourseGradeOnNewHighScore: Processing new high score', [
            'submission_id' => $submission->id,
            'assignment_id' => $submission->assignment_id,
            'user_id' => $submission->user_id,
            'previous_high_score' => $event->previousHighScore,
            'new_high_score' => $event->newHighScore,
        ]);

        
        $this->gradingService->recalculateCourseGrade(
            $submission->user_id,
            $submission->assignment->getCourseId()
        );
    }
}
