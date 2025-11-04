<?php

namespace Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Auth\Http\Requests\Concerns\HasApiValidation;
use Modules\Auth\Http\Requests\Concerns\HasAuthRequestRules;
use Modules\Auth\Http\Requests\Concerns\HasCommonValidationMessages;

class LoginRequest extends FormRequest
{
    use HasApiValidation, HasAuthRequestRules, HasCommonValidationMessages;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return $this->rulesLogin();
    }

    public function messages(): array
    {
        return $this->messagesLogin();
    }
}
