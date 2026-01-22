<?php

declare(strict_types=1);

namespace Modules\Grading\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource for grading queue items.
 *
 * Requirements: 10.4 - Display submission metadata
 */
class GradingQueueItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Handle both array and object data
        if (is_array($this->resource)) {
            return $this->resource;
        }

        return [
            'submission_id' => $this->submission_id ?? $this->id,
            'student_name' => $this->student_name ?? $this->user?->name,
            'student_email' => $this->student_email ?? $this->user?->email,
            'assignment_id' => $this->assignment_id,
            'assignment_title' => $this->assignment_title ?? $this->assignment?->title,
            'submitted_at' => $this->submitted_at,
            'is_late' => $this->is_late,
            'questions_requiring_grading' => $this->questions_requiring_grading ?? [],
            'total_questions' => $this->total_questions ?? 0,
            'graded_questions' => $this->graded_questions ?? 0,
        ];
    }
}
