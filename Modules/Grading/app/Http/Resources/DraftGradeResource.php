<?php

declare(strict_types=1);

namespace Modules\Grading\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource for draft grade data.
 *
 * Requirements: 11.3 - Restore previously saved draft grades
 */
class DraftGradeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Handle array data from getDraftGrade
        if (is_array($this->resource)) {
            return [
                'submission_id' => $this->resource['submission_id'] ?? null,
                'graded_by' => $this->resource['graded_by'] ?? null,
                'grades' => $this->resource['grades'] ?? [],
                'overall_feedback' => $this->resource['overall_feedback'] ?? null,
            ];
        }

        return [
            'submission_id' => $this->submission_id,
            'graded_by' => $this->graded_by,
            'grades' => $this->grades ?? [],
            'overall_feedback' => $this->overall_feedback,
        ];
    }
}
