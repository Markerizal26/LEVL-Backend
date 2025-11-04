<?php

namespace Modules\Auth\Http\Requests\Concerns;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

trait HasApiValidation
{
    protected function failedValidation(Validator $validator)
    {
        $errors = $validator->errors()->toArray();
        $response = response()->json([
            'status' => 'error',
            'message' => 'Data yang Anda kirim tidak valid. Periksa kembali isian Anda.',
            'errors' => $errors,
        ], 422);
        throw new HttpResponseException($response);
    }
}
