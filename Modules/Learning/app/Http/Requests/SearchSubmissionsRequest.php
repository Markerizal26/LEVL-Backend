<?php

declare(strict_types=1);

namespace Modules\Learning\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Learning\Enums\SubmissionState;

class SearchSubmissionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; 
    }

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

    public function attributes(): array
    {
        return [
            'q' => __('validation.attributes.search_query'),
            'state' => __('validation.attributes.submission_state'),
            'score_min' => __('validation.attributes.minimum_score'),
            'score_max' => __('validation.attributes.maximum_score'),
            'date_from' => __('validation.attributes.start_date'),
            'date_to' => __('validation.attributes.end_date'),
            'assignment_id' => __('validation.attributes.assignment'),
            'per_page' => __('validation.attributes.items_per_page'),
            'sort_by' => __('validation.attributes.sort_field'),
            'sort_direction' => __('validation.attributes.sort_direction'),
        ];
    }

    public function messages(): array
    {
        return [
            'score_max.gte' => __('messages.validations.score_max_gte'),
            'date_to.after_or_equal' => __('messages.validations.date_to_after_equal'),
        ];
    }
}
