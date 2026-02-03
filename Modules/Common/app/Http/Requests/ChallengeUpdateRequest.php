<?php

declare(strict_types=1);

namespace Modules\Common\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Common\Http\Requests\Concerns\HasApiValidation;
use Modules\Gamification\Enums\ChallengeType;

class ChallengeUpdateRequest extends FormRequest
{
    use HasApiValidation;

    public function authorize(): bool
    {
        return auth('api')->check() && auth('api')->user()->hasRole('Superadmin');
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'type' => ['sometimes', 'required', 'string', 'in:'.implode(',', array_column(ChallengeType::cases(), 'value'))],
            'criteria' => ['nullable', 'array'],
            'target_count' => ['sometimes', 'required', 'integer', 'min:1'],
            'points_reward' => ['sometimes', 'required', 'integer', 'min:0'],
            'badge_id' => ['nullable', 'integer', 'exists:badges,id'],
            'start_at' => ['nullable', 'date'],
            'end_at' => ['nullable', 'date', 'after:start_at'],
        ];
    }
}
