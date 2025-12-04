<?php

namespace Modules\Learning\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Auth\Models\User;
use Modules\Common\Models\SystemSetting;
use Modules\Enrollments\Enums\EnrollmentStatus;
use Modules\Enrollments\Enums\ProgressStatus;
use Modules\Enrollments\Models\Enrollment;
use Modules\Enrollments\Models\LessonProgress;
use Modules\Learning\Events\SubmissionCreated;
use Modules\Learning\Models\Assignment;
use Modules\Learning\Models\Submission;
use Modules\Learning\Repositories\SubmissionRepository;
use Modules\Schemes\Models\Lesson;

class SubmissionService
{
    private SubmissionRepository $repository;

    public function __construct(?SubmissionRepository $repository = null)
    {
        $this->repository = $repository ?? app(SubmissionRepository::class);
    }

    public function listForAssignment(Assignment $assignment, User $user, array $filters = []): Collection
    {
        return $this->repository->listForAssignment($assignment, $user, $filters);
    }

    public function create(Assignment $assignment, int $userId, array $data): Submission
    {
        return DB::transaction(function () use ($assignment, $userId, $data) {
            $lesson = $assignment->lesson;
            if (! $lesson) {
                throw ValidationException::withMessages([
                    'assignment' => 'Assignment tidak memiliki lesson yang valid.',
                ]);
            }

            $enrollment = $this->getEnrollmentForLesson($lesson, $userId);
            if (! $enrollment) {
                throw ValidationException::withMessages([
                    'enrollment' => 'Anda belum terdaftar pada course ini.',
                ]);
            }

            if (! $assignment->isAvailable()) {
                throw ValidationException::withMessages([
                    'assignment' => 'Assignment belum tersedia atau belum dipublish.',
                ]);
            }

            $existingSubmission = $this->repository->latestCommittedSubmission($assignment, $userId);

            $allowResubmit = $assignment->allow_resubmit !== null
                ? (bool) $assignment->allow_resubmit
                : SystemSetting::get('learning.allow_resubmit', true);
            $isResubmission = $existingSubmission !== null;

            if ($isResubmission && ! $allowResubmit) {
                throw ValidationException::withMessages([
                    'submission' => 'Resubmission tidak diizinkan untuk assignment ini.',
                ]);
            }

            $attemptNumber = $isResubmission
                ? ($existingSubmission->attempt_number + 1)
                : 1;

            $isLate = $assignment->isPastDeadline();

            if ($isResubmission && $existingSubmission) {
                $this->repository->delete($existingSubmission);
            }

            $submission = $this->repository->create([
                'assignment_id' => $assignment->id,
                'user_id' => $userId,
                'enrollment_id' => $enrollment->id,
                'answer_text' => $data['answer_text'] ?? null,
                'status' => $isLate
                    ? \Modules\Learning\Enums\SubmissionStatus::Late->value
                    : \Modules\Learning\Enums\SubmissionStatus::Submitted->value,
                'attempt_number' => $attemptNumber,
                'is_late' => $isLate,
                'is_resubmission' => $isResubmission,
                'previous_submission_id' => null,
                'submitted_at' => Carbon::now(),
            ]);

            $this->incrementLessonProgressAttempt($enrollment->id, $lesson->id);

            SubmissionCreated::dispatch($submission);

            return $submission->fresh(['assignment', 'user', 'enrollment', 'files', 'grade']);
        });
    }

    public function update(Submission $submission, array $data): Submission
    {
        if ($submission->status === \Modules\Learning\Enums\SubmissionStatus::Graded) {
            throw ValidationException::withMessages([
                'submission' => 'Submission yang sudah dinilai tidak dapat diubah.',
            ]);
        }

        $updated = $this->repository->update($submission, [
            'answer_text' => $data['answer_text'] ?? $submission->answer_text,
        ]);

        return $updated->fresh(['assignment', 'user', 'enrollment', 'files']);
    }

    public function grade(Submission $submission, int $score, ?string $feedback = null, ?int $gradedBy = null): Submission
    {
        $assignment = $submission->assignment;
        $maxScore = $assignment->max_score;

        if ($score < 0 || $score > $maxScore) {
            throw ValidationException::withMessages([
                'score' => "Score harus antara 0 dan {$maxScore}.",
            ]);
        }

        $finalScore = $score;
        if ($submission->is_late) {
            $assignmentPenalty = $assignment->late_penalty_percent;
            $latePenaltyPercent = $assignmentPenalty !== null
                ? (int) $assignmentPenalty
                : (int) SystemSetting::get('learning.late_penalty_percent', 0);
            if ($latePenaltyPercent > 0) {
                $penalty = ($score * $latePenaltyPercent) / 100;
                $finalScore = max(0, $score - $penalty);
            }
        }

        $grade = \Modules\Grading\Models\Grade::updateOrCreate(
            [
                'source_type' => 'assignment',
                'source_id' => $assignment->id,
                'user_id' => $submission->user_id,
            ],
            [
                'graded_by' => $gradedBy ?? auth('api')->id(),
                'score' => $finalScore,
                'max_score' => $maxScore,
                'feedback' => $feedback,
                'status' => \Modules\Grading\Enums\GradeStatus::Graded,
                'graded_at' => Carbon::now(),
            ]
        );

        $updated = $this->repository->update($submission, [
            'status' => \Modules\Learning\Enums\SubmissionStatus::Graded->value,
        ])->fresh(['assignment', 'user', 'enrollment', 'files']);
        $updated->setRelation('grade', $grade);

        return $updated;
    }

    private function getEnrollmentForLesson(Lesson $lesson, int $userId): ?Enrollment
    {
        $lesson->loadMissing('unit.course');

        if (! $lesson->unit || ! $lesson->unit->course) {
            return null;
        }

        return Enrollment::query()
            ->where('course_id', $lesson->unit->course->id)
            ->where('user_id', $userId)
            ->whereIn('status', [EnrollmentStatus::Active, EnrollmentStatus::Completed])
            ->first();
    }

    private function incrementLessonProgressAttempt(int $enrollmentId, int $lessonId): void
    {
        $progress = LessonProgress::query()
            ->where('enrollment_id', $enrollmentId)
            ->where('lesson_id', $lessonId)
            ->first();

        if ($progress) {
            $progress->increment('attempt_count');
        } else {
            LessonProgress::create([
                'enrollment_id' => $enrollmentId,
                'lesson_id' => $lessonId,
                'status' => ProgressStatus::NotStarted,
                'progress_percent' => 0,
                'attempt_count' => 1,
            ]);
        }
    }
}
