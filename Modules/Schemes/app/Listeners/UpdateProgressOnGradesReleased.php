<?php

declare(strict_types=1);

namespace Modules\Schemes\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Modules\Enrollments\Models\Enrollment;
use Modules\Grading\Events\GradesReleased;
use Modules\Schemes\Services\ProgressionService;

class UpdateProgressOnGradesReleased implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'default';

    public int $tries = 3;

    public array $backoff = [5, 30, 60];

    public bool $afterCommit = true;

    public function __construct(private readonly ProgressionService $progression) {}

    public function handle(GradesReleased $event): void
    {
        foreach ($event->submissions as $submission) {
            $this->processSubmission($submission);
        }
    }

    private function processSubmission($submission): void
    {
        $submission->loadMissing(['assignment.unit.course', 'grade']);

        if (! $submission->assignment || ! $submission->assignment->unit) {
            return;
        }

        $grade = $submission->grade;
        if (! $grade || ! $grade->isReleased()) {
            return;
        }

        $assignment = $submission->assignment;
        $passingGrade = $assignment->passing_grade ?? ($assignment->max_score * 0.6);

        
        if ($grade->effective_score < $passingGrade) {
            return;
        }

        $unit = $assignment->unit;
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
