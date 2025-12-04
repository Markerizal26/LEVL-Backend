<?php

namespace Modules\Learning\Services;

use Modules\Learning\Enums\AssignmentStatus;
use Modules\Learning\Models\Assignment;
use Modules\Learning\Repositories\AssignmentRepository;

class AssignmentService
{
    private AssignmentRepository $repository;

    public function __construct(?AssignmentRepository $repository = null)
    {
        $this->repository = $repository ?? app(AssignmentRepository::class);
    }

    public function listByLesson(\Modules\Schemes\Models\Lesson $lesson, array $filters = [])
    {
        return $this->repository->listForLesson($lesson, $filters);
    }

    public function create(array $data, int $createdBy): Assignment
    {
        $assignment = $this->repository->create([
            'lesson_id' => $data['lesson_id'],
            'created_by' => $createdBy,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'submission_type' => $data['submission_type'] ?? 'text',
            'max_score' => $data['max_score'] ?? 100,
            'available_from' => $data['available_from'] ?? null,
            'deadline_at' => $data['deadline_at'] ?? null,
            'status' => $data['status'] ?? AssignmentStatus::Draft->value,
            'allow_resubmit' => array_key_exists('allow_resubmit', $data) ? (bool) $data['allow_resubmit'] : null,
            'late_penalty_percent' => $data['late_penalty_percent'] ?? null,
        ]);

        return $assignment->fresh(['lesson', 'creator']);
    }

    public function update(Assignment $assignment, array $data): Assignment
    {
        $updated = $this->repository->update($assignment, [
            'title' => $data['title'] ?? $assignment->title,
            'description' => $data['description'] ?? $assignment->description,
            'submission_type' => $data['submission_type'] ?? $assignment->submission_type ?? 'text',
            'max_score' => $data['max_score'] ?? $assignment->max_score,
            'available_from' => $data['available_from'] ?? $assignment->available_from,
            'deadline_at' => $data['deadline_at'] ?? $assignment->deadline_at,
            'status' => $data['status'] ?? ($assignment->status?->value ?? AssignmentStatus::Draft->value),
            'allow_resubmit' => array_key_exists('allow_resubmit', $data) ? (bool) $data['allow_resubmit'] : $assignment->allow_resubmit,
            'late_penalty_percent' => array_key_exists('late_penalty_percent', $data) ? $data['late_penalty_percent'] : $assignment->late_penalty_percent,
        ]);

        return $updated->fresh(['lesson', 'creator']);
    }

    public function publish(Assignment $assignment): Assignment
    {
        $wasDraft = $assignment->status === AssignmentStatus::Draft;
        $published = $this->repository->update($assignment, ['status' => AssignmentStatus::Published->value]);
        $freshAssignment = $published->fresh(['lesson', 'creator']);

        if ($wasDraft) {
            \Modules\Learning\Events\AssignmentPublished::dispatch($freshAssignment);
        }

        return $freshAssignment;
    }

    public function unpublish(Assignment $assignment): Assignment
    {
        $updated = $this->repository->update($assignment, ['status' => AssignmentStatus::Draft->value]);

        return $updated->fresh(['lesson', 'creator']);
    }

    public function delete(Assignment $assignment): bool
    {
        return $this->repository->delete($assignment);
    }
}
