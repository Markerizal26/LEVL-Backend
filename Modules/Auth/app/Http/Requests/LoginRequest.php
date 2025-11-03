<?php

namespace Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Auth\Http\Requests\Concerns\HasCommonValidationMessages;

class LoginRequest extends FormRequest
{
    use HasCommonValidationMessages;
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'login' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
        ];
    }

    public function messages(): array
    {
        return array_merge($this->commonMessages(), [
            'login.required' => 'Login wajib diisi (email atau username).',
            'login.string' => 'Login harus berupa teks.',
            'login.max' => 'Login maksimal 255 karakter.',

            'password.required' => 'Password wajib diisi.',
            'password.string' => 'Password harus berupa teks.',
            'password.min' => 'Password minimal 8 karakter.',
        ]);
    }
}


