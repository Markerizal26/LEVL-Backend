<?php

declare(strict_types=1);

namespace Modules\Grading\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DraftGradeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        if (is_array($this->resource)) {
            return [
                'submission_id' => $this->resource['submission_id'] ?? null,
                'graded_by' => $this->resource['graded_by'] ?? null,
                'grades' => $this->resource['grades'] ?? [],
                'overall_feedback' => $this->resource['overall_feedback'] ?? null,
                'is_ai_assisted' => (bool) ($this->resource['is_ai_assisted'] ?? false),
                'ai_suggested_score' => $this->resource['ai_suggested_score'] ?? null,
                'ai_reasoning' => $this->resource['ai_reasoning'] ?? null,
                'ai_scoring_reference' => $this->resource['ai_scoring_reference'] ?? null,
            ];
        }

        return [
            'submission_id' => $this->submission_id,
            'graded_by' => $this->graded_by,
            'grades' => $this->grades ?? [],
            'overall_feedback' => $this->overall_feedback,
            'is_ai_assisted' => (bool) $this->is_ai_assisted,
            'ai_suggested_score' => $this->ai_suggested_score,
            'ai_reasoning' => $this->ai_reasoning,
            'ai_scoring_reference' => $this->ai_scoring_reference,
        ];
    }
}
