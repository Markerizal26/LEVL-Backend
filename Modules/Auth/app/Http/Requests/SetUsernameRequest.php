<?php

declare(strict_types=1);


namespace Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Auth\Models\User;

class SetUsernameRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'username' => [
                'required',
                'string',
                'min:3',
                'max:255',
                'regex:/^[a-z0-9_\.\-]+$/i',
                Rule::unique(User::class, 'username'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'username.required' => __('validation.required', ['attribute' => __('validation.attributes.username')]),
            'username.string' => __('validation.string', ['attribute' => __('validation.attributes.username')]),
            'username.min' => __('validation.min.string', ['attribute' => __('validation.attributes.username'), 'min' => 3]),
            'username.max' => __('validation.max.string', ['attribute' => __('validation.attributes.username'), 'max' => 255]),
            'username.regex' => __('validation.regex', ['attribute' => __('validation.attributes.username')]),
            'username.unique' => __('validation.unique', ['attribute' => __('validation.attributes.username')]),
        ];
    }
}
