<?php

namespace Modules\Forums\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateReplyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization is handled by policy
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'content' => 'required|string',
            'parent_id' => 'nullable|integer|exists:replies,id',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'content.required' => __('validation.required', ['attribute' => __('validation.attributes.content')]),
            'parent_id.exists' => __('validation.exists', ['attribute' => __('validation.attributes.parent_reply')]),
        ];
    }
}
