<?php

declare(strict_types=1);

namespace Modules\Grading\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GradeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $submissionStatus = $this->submission?->status instanceof \BackedEnum
            ? $this->submission->status->value
            : $this->submission?->status;
        $workflowState = $this->submission?->state instanceof \BackedEnum
            ? $this->submission->state->value
            : $this->submission?->state;

        return [
            'id' => $this->id,
            'submission_id' => $this->submission_id,
            'source_type' => $this->source_type,
            'source_id' => $this->source_id,
            'user_id' => $this->user_id,
            'graded_by' => $this->graded_by,
            'score' => $this->submission?->score ?? $this->score,
            'grade_score' => $this->score,
            'max_score' => $this->max_score,
            'original_score' => $this->original_score,
            'is_override' => $this->is_override,
            'override_reason' => $this->override_reason,
            'feedback' => $this->feedback,
            'is_draft' => $this->is_draft,
            'is_ai_assisted' => (bool) $this->is_ai_assisted,
            'ai_suggested_score' => $this->ai_suggested_score,
            'ai_reasoning' => $this->ai_reasoning,
            'ai_scoring_reference' => $this->ai_scoring_reference,
            'status' => $submissionStatus,
            'status_value' => $submissionStatus,
            'workflow_state' => $workflowState,
            'workflow_state_value' => $workflowState,
            'student_name' => $this->submission?->user?->name,
            'student_email' => $this->submission?->user?->email,
            'graded_at' => $this->graded_at,
            'released_at' => $this->released_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'submission' => $this->whenLoaded('submission'),
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                ];
            }),
            'grader' => $this->whenLoaded('grader', function () {
                return [
                    'id' => $this->grader->id,
                    'name' => $this->grader->name,
                    'email' => $this->grader->email,
                ];
            }),
        ];
    }
}
