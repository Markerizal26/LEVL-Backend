<?php

declare(strict_types=1);

namespace Modules\Schemes\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Modules\Enrollments\Models\Enrollment;
use Modules\Learning\Events\QuizCompleted;
use Modules\Schemes\Services\ProgressionService;

class UpdateProgressOnQuizCompleted implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'default';

    public int $tries = 3;

    public array $backoff = [5, 30, 60];

    public bool $afterCommit = true;

    public function __construct(private readonly ProgressionService $progression) {}

    public function handle(QuizCompleted $event): void
    {
        $submission = $event->submission->fresh(['quiz.unit.course']);

        if (! $submission || ! $submission->quiz || ! $submission->quiz->unit) {
            return;
        }

        
        if (! $submission->isPassed()) {
            return;
        }

        $unit = $submission->quiz->unit;
        $course = $unit->course;

        if (! $course) {
            return;
        }

        
        $enrollment = Enrollment::where('user_id', $submission->user_id)
            ->where('course_id', $course->id)
            ->whereIn('status', ['active', 'completed'])
            ->first();

        if (! $enrollment) {
            
            if ($submission->enrollment_id) {
                $enrollment = Enrollment::find($submission->enrollment_id);
            }
        }

        if (! $enrollment) {
            return;
        }

        
        $this->progression->refreshUnitAndCourseProgress($unit, $enrollment);
    }
}
