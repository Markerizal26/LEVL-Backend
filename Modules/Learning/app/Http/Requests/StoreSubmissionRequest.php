<?php

declare(strict_types=1);

namespace Modules\Learning\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Learning\Enums\SubmissionType;
use Modules\Learning\Models\Assignment;

class StoreSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $assignment = $this->route('assignment');

        if (! $assignment instanceof Assignment) {
            return [];
        }

        $rules = [];

        switch ($assignment->submission_type) {
            case SubmissionType::Text:
                $rules['answer_text'] = ['required', 'string', 'min:10'];
                $rules['files'] = ['prohibited'];
                break;

            case SubmissionType::File:
                $rules['files'] = ['required', 'array', 'min:1'];
                $rules['files.*'] = ['file', 'max:10240'];
                break;

            case SubmissionType::Mixed:
                $rules['answer_text'] = ['nullable', 'string'];
                $rules['files'] = ['nullable', 'array'];
                $rules['files.*'] = ['file', 'max:10240'];
                break;
        }

        return $rules;
    }

    public function attributes(): array
    {
        return [
            'answer_text' => __('validation.attributes.answer_text'),
            'files' => __('validation.attributes.files'),
            'files.*' => __('validation.attributes.file'),
        ];
    }

    public function messages(): array
    {
        $assignment = $this->route('assignment');

        if (! $assignment instanceof Assignment) {
            return [];
        }

        $messages = [];

        if ($assignment->submission_type === SubmissionType::Text) {
            $messages['answer_text.required'] = 'Jawaban teks wajib diisi untuk tugas jenis teks.';
            $messages['files.prohibited'] = 'Tugas ini hanya menerima jawaban teks, tidak menerima file.';
        }

        if ($assignment->submission_type === SubmissionType::File) {
            $messages['files.required'] = 'File wajib diunggah untuk tugas jenis file.';
            $messages['files.min'] = 'Minimal 1 file harus diunggah.';
        }

        return $messages;
    }
}
