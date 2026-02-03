<?php

declare(strict_types=1);

namespace Modules\Common\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Common\Http\Requests\Concerns\HasApiValidation;
use Modules\Common\Http\Requests\Concerns\HasCommonRequestRules;
use Modules\Common\Models\Category;

class CategoryUpdateRequest extends FormRequest
{
    use HasApiValidation, HasCommonRequestRules;

    public function authorize(): bool
    {
        return auth('api')->check() && auth('api')->user()->hasRole('Superadmin');
    }

    public function rules(): array
    {
        $categoryId = (int) $this->route('category');

        return $this->rulesCategoryUpdate($categoryId);
    }

    public function messages(): array
    {
        return $this->messagesCategoryUpdate();
    }
}
