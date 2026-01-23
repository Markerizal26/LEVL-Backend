<?php

declare(strict_types=1);

namespace Modules\Learning\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Learning\Enums\AssignmentStatus;
use Modules\Learning\Enums\RandomizationType;
use Modules\Learning\Enums\ReviewMode;
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
            'assignable_type' => [
                'required',
                'string',
                Rule::in(['Course', 'Unit', 'Lesson']),
            ],
            'assignable_slug' => ['required', 'string'],
            'assignable_id' => ['nullable', 'integer'],
            'submission_type' => ['required', Rule::enum(SubmissionType::class)],
            'max_score' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'available_from' => ['nullable', 'date'],
            'deadline_at' => ['nullable', 'date', 'after_or_equal:available_from'],
            'status' => ['nullable', Rule::enum(AssignmentStatus::class)],
            'allow_resubmit' => ['nullable', 'boolean'],
            'late_penalty_percent' => ['nullable', 'integer', 'min:0', 'max:100'],
            'tolerance_minutes' => ['nullable', 'integer', 'min:0'],
            'max_attempts' => ['nullable', 'integer', 'min:1'],
            'cooldown_minutes' => ['nullable', 'integer', 'min:0'],
            'retake_enabled' => ['nullable', 'boolean'],
            'review_mode' => ['nullable', Rule::enum(ReviewMode::class)],
            'randomization_type' => ['nullable', Rule::enum(RandomizationType::class)],
            'question_bank_count' => ['nullable', 'integer', 'min:0'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,zip,jpg,jpeg,png,webp', 'max:10240'],
        ];
    }

    private ?array $resolvedScope = null;

    public function getResolvedScope(): ?array
    {
        return $this->resolvedScope;
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $typeMap = [
                'Course' => 'Modules\\Schemes\\Models\\Course',
                'Unit' => 'Modules\\Schemes\\Models\\Unit',
                'Lesson' => 'Modules\\Schemes\\Models\\Lesson',
            ];

            $assignableType = $this->input('assignable_type');
            $assignableSlug = $this->input('assignable_slug');

            if ($assignableType && isset($typeMap[$assignableType]) && $assignableSlug) {
                $modelClass = $typeMap[$assignableType];
                
                $query = $modelClass::where('slug', $assignableSlug);
                
                if (in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive($modelClass))) {
                    $query->whereNull('deleted_at');
                }
                
                $model = $query->first();
                
                if (!$model) {
                    $validator->errors()->add('assignable_slug', __('validation.exists', ['attribute' => 'assignable slug']));
                } else {
                    $this->resolvedScope = [
                        'assignable_id' => $model->id,
                        'assignable_type' => $modelClass,
                    ];
                    // Also merge for convenience if needed, but we rely on getter now
                    $this->merge($this->resolvedScope);
                }
            }
        });
    }

    public function attributes(): array
    {
        return [
            'title' => 'Judul',
            'description' => 'Deskripsi',
            'assignable_type' => 'Tipe Cakupan',
            'assignable_slug' => 'Slug Cakupan',
            'submission_type' => 'Tipe Pengumpulan',
            'max_score' => 'Skor Maksimal',
            'available_from' => 'Waktu Mulai',
            'deadline_at' => 'Tenggat Waktu',
            'status' => 'Status',
            'allow_resubmit' => 'Izinkan Kirim Ulang',
            'late_penalty_percent' => 'Persentase Penalti',
            'tolerance_minutes' => 'Toleransi Keterlambatan',
            'max_attempts' => 'Maksimal Percobaan',
            'cooldown_minutes' => 'Jeda Waktu',
            'retake_enabled' => 'Izinkan Mengulang',
            'review_mode' => 'Mode Tinjauan',
            'randomization_type' => 'Tipe Pengacakan',
            'question_bank_count' => 'Jumlah Soal Bank',
            'attachments' => 'Lampiran',
            'attachments.*' => 'File Lampiran',
            'assignable_id' => 'ID Cakupan',
        ];
    }
}
