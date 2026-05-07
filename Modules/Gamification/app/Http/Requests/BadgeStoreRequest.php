<?php

declare(strict_types=1);

namespace Modules\Gamification\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Common\Http\Requests\Concerns\HasApiValidation;
use Modules\Gamification\Enums\BadgeType;

class BadgeStoreRequest extends FormRequest
{
    use HasApiValidation;

    public function authorize(): bool
    {
        return auth('api')->check() && (auth('api')->user()->hasRole('Superadmin') || auth('api')->user()->hasRole('Admin'));
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:50', 'unique:badges,code'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'type' => ['required', 'string', 'in:'.implode(',', array_column(BadgeType::cases(), 'value'))],
            'rarity' => ['nullable', 'string', 'in:common,uncommon,rare,epic,legendary'],
            'xp_reward' => ['required', 'integer', 'min:1', 'max:10000'],
            'active' => ['nullable', 'boolean'],
            'threshold' => ['nullable', 'integer', 'min:1'],
            'is_repeatable' => ['nullable', 'boolean'],
            'max_awards_per_user' => ['nullable', 'integer', 'min:1'],
            'icon' => ['required', 'file', 'mimes:jpeg,png,svg,webp', 'max:2048'],
            'rules' => ['nullable', 'array'],
            'rules.*.event_trigger' => ['required', 'string', 'max:100'],
            'rules.*.conditions' => ['nullable', 'array'],
            'rules.*.conditions.course_slug' => ['nullable', 'string', 'exists:courses,slug'],
            'rules.*.priority' => ['nullable', 'integer', 'min:0'],
            'rules.*.cooldown_seconds' => ['nullable', 'integer', 'min:0'],
            'rules.*.rule_enabled' => ['nullable', 'boolean'],
        ];
    }
}
