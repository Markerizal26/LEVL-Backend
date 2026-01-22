<?php

declare(strict_types=1);

namespace Modules\Learning\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Learning\Enums\SubmissionState;

/**
 * Request validation for searching submissions.
 *
 * Requirements: 27.1, 27.2, 27.3, 27.4, 27.6
 */
class SearchSubmissionsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by controller
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $validStates = array_map(fn ($state) => $state->value, SubmissionState::cases());

        return [
            'q' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'in:'.implode(',', $validStates)],
            'score_min' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'score_max' => ['nullable', 'numeric', 'min:0', 'max:100', 'gte:score_min'],
            'date_from' => ['nullable', 'date', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'assignment_id' => ['nullable', 'integer', 'exists:assignments,id'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'sort_by' => ['nullable', 'string', 'in:submitted_at,score,created_at,student_name'],
            'sort_direction' => ['nullable', 'string', 'in:asc,desc'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'q' => 'search query',
            'state' => 'submission state',
            'score_min' => 'minimum score',
            'score_max' => 'maximum score',
            'date_from' => 'start date',
            'date_to' => 'end date',
            'assignment_id' => 'assignment',
            'per_page' => 'items per page',
            'sort_by' => 'sort field',
            'sort_direction' => 'sort direction',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'score_max.gte' => 'Maximum score must be greater than or equal to minimum score.',
            'date_to.after_or_equal' => 'End date must be after or equal to start date.',
        ];
    }
}
