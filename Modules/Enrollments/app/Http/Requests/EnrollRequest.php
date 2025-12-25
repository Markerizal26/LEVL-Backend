<?php

namespace Modules\Enrollments\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EnrollRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by controller logic
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'enrollment_key' => ['nullable', 'string', 'max:100'],
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
            'enrollment_key' => __('validation.attributes.enrollment_key'),
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
            'enrollment_key.max' => __('validation.max.string', [
                'attribute' => __('validation.attributes.enrollment_key'),
                'max' => 100,
            ]),
        ];
    }
}
