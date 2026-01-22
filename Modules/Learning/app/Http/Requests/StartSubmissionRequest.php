<?php

declare(strict_types=1);

namespace Modules\Learning\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StartSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; 
    }

    public function rules(): array
    {
        return [
            
            
        ];
    }
}
