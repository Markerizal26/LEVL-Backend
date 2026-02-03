<?php

declare(strict_types=1);

namespace Modules\Common\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Common\Http\Requests\Concerns\HasApiValidation;
use Modules\Gamification\Enums\BadgeType;

class BadgeUpdateRequest extends FormRequest
{
    use HasApiValidation;

    public function authorize(): bool
    {
        return auth('api')->check() && auth('api')->user()->hasRole('Superadmin');
    }

    public function rules(): array
    {
        $badgeId = $this->route('badge');

        return [
            'code' => ['sometimes', 'required', 'string', 'max:50', 'unique:badges,code,'.$badgeId],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'type' => ['sometimes', 'required', 'string', 'in:'.implode(',', array_column(BadgeType::cases(), 'value'))],
            'threshold' => ['nullable', 'integer', 'min:1'],
            'icon' => ['nullable', 'file', 'mimes:jpeg,png,svg,webp', 'max:2048'],
            'rules' => ['nullable', 'array'],
            'rules.*.criterion' => ['required', 'string', 'max:50'],
            'rules.*.operator' => ['required', 'string', 'in:=,>=,>'],
            'rules.*.value' => ['required', 'integer', 'min:1'],
        ];
    }
}
