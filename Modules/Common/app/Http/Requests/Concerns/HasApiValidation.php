<?php

namespace Modules\Common\Http\Requests\Concerns;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

trait HasApiValidation
{
    protected function failedValidation(Validator $validator)
    {
        $errors = $validator->errors()->toArray();
        $response = response()->json([
            'status' => 'error',
            'message' => __('messages.validation.failed'),
            'errors' => $errors,
        ], 422);
        throw new HttpResponseException($response);
    }
}
