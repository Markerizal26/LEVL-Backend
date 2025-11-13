<?php

namespace Modules\Schemes\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Schemes\Http\Requests\Concerns\HasApiValidation;
use Modules\Schemes\Http\Requests\Concerns\HasSchemesRequestRules;

class LessonRequest extends FormRequest
{
    use HasApiValidation, HasSchemesRequestRules;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $unit = $this->route('unit');
        $unitId = $unit ? (is_object($unit) ? $unit->id : (int) $unit) : 0;
        
        $lesson = $this->route('lesson');
        $lessonId = $lesson ? (is_object($lesson) ? $lesson->id : (int) $lesson) : 0;

        return $this->rulesLesson($unitId, $lessonId);
    }

    public function messages(): array
    {
        return $this->messagesLesson();
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('markdown_content') && is_string($this->markdown_content)) {
            $this->merge([
                'markdown_content' => strip_tags($this->markdown_content),
            ]);
        }
    }
}
