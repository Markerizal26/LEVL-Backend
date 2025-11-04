<?php

namespace Modules\Common\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Common\Http\Requests\Concerns\HasApiValidation;
use Modules\Common\Http\Requests\Concerns\HasCommonRequestRules;

class CategoryStoreRequest extends FormRequest
{
    use HasApiValidation, HasCommonRequestRules;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return $this->rulesCategoryStore();
    }

    public function messages(): array
    {
        return $this->messagesCategoryStore();
    }
}
