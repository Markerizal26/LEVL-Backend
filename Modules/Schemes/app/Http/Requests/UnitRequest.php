<?php

namespace Modules\Schemes\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Schemes\Http\Requests\Concerns\HasApiValidation;
use Modules\Schemes\Http\Requests\Concerns\HasSchemesRequestRules;

class UnitRequest extends FormRequest
{
    use HasApiValidation, HasSchemesRequestRules;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $course = $this->route('course');
        $courseId = $course ? (is_object($course) ? $course->id : (int) $course) : 0;
        
        $unit = $this->route('unit');
        $unitId = $unit ? (is_object($unit) ? $unit->id : (int) $unit) : 0;

        return $this->rulesUnit($courseId, $unitId);
    }

    public function messages(): array
    {
        return $this->messagesUnit();
    }
}
