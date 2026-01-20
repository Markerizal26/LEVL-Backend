<?php

declare(strict_types=1);


namespace Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Auth\Models\ProfilePrivacySetting;

class UpdatePrivacySettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'profile_visibility' => [
                'sometimes',
                Rule::in([
                    ProfilePrivacySetting::VISIBILITY_PUBLIC,
                    ProfilePrivacySetting::VISIBILITY_PRIVATE,
                    ProfilePrivacySetting::VISIBILITY_FRIENDS,
                ]),
            ],
            'show_email' => 'sometimes|boolean',
            'show_phone' => 'sometimes|boolean',
            'show_activity_history' => 'sometimes|boolean',
            'show_achievements' => 'sometimes|boolean',
            'show_statistics' => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'profile_visibility.in' => __('validation.in', ['attribute' => __('validation.attributes.profile_visibility')]),
        ];
    }
}
