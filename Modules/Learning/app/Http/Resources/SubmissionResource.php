<?php

declare(strict_types=1);

namespace Modules\Learning\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubmissionResource extends JsonResource
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
            'assignment_id' => $this->assignment_id,
            'user_id' => $this->user_id,
            'enrollment_id' => $this->enrollment_id,
            'answer_text' => $this->answer_text,
            'status' => $this->status,
            'state' => $this->state?->value,
            'score' => $this->score,
            'attempt_number' => $this->attempt_number,
            'is_late' => $this->is_late,
            'is_resubmission' => $this->is_resubmission,
            'question_set' => $this->question_set,
            'submitted_at' => $this->submitted_at,
            'graded_at' => $this->graded_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Dynamic attribute for highest score marking (Requirement 22.3)
            'is_highest' => $this->when(isset($this->is_highest), $this->is_highest),

            // Relationships
            'assignment' => $this->whenLoaded('assignment'),
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                ];
            }),
            'enrollment' => $this->whenLoaded('enrollment'),
            'files' => $this->whenLoaded('files'),
            'answers' => $this->whenLoaded('answers'),
            'previousSubmission' => $this->whenLoaded('previousSubmission'),
            'grade' => $this->whenLoaded('grade'),
            'appeal' => $this->whenLoaded('appeal'),
        ];
    }

    /**
     * Create a new resource instance with default relationships loaded.
     */
    public static function make(...$parameters)
    {
        $resource = $parameters[0] ?? null;

        if ($resource && method_exists($resource, 'loadMissing')) {
            $resource->loadMissing([
                'assignment',
                'user:id,name,email',
                'enrollment',
                'files',
                'answers',
                'previousSubmission',
                'grade',
            ]);
        }

        return parent::make(...$parameters);
    }
}
