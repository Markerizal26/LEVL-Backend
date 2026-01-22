<?php

declare(strict_types=1);

namespace Modules\Grading\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request validation for bulk grade release.
 *
 * Requirements: 26.2, 26.5, 28.6
 */
class BulkReleaseGradesRequest extends FormRequest
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
        return [
            'submission_ids' => ['required', 'array', 'min:1'],
            'submission_ids.*' => ['required', 'integer', 'exists:submissions,id'],
            'async' => ['sometimes', 'boolean'],
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
            'submission_ids' => __('validation.attributes.submissions'),
            'submission_ids.*' => __('validation.attributes.submission'),
            'async' => __('validation.attributes.async'),
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
            'submission_ids.required' => 'At least one submission must be selected.',
            'submission_ids.min' => 'At least one submission must be selected.',
        ];
    }
}
