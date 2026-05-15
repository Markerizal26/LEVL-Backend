<?php

declare(strict_types=1);

namespace Modules\Enrollments\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Enrollments\Models\Enrollment;
use Modules\Enrollments\Models\EnrollmentActivity;
use Modules\Learning\Events\QuizCompleted;

class RecordQuizCompletedActivity implements ShouldQueue
{
    public bool $afterCommit = true;

    public function handle(QuizCompleted $event): void
    {
        $submission = $event->submission->fresh(['quiz']);

        if (! $submission || ! $submission->quiz || ! $submission->enrollment_id) {
            return;
        }

        if (! $submission->isPassed()) {
            return;
        }

        $enrollment = Enrollment::query()->find($submission->enrollment_id);

        if (! $enrollment || $enrollment->user_id !== $submission->user_id) {
            return;
        }

        EnrollmentActivity::query()->firstOrCreate(
            [
                'enrollment_id' => $enrollment->id,
                'event_type' => 'quiz_passed',
                'quiz_id' => $submission->quiz->id,
                'body' => 'submission:'.$submission->id,
            ],
            [
                'user_id' => $submission->user_id,
                'course_id' => $submission->quiz->unit?->course_id ?? $enrollment->course_id,
                'title' => 'Passed Quiz: "'.$submission->quiz->title.'"',
                'metadata' => [
                    'quiz_id' => $submission->quiz->id,
                    'submission_id' => $submission->id,
                    'score' => $submission->final_score ?? $submission->score,
                ],
                'lesson_id' => null,
                'assignment_id' => null,
                'occurred_at' => $submission->submitted_at ?? now(),
            ],
        );
    }
}