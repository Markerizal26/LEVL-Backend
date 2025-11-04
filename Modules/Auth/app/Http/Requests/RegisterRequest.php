<?php

namespace Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Auth\Http\Requests\Concerns\HasApiValidation;
use Modules\Auth\Http\Requests\Concerns\HasAuthRequestRules;
use Modules\Auth\Http\Requests\Concerns\HasCommonValidationMessages;
use Modules\Auth\Http\Requests\Concerns\HasPasswordRules;

class RegisterRequest extends FormRequest
{
    use HasApiValidation, HasAuthRequestRules, HasCommonValidationMessages, HasPasswordRules;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return $this->rulesRegister();
    }

    public function messages(): array
    {
        return $this->messagesRegister();
    }
}
