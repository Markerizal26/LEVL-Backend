<?php

declare(strict_types=1);

namespace Modules\Learning\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request validation for starting a new submission.
 *
 * Requirements: 6.3, 6.4, 7.3, 7.4, 8.3
 */
class StartSubmissionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by controller
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // No additional fields required for starting a submission
            // The assignment ID comes from the route parameter
        ];
    }
}
