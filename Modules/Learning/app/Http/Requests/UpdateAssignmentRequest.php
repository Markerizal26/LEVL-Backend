<?php

declare(strict_types=1);

namespace Modules\Learning\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Learning\Enums\AssignmentStatus;
use Modules\Learning\Enums\RandomizationType;
use Modules\Learning\Enums\ReviewMode;
use Modules\Learning\Enums\SubmissionType;

class UpdateAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; 
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'submission_type' => ['sometimes', Rule::enum(SubmissionType::class)],
            'max_score' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'available_from' => ['nullable', 'date'],
            'deadline_at' => ['nullable', 'date', 'after_or_equal:available_from'],
            'status' => ['sometimes', Rule::enum(AssignmentStatus::class)],
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
            'delete_attachments' => ['nullable', 'array'],
            'delete_attachments.*' => ['integer', 'exists:media,id'],
        ];

    }

    public function attributes(): array
    {
        return [
            'title' => 'Judul',
            'description' => 'Deskripsi',
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
            'delete_attachments' => 'Hapus Lampiran',
            'delete_attachments.*' => 'ID Lampiran yang Dihapus',
        ];
    }
}
