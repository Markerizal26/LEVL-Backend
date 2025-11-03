<?php

namespace Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token' => ['required', 'regex:/^\d{6}$/'],
            'password' => [
                'required',
                'string',
                'confirmed',
                PasswordRule::min(8)->letters()->mixedCase()->numbers()->symbols()->uncompromised(),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'token.required' => 'Kode reset wajib diisi.',
            'token.regex' => 'Kode reset harus 6 digit angka.',
            'password.required' => 'Kata sandi baru wajib diisi.',
            'password.string' => 'Kata sandi baru harus berupa teks.',
            'password.confirmed' => 'Konfirmasi kata sandi baru tidak cocok.',
            'password.min' => 'Kata sandi baru minimal :min karakter.',
            'password.letters' => 'Kata sandi baru harus mengandung huruf.',
            'password.mixed' => 'Kata sandi baru harus mengandung huruf besar dan kecil.',
            'password.numbers' => 'Kata sandi baru harus mengandung angka.',
            'password.symbols' => 'Kata sandi baru harus mengandung simbol.',
            'password.uncompromised' => 'Kata sandi baru terdeteksi dalam kebocoran data. Gunakan kata sandi lain.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        $errors = $validator->errors()->toArray();
        throw new HttpResponseException(
            response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal',
                'errors' => $errors,
            ], 422)
        );
    }
}


