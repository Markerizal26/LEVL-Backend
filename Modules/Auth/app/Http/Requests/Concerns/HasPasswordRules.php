<?php

namespace Modules\Auth\Http\Requests\Concerns;

use Illuminate\Validation\Rules\Password as PasswordRule;

trait HasPasswordRules
{
    /**
     * Strong password rules for resets/changes (includes uncompromised).
     */
    protected function passwordRulesStrong(): array
    {
        return [
            'required',
            'string',
            'confirmed',
            PasswordRule::min(8)->letters()->mixedCase()->numbers()->symbols()->uncompromised(),
        ];
    }

    /**
     * Registration password rules (no uncompromised check, faster UX).
     */
    protected function passwordRulesRegistration(): array
    {
        return [
            'required',
            'string',
            'confirmed',
            PasswordRule::min(8)->letters()->mixedCase()->numbers()->symbols(),
        ];
    }

    /**
     * Standard Indonesian messages for password validation.
     */
    protected function passwordMessages(): array
    {
        return [
            'password.required' => 'Password wajib diisi.',
            'password.string' => 'Password harus berupa teks.',
            'password.confirmed' => 'Konfirmasi password tidak sama.',
            'password.min' => 'Password minimal :min karakter.',
            'password.letters' => 'Password harus mengandung huruf.',
            'password.mixed' => 'Password harus mengandung huruf besar dan huruf kecil.',
            'password.numbers' => 'Password harus mengandung angka.',
            'password.symbols' => 'Password harus mengandung simbol.',
            'password.uncompromised' => 'Password terdeteksi dalam kebocoran data. Gunakan password lain.',
        ];
    }
}
