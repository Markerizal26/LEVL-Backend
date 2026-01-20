<?php

declare(strict_types=1);


namespace Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Common\Http\Requests\Concerns\HasApiValidation;

class VerifyEmailByTokenRequest extends FormRequest
{
    use HasApiValidation;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token' => ['required', 'string', 'size:16'],
            'uuid' => ['required', 'string', 'uuid'],
        ];
    }

    public function messages(): array
    {
        return [
            'token.required' => __('validation.required', ['attribute' => __('validation.attributes.token')]),
            'token.string' => __('validation.string', ['attribute' => __('validation.attributes.token')]),
            'token.size' => __('validation.size.string', ['attribute' => __('validation.attributes.token'), 'size' => 16]),
            'uuid.required' => __('validation.required', ['attribute' => __('validation.attributes.uuid')]),
            'uuid.string' => __('validation.string', ['attribute' => __('validation.attributes.uuid')]),
            'uuid.uuid' => __('validation.uuid', ['attribute' => __('validation.attributes.uuid')]),
        ];
    }
}

