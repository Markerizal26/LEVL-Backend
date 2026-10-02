<?php

declare(strict_types=1);

namespace Modules\Learning\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Learning\Enums\QuestionType;

class StoreQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('answer_key') && ! is_array($this->answer_key)) {
            $this->merge([
                'answer_key' => [$this->answer_key],
            ]);
        }
    }

    public function messages(): array
    {
        return [
            'attachments.*.max' => __('messages.validations.attachments_max_size'),
        ];
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(QuestionType::class)],
            'content' => ['required', 'string', 'max:65535'],
            'options' => ['nullable', 'array'],
            'options.*' => ['nullable'],
            'options.*.image' => ['nullable', 'file', 'image', 'max:2048'],
            'answer_key' => ['nullable', 'array'],
            'weight' => ['required', 'numeric', 'gt:0'],
            'order' => ['nullable', 'integer', 'min:0'],
            'max_score' => ['nullable', 'numeric', 'gt:0'],
            'max_file_size' => ['nullable', 'integer', 'min:1', 'max:52428800'],
            'allowed_file_types' => ['nullable', 'array'],
            'allowed_file_types.*' => ['string', 'max:50'],
            'allow_multiple_files' => ['nullable', 'boolean'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['image', 'max:2048'],
        ];
    }

    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(function (\Illuminate\Validation\Validator $validator) {
            $type = $this->input('type');

            if (in_array($type, [QuestionType::MultipleChoice->value, QuestionType::Checkbox->value])) {
                $options = $this->input('options') ?? [];
                $answerKey = $this->input('answer_key');

                if (empty($options)) {
                    $validator->errors()->add('options', __('messages.validations.question_options_required'));
                }

                if (empty($answerKey)) {
                    $validator->errors()->add('answer_key', __('messages.validations.question_answer_key_required'));
                } else {
                    if (is_array($answerKey)) {
                        if ($type === QuestionType::MultipleChoice->value && count($answerKey) > 1) {
                            $validator->errors()->add('answer_key', __('messages.validations.multiple_choice_single_answer'));
                        }
                        foreach ($answerKey as $keyIndex) {
                            if (! array_key_exists($keyIndex, $options)) {
                                $validator->errors()->add('answer_key', __('messages.validations.invalid_answer_key_index', ['index' => $keyIndex]));
                            }
                        }
                    }
                }
            }

            if ($type === QuestionType::FileUpload->value) {
                if (empty($this->input('allowed_file_types'))) {
                    $validator->errors()->add('allowed_file_types', __('messages.validations.question_file_types_required'));
                }
            }
        });
    }

    public function attributes(): array
    {
        return [
            'type' => __('validation.attributes.question_type'),
            'content' => __('validation.attributes.question_content'),
            'options' => __('validation.attributes.options'),
            'answer_key' => __('validation.attributes.answer_key'),
            'weight' => __('validation.attributes.weight'),
            'order' => __('validation.attributes.order'),
            'max_score' => __('validation.attributes.max_score'),
            'max_file_size' => __('validation.attributes.max_file_size'),
            'allowed_file_types' => __('validation.attributes.allowed_file_types'),
            'allow_multiple_files' => __('validation.attributes.allow_multiple_files'),
            'attachments' => __('validation.attributes.attachments'),
            'attachments.*' => __('validation.attributes.attachments'),
        ];
    }
}
