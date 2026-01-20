<?php

declare(strict_types=1);


namespace Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeleteAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'password' => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'password.required' => __('validation.required', ['attribute' => __('validation.attributes.password')]),
        ];
    }
}
