<?php

declare(strict_types=1);

namespace Modules\Common\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Common\Http\Requests\Concerns\HasApiValidation;

class LevelConfigUpdateRequest extends FormRequest
{
    use HasApiValidation;

    public function authorize(): bool
    {
        return auth('api')->check() && auth('api')->user()->hasRole('Superadmin');
    }

    public function rules(): array
    {
        $levelConfigId = $this->route('level_config');

        return [
            'level' => ['sometimes', 'required', 'integer', 'min:1', 'unique:level_configs,level,'.$levelConfigId],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'xp_required' => ['sometimes', 'required', 'integer', 'min:0'],
            'rewards' => ['nullable', 'array'],
            'rewards.*.type' => ['required_with:rewards', 'string'],
            'rewards.*.value' => ['required_with:rewards'],
        ];
    }
}
