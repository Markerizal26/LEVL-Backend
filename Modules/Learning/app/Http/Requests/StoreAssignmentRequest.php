<?php

declare(strict_types=1);

namespace Modules\Learning\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Support\ValidationRules\UploadSizeRule;
use Modules\Learning\Enums\AssignmentStatus;
use Modules\Learning\Enums\SubmissionType;

class StoreAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'unit_slug' => ['required', 'string', 'exists:units,slug'],
            'submission_type' => ['required', Rule::enum(SubmissionType::class)],
            'max_score' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'passing_grade' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'status' => ['nullable', Rule::enum(AssignmentStatus::class)],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', new UploadSizeRule()],
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => __('validation.attributes.title'),
            'description' => __('validation.attributes.description'),
            'unit_slug' => __('validation.attributes.unit_slug'),
            'submission_type' => __('validation.attributes.submission_type'),
            'max_score' => __('validation.attributes.max_score'),
            'passing_grade' => __('validation.attributes.passing_grade'),
            'status' => __('validation.attributes.status'),
            'attachments' => __('validation.attributes.attachments'),
            'attachments.*' => __('validation.attributes.attachments'),
        ];
    }

    public function getResolvedScope(): array
    {
        $unit = \Modules\Schemes\Models\Unit::where('slug', $this->input('unit_slug'))->firstOrFail();

        return [
            'assignable_type' => \Modules\Schemes\Models\Unit::class,
            'assignable_id' => $unit->id,
        ];
    }

    public function validated($key = null, $default = null)
    {
        $data = parent::validated($key, $default);
        $unit = \Modules\Schemes\Models\Unit::where('slug', $this->input('unit_slug'))->firstOrFail();
        $data['unit_id'] = $unit->id;
        unset($data['unit_slug']);

        return $data;
    }
}
