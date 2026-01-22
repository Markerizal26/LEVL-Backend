<?php

declare(strict_types=1);

namespace Modules\Grading\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request validation for appeal submission.
 *
 * Requirements: 17.1, 17.2
 */
class SubmitAppealRequest extends FormRequest
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
            'reason' => ['required', 'string', 'min:10', 'max:2000'],
            'documents' => ['sometimes', 'array'],
            'documents.*' => ['file', 'max:10240', 'mimes:pdf,doc,docx,jpg,jpeg,png'],
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
            'reason' => __('validation.attributes.reason'),
            'documents' => __('validation.attributes.documents'),
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
            'reason.required' => 'A reason is required for the appeal.',
            'reason.min' => 'The reason must be at least 10 characters.',
            'documents.*.max' => 'Each document must not exceed 10MB.',
        ];
    }
}
