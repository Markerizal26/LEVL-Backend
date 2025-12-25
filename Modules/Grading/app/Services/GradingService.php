<?php

namespace Modules\Grading\Services;

use Modules\Grading\Contracts\Repositories\GradingRepositoryInterface;
use Modules\Grading\Contracts\Services\GradingServiceInterface;
use Modules\Grading\Models\Grade;
use Modules\Learning\Repositories\SubmissionRepository;

class GradingService implements GradingServiceInterface
{
    public function __construct(
        private readonly GradingRepositoryInterface $repository,
        private readonly SubmissionRepository $submissionRepository
    ) {}

    public function gradeSubmission(int $submissionId, array $data, int $gradedBy): Grade
    {
        $gradeData = array_merge($data, [
            'submission_id' => $submissionId,
            'graded_by' => $gradedBy,
            'graded_at' => now(),
        ]);

        $grade = $this->repository->create($gradeData);

        // Update submission status to graded
        $this->submissionRepository->markAsGraded($submissionId);

        return $grade;
    }

    public function updateGrade(Grade $grade, array $data, int $updatedBy): Grade
    {
        $updateData = array_merge($data, [
            'graded_by' => $updatedBy,
            'graded_at' => now(),
        ]);

        return $this->repository->update($grade, $updateData);
    }

    public function getGradeBySubmission(int $submissionId): ?Grade
    {
        return $this->repository->findBySubmission($submissionId);
    }
}
