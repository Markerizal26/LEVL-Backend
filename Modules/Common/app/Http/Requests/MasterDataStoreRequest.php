<?php

namespace Modules\Common\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MasterDataStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'value' => 'required|string|max:100',
            'label' => 'required|string|max:255',
            'metadata' => 'nullable|array',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'value.required' => __('validation.required', ['attribute' => __('validation.attributes.value')]),
            'value.max' => __('validation.max.string', ['attribute' => __('validation.attributes.value'), 'max' => 100]),
            'label.required' => __('validation.required', ['attribute' => __('validation.attributes.label')]),
            'label.max' => __('validation.max.string', ['attribute' => __('validation.attributes.label'), 'max' => 255]),
        ];
    }
}
