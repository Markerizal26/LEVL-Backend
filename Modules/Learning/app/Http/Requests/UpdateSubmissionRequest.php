<?php

declare(strict_types=1);

namespace Modules\Learning\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; 
    }

    public function rules(): array
    {
        return [
            'answer_text' => ['sometimes', 'string'],
        ];
    }

    public function attributes(): array
    {
        return [
            'answer_text' => __('validation.attributes.answer_text'),
        ];
    }
}
