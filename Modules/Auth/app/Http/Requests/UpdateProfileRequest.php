<?php

declare(strict_types=1);


namespace Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->user()->id;

        return [
            'name' => 'sometimes|string|max:100',
            'email' => "sometimes|email|max:191|unique:users,email,{$userId}",
            'phone' => 'sometimes|nullable|string|max:20|regex:/^[0-9+\-\s()]+$/',
            'bio' => 'sometimes|nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'name.max' => __('validation.max.string', ['attribute' => __('validation.attributes.name'), 'max' => 100]),
            'email.email' => __('validation.email', ['attribute' => __('validation.attributes.email')]),
            'email.unique' => __('validation.unique', ['attribute' => __('validation.attributes.email')]),
            'phone.regex' => __('validation.regex', ['attribute' => __('validation.attributes.phone')]),
            'bio.max' => __('validation.max.string', ['attribute' => __('validation.attributes.bio'), 'max' => 1000]),
        ];
    }
}
