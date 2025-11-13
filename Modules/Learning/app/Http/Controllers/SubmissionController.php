<?php

namespace Modules\Learning\Http\Controllers;

use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Learning\Models\Assignment;
use Modules\Learning\Models\Submission;
use Modules\Learning\Services\SubmissionService;

class SubmissionController extends Controller
{
    use ApiResponse;

    public function __construct(private SubmissionService $service) {}

    public function index(Request $request, Assignment $assignment)
    {
        /** @var \Modules\Auth\Models\User $user */
        $user = auth('api')->user();

        $query = Submission::query()
            ->where('assignment_id', $assignment->id)
            ->with(['user:id,name,email', 'enrollment:id,status', 'files', 'grade']);

        // Students can only see their own submissions
        if ($user->hasRole('student')) {
            $query->where('user_id', $user->id);
        }

        $submissions = $query->orderBy('created_at', 'desc')->get();

        return $this->success(['submissions' => $submissions]);
    }

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

    public function show(Submission $submission)
    {
        /** @var \Modules\Auth\Models\User $user */
        $user = auth('api')->user();

        // Students can only see their own submissions
        if ($user->hasRole('student') && $submission->user_id !== $user->id) {
            return $this->error('Anda tidak memiliki akses untuk melihat submission ini.', 403);
        }

        $submission->load(['assignment', 'user:id,name,email', 'enrollment', 'files', 'previousSubmission', 'grade']);

        return $this->success(['submission' => $submission]);
    }

    public function update(Request $request, Submission $submission)
    {
        /** @var \Modules\Auth\Models\User $user */
        $user = auth('api')->user();

        // Students can only update their own draft submissions
        if ($user->hasRole('student')) {
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

    public function grade(Request $request, Submission $submission)
    {
        /** @var \Modules\Auth\Models\User $user */
        $user = auth('api')->user();

        // Only admin/instructor can grade
        if (! $user->hasRole('admin') && ! $user->hasRole('instructor') && ! $user->hasRole('superadmin')) {
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

