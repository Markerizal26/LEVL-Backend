<?php

namespace Modules\Common\Http\Requests\Concerns;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

trait HasApiValidation
{
    protected function failedValidation(Validator $validator)
    {
        $errors = $validator->errors()->toArray();
        throw new HttpResponseException(response()->json([
            'status' => 'error',
            'message' => 'Data yang Anda kirim tidak valid. Periksa kembali isian Anda.',
            'errors' => $errors,
        ], 422));
    }
}
