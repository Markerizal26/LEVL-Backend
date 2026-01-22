<?php

declare(strict_types=1);

namespace Modules\Grading\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request validation for manual grading.
 *
 * Requirements: 10.1, 12.1, 12.2
 */
class ManualGradeRequest extends FormRequest
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
            'grades' => ['required', 'array', 'min:1'],
            'grades.*.question_id' => ['required', 'integer', 'exists:questions,id'],
            'grades.*.score' => ['required', 'numeric', 'min:0'],
            'grades.*.feedback' => ['nullable', 'string'],
            'feedback' => ['nullable', 'string'],
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
            'grades' => __('validation.attributes.grades'),
            'grades.*.question_id' => __('validation.attributes.question_id'),
            'grades.*.score' => __('validation.attributes.score'),
            'grades.*.feedback' => __('validation.attributes.feedback'),
            'feedback' => __('validation.attributes.overall_feedback'),
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
            'grades.required' => 'At least one grade must be provided.',
            'grades.*.score.min' => 'Score must be a non-negative number.',
        ];
    }
}
