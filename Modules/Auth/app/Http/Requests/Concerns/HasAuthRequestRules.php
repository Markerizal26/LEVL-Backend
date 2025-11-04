<?php

namespace Modules\Auth\Http\Requests\Concerns;

trait HasAuthRequestRules
{
    use HasCommonValidationMessages;
    use HasPasswordRules;

    protected function rulesLogin(): array
    {
        return [
            'login' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
        ];
    }

    protected function messagesLogin(): array
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

    protected function rulesRegister(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:50', 'unique:users,username'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => $this->passwordRulesRegistration(),
        ];
    }

    protected function messagesRegister(): array
    {
        return array_merge($this->commonMessages(), $this->passwordMessages(), [
            'name.required' => 'Nama wajib diisi.',
            'name.string' => 'Nama harus berupa teks.',
            'name.max' => 'Nama maksimal 255 karakter.',
            'username.required' => 'Username wajib diisi.',
            'username.string' => 'Username harus berupa teks.',
            'username.max' => 'Username maksimal 50 karakter.',
            'username.unique' => 'Username sudah digunakan.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.max' => 'Email maksimal 255 karakter.',
            'email.unique' => 'Email sudah digunakan.',
        ]);
    }

    protected function rulesCreateManagedUser(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:users,username'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
        ];
    }

    protected function messagesCreateManagedUser(): array
    {
        return [
            'name.required' => 'Nama wajib diisi.',
            'name.string' => 'Nama harus berupa teks.',
            'name.max' => 'Nama maksimal 255 karakter.',
            'username.required' => 'Username wajib diisi.',
            'username.string' => 'Username harus berupa teks.',
            'username.max' => 'Username maksimal 255 karakter.',
            'username.unique' => 'Username sudah digunakan.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.max' => 'Email maksimal 255 karakter.',
            'email.unique' => 'Email sudah digunakan.',
        ];
    }

    protected function rulesChangePassword(): array
    {
        return [
            'current_password' => ['required', 'string'],
            'password' => $this->passwordRulesStrong(),
        ];
    }

    protected function messagesChangePassword(): array
    {
        return array_merge($this->passwordMessages(), [
            'current_password.required' => 'Password lama wajib diisi.',
        ]);
    }

    protected function rulesResetPassword(): array
    {
        return [
            'token' => ['required', 'regex:/^\d{6}$/'],
            'password' => $this->passwordRulesStrong(),
        ];
    }

    protected function messagesResetPassword(): array
    {
        return array_merge($this->passwordMessages(), [
            'token.required' => 'Kode reset wajib diisi.',
            'token.regex' => 'Kode reset harus 6 digit angka.',
        ]);
    }

    protected function rulesRefresh(): array
    {
        return [
            'refresh_token' => ['required', 'string'],
        ];
    }

    protected function messagesRefresh(): array
    {
        return [
            'refresh_token.required' => 'Refresh token wajib diisi.',
            'refresh_token.string' => 'Refresh token harus berupa teks.',
        ];
    }

    protected function rulesLogout(): array
    {
        return [
            'refresh_token' => ['nullable', 'string'],
        ];
    }

    protected function messagesLogout(): array
    {
        return [
            'refresh_token.string' => 'Refresh token harus berupa teks.',
        ];
    }
}
