<?php

namespace Modules\Learning\Services;

use Modules\Learning\Models\Assignment;
use Modules\Schemes\Models\Lesson;

class AssignmentService
{
    public function create(array $data, int $createdBy): Assignment
    {
        $assignment = Assignment::create([
            'lesson_id' => $data['lesson_id'],
            'created_by' => $createdBy,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'submission_type' => $data['submission_type'] ?? 'text',
            'max_score' => $data['max_score'] ?? 100,
            'available_from' => $data['available_from'] ?? null,
            'deadline_at' => $data['deadline_at'] ?? null,
            'status' => $data['status'] ?? 'draft',
            'allow_resubmit' => array_key_exists('allow_resubmit', $data) ? (bool) $data['allow_resubmit'] : null,
            'late_penalty_percent' => $data['late_penalty_percent'] ?? null,
        ]);

        return $assignment->fresh(['lesson', 'creator']);
    }

    public function update(Assignment $assignment, array $data): Assignment
    {
        $assignment->update([
            'title' => $data['title'] ?? $assignment->title,
            'description' => $data['description'] ?? $assignment->description,
            'submission_type' => $data['submission_type'] ?? $assignment->submission_type ?? 'text',
            'max_score' => $data['max_score'] ?? $assignment->max_score,
            'available_from' => $data['available_from'] ?? $assignment->available_from,
            'deadline_at' => $data['deadline_at'] ?? $assignment->deadline_at,
            'status' => $data['status'] ?? $assignment->status ?? 'draft',
            'allow_resubmit' => array_key_exists('allow_resubmit', $data) ? (bool) $data['allow_resubmit'] : $assignment->allow_resubmit,
            'late_penalty_percent' => array_key_exists('late_penalty_percent', $data) ? $data['late_penalty_percent'] : $assignment->late_penalty_percent,
        ]);

        return $assignment->fresh(['lesson', 'creator']);
    }

    public function publish(Assignment $assignment): Assignment
    {
        $wasDraft = $assignment->status === 'draft';
        $assignment->update(['status' => 'published']);

        $freshAssignment = $assignment->fresh(['lesson', 'creator']);

        // Dispatch event only if transitioning from draft to published
        if ($wasDraft) {
            \Modules\Learning\Events\AssignmentPublished::dispatch($freshAssignment);
        }

        return $freshAssignment;
    }

    public function unpublish(Assignment $assignment): Assignment
    {
        $assignment->update(['status' => 'draft']);

        return $assignment->fresh(['lesson', 'creator']);
    }

    public function delete(Assignment $assignment): bool
    {
        return $assignment->delete();
    }
}

