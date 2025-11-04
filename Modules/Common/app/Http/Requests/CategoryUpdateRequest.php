<?php

namespace Modules\Common\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Common\Http\Requests\Concerns\HasApiValidation;
use Modules\Common\Http\Requests\Concerns\HasCommonRequestRules;

class CategoryUpdateRequest extends FormRequest
{
    use HasApiValidation, HasCommonRequestRules;

    public function authorize(): bool
    {
        return true;
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
