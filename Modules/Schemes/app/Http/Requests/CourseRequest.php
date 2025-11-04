<?php

namespace Modules\Schemes\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Schemes\Http\Requests\Concerns\HasApiValidation;
use Modules\Schemes\Http\Requests\Concerns\HasSchemesRequestRules;

class CourseRequest extends FormRequest
{
    use HasApiValidation, HasSchemesRequestRules;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $courseId = $this->route('course') ? (int) $this->route('course') : 0;

        return $this->rulesCourse($courseId);
    }

    public function messages(): array
    {
        return $this->messagesCourse();
    }

    public function validated($key = null, $default = null)
    {
        $data = parent::validated($key, $default);
        if (array_key_exists('tags', $data)) {
            $data['tags_json'] = $data['tags'];
            unset($data['tags']);
        }

        return $data;
    }
}
