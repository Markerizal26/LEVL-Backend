<?php

declare(strict_types=1);

namespace Modules\Enrollments\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkEnrollmentActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'enrollment_ids' => ['required', 'array', 'min:1', 'max:100'],
            'enrollment_ids.*' => ['integer', 'exists:enrollments,id'],
        ];
    }

    public function attributes(): array
    {
        return [
            'enrollment_ids' => __('validation.attributes.enrollment_ids'),
        ];
    }
}
