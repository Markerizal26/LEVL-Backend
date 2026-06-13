<?php

declare(strict_types=1);

namespace Modules\Grading\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BatchEssayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.submission_id' => ['required', 'integer', 'min:1'],
            'items.*.question_id' => ['nullable', 'integer', 'min:1'],
            'items.*.type' => ['nullable', Rule::in(['quiz', 'assignment'])],
        ];
    }

    public function attributes(): array
    {
        return [
            'items' => __('validation.attributes.items'),
            'items.*.submission_id' => __('validation.attributes.submission_id'),
            'items.*.question_id' => __('validation.attributes.question_id'),
            'items.*.type' => __('validation.attributes.type'),
        ];
    }
}
