<?php

declare(strict_types=1);

namespace Modules\Grading\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Grading\Models\Appeal;

/**
 * Resource for Appeal model.
 *
 * Requirements: 17.1, 17.4, 17.5
 *
 * @mixin Appeal
 */
class AppealResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'submission_id' => $this->submission_id,
            'student_id' => $this->student_id,
            'reviewer_id' => $this->reviewer_id,
            'reason' => $this->reason,
            'supporting_documents' => $this->supporting_documents,
            'status' => $this->status->value,
            'decision_reason' => $this->decision_reason,
            'submitted_at' => $this->submitted_at,
            'decided_at' => $this->decided_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Relationships
            'submission' => $this->whenLoaded('submission', function () {
                /** @var \Modules\Learning\Models\Submission $submission */
                $submission = $this->submission;

                $data = [
                    'id' => $submission->id,
                    'assignment_id' => $submission->assignment_id,
                    'state' => $submission->state?->value,
                    'score' => $submission->score,
                    'is_late' => $submission->is_late,
                    'submitted_at' => $submission->submitted_at,
                ];

                if ($submission->relationLoaded('assignment') && $submission->assignment) {
                    $data['assignment'] = [
                        'id' => $submission->assignment->id,
                        'title' => $submission->assignment->title,
                    ];
                }

                return $data;
            }),
            'student' => $this->whenLoaded('student', function () {
                /** @var \Modules\Auth\Models\User $student */
                $student = $this->student;

                return [
                    'id' => $student->id,
                    'name' => $student->name,
                    'email' => $student->email,
                ];
            }),
            'reviewer' => $this->whenLoaded('reviewer', function () {
                if (! $this->reviewer) {
                    return null;
                }

                /** @var \Modules\Auth\Models\User $reviewer */
                $reviewer = $this->reviewer;

                return [
                    'id' => $reviewer->id,
                    'name' => $reviewer->name,
                    'email' => $reviewer->email,
                ];
            }),
        ];
    }
}
