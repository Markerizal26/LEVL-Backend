<?php

namespace Modules\Schemes\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PublishCourseRequest extends FormRequest
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
            'enrollment_type' => ['required', 'in:auto_accept,key_based,approval'],
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
            'enrollment_type' => __('validation.attributes.enrollment_type'),
            'enrollment_key' => __('validation.attributes.enrollment_key'),
        ];
    }
}
