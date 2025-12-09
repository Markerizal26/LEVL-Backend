<?php

namespace Modules\Learning\Http\Controllers;

use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Learning\Models\Assignment;
use Modules\Learning\Models\Submission;
use Modules\Learning\Services\SubmissionService;

/**
 * @tags Tugas & Pengumpulan
 */
class SubmissionController extends Controller
{
    use ApiResponse;

    public function __construct(private SubmissionService $service) {}

    /**
     * Mengambil daftar submission untuk assignment
     *
     * Mengambil daftar submission dari sebuah assignment. Student hanya bisa melihat submission mereka sendiri, sedangkan Admin/Instructor dapat melihat semua submission.
     *
     * Requires: Student (own submissions only), Admin, Instructor, Superadmin
     *
     *
     * @summary Mengambil daftar submission untuk assignment
     * @allowedFilters user_id
     *
     * @queryParam user_id string Filter berdasarkan ID pengguna. Example: 
     *
     * @allowedSorts created_at
     *
     * @queryParam sort string Field untuk sorting. Allowed: created_at. Prefix dengan '-' untuk descending. Example: -created_at
     *
     * @filterEnum user_id integer
     *
     * @response 200 scenario="Success" {"success": true, "data": {"submissions": [{"id": 1, "assignment_id": 1, "user_id": 1, "answer_text": "Jawaban saya...", "status": "draft", "created_at": "2024-01-15T10:00:00Z"}]}}
     * @response 403 scenario="Forbidden" {"success":false,"message":"Anda tidak memiliki akses."}
     *
     * @authenticated
     */    
    public function index(Request $request, Assignment $assignment)
    {
        /** @var \Modules\Auth\Models\User $user */
        $user = auth('api')->user();

        $submissions = $this->service->listForAssignment($assignment, $user, $request->all());

        return $this->success(['submissions' => $submissions]);
    }

    /**
     * Membuat submission baru
     *
     * Membuat submission baru untuk sebuah assignment. Submission akan dibuat dengan status "draft" secara default dan dapat diupdate sebelum submit final.
     *
     * Requires: Student
     *
     *
     * @summary Membuat submission baru
     * @response 201 scenario="Created" {"success": true, "data": {"submission": {"id": 1, "assignment_id": 1, "user_id": 1, "answer_text": "Jawaban saya...", "status": "draft"}}, "message": "Submission berhasil dibuat."}
     * @response 422 scenario="Validation Error" {"success": false, "message": "Validation error", "errors": {"answer_text": ["The answer text field is required."]}}
     *
     * @authenticated
     */    
    public function store(Request $request, Assignment $assignment)
    {
        $validated = $request->validate([
            'answer_text' => ['nullable', 'string'],
        ]);

        /** @var \Modules\Auth\Models\User $user */
        $user = auth('api')->user();

        $submission = $this->service->create($assignment, $user->id, $validated);

        return $this->created(['submission' => $submission], 'Submission berhasil dibuat.');
    }

    /**
     * Detail Submission
     *
     * Mengambil detail submission beserta assignment, user, enrollment, files, dan grade. Student hanya bisa melihat submission miliknya sendiri.
     *
     * Requires: Student (own only), Admin, Instructor, Superadmin
     *
     *
     * @summary Detail Submission
     * @response 200 scenario="Success" {"success": true, "data": {"submission": {"id": 1, "assignment_id": 1, "user_id": 1, "answer_text": "Jawaban saya...", "status": "submitted", "assignment": {"id": 1, "title": "Tugas 1"}, "user": {"id": 1, "name": "John Doe", "email": "john@example.com"}, "grade": {"score": 85, "feedback": "Bagus!"}}}}
     * @response 403 scenario="Forbidden" {"success":false,"message":"Anda tidak memiliki akses untuk melihat submission ini."}
     * @response 404 scenario="Not Found" {"success":false,"message":"Submission tidak ditemukan."}
     *
     * @authenticated
     */    
    public function show(Submission $submission)
    {
        /** @var \Modules\Auth\Models\User $user */
        $user = auth('api')->user();

        // Students can only see their own submissions
        if ($user->hasRole('Student') && $submission->user_id !== $user->id) {
            return $this->error('Anda tidak memiliki akses untuk melihat submission ini.', 403);
        }

        $submission->load(['assignment', 'user:id,name,email', 'enrollment', 'files', 'previousSubmission', 'grade']);

        return $this->success(['submission' => $submission]);
    }

    /**
     * Perbarui Submission
     *
     * Memperbarui submission yang masih berstatus draft. Student hanya bisa mengubah submission miliknya sendiri yang masih draft.
     *
     * Requires: Student (own draft only), Admin, Instructor, Superadmin
     *
     *
     * @summary Perbarui Submission
     * @response 200 scenario="Success" {"success": true, "data": {"submission": {"id": 1, "answer_text": "Jawaban yang diperbarui..."}}, "message": "Submission berhasil diperbarui."}
     * @response 403 scenario="Forbidden" {"success":false,"message":"Anda tidak memiliki akses untuk mengubah submission ini."}
     * @response 422 scenario="Validation Error" {"success":false,"message":"Hanya submission dengan status draft yang dapat diubah."}
     *
     * @authenticated
     */    
    public function update(Request $request, Submission $submission)
    {
        /** @var \Modules\Auth\Models\User $user */
        $user = auth('api')->user();

        // Students can only update their own draft submissions
        if ($user->hasRole('Student')) {
            if ($submission->user_id !== $user->id) {
                return $this->error('Anda tidak memiliki akses untuk mengubah submission ini.', 403);
            }
            if ($submission->status !== 'draft') {
                return $this->error('Hanya submission dengan status draft yang dapat diubah.', 422);
            }
        }

        $validated = $request->validate([
            'answer_text' => ['sometimes', 'string'],
        ]);

        $updated = $this->service->update($submission, $validated);

        return $this->success(['submission' => $updated], 'Submission berhasil diperbarui.');
    }

    /**
     * Nilai Submission
     *
     * Memberikan nilai dan feedback untuk submission. Hanya Admin, Instructor, atau Superadmin yang dapat menilai.
     *
     * Requires: Admin, Instructor, Superadmin
     *
     *
     * @summary Nilai Submission
     * @response 200 scenario="Success" {"success": true, "data": {"submission": {"id": 1, "status": "graded", "grade": {"score": 85, "feedback": "Bagus!"}}}, "message": "Submission berhasil dinilai."}
     * @response 403 scenario="Forbidden" {"success":false,"message":"Anda tidak memiliki akses untuk menilai submission ini."}
     * @response 422 scenario="Validation Error" {"success": false, "message": "Validation error", "errors": {"score": ["The score field is required."]}}
     *
     * @authenticated
     */    
    public function grade(Request $request, Submission $submission)
    {
        /** @var \Modules\Auth\Models\User $user */
        $user = auth('api')->user();

        // Only admin/instructor can grade
        if (! $user->hasRole('Admin') && ! $user->hasRole('Instructor') && ! $user->hasRole('Superadmin')) {
            return $this->error('Anda tidak memiliki akses untuk menilai submission ini.', 403);
        }

        $validated = $request->validate([
            'score' => ['required', 'integer', 'min:0'],
            'feedback' => ['nullable', 'string'],
        ]);

        $graded = $this->service->grade($submission, $validated['score'], $validated['feedback'] ?? null);

        return $this->success(['submission' => $graded], 'Submission berhasil dinilai.');
    }
}
