<?php

declare(strict_types=1);

namespace Modules\Learning\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GradeSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; 
    }

    public function rules(): array
    {
        return [
            'score' => ['required', 'integer', 'min:0'],
            'feedback' => ['nullable', 'string'],
        ];
    }

    public function attributes(): array
    {
        return [
            'score' => __('validation.attributes.score'),
            'feedback' => __('validation.attributes.feedback'),
        ];
    }
}
