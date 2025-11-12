<?php

namespace Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Auth\Models\User;

class SetUsernameRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'username' => [
                'required',
                'string',
                'min:3',
                'max:255',
                'regex:/^[a-z0-9_\.\-]+$/i',
                Rule::unique(User::class, 'username'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'username.required' => 'Username harus diisi.',
            'username.string' => 'Username harus berupa teks.',
            'username.min' => 'Username minimal 3 karakter.',
            'username.max' => 'Username maksimal 255 karakter.',
            'username.regex' => 'Username hanya boleh mengandung huruf, angka, titik, garis bawah, dan garis sambung. Tidak boleh mengandung spasi.',
            'username.unique' => 'Username sudah digunakan.',
        ];
    }
}
