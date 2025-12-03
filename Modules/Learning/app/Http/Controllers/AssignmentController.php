<?php

namespace Modules\Learning\Http\Controllers;

use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
use Modules\Learning\Enums\AssignmentStatus;
use Modules\Learning\Enums\SubmissionType;
use Modules\Learning\Models\Assignment;
use Modules\Learning\Services\AssignmentService;

class AssignmentController extends Controller
{
    use ApiResponse;

    public function __construct(private AssignmentService $service) {}

    public function index(Request $request, \Modules\Schemes\Models\Course $course, \Modules\Schemes\Models\Unit $unit, \Modules\Schemes\Models\Lesson $lesson)
    {
        $assignments = $this->service->listByLesson($lesson, $request->all());

        return $this->success(['assignments' => $assignments]);
    }

    public function store(Request $request, \Modules\Schemes\Models\Course $course, \Modules\Schemes\Models\Unit $unit, \Modules\Schemes\Models\Lesson $lesson)
    {
        /** @var \Modules\Auth\Models\User $user */
        $user = auth('api')->user();

        // Check if user can manage this course
        if (! $this->userCanManageCourse($user, $course)) {
            return $this->error('Anda tidak memiliki akses untuk membuat assignment di course ini.', 403);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'submission_type' => ['required', Rule::enum(SubmissionType::class)],
            'max_score' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'available_from' => ['nullable', 'date'],
            'deadline_at' => ['nullable', 'date', 'after_or_equal:available_from'],
            'status' => ['nullable', Rule::enum(AssignmentStatus::class)],
            'allow_resubmit' => ['nullable', 'boolean'],
            'late_penalty_percent' => ['nullable', 'integer', 'min:0', 'max:100'],
        ]);

        $validated['lesson_id'] = $lesson->id;

        $assignment = $this->service->create($validated, $user->id);

        return $this->created(['assignment' => $assignment], 'Assignment berhasil dibuat.');
    }

    public function show(Assignment $assignment)
    {
        $assignment->load(['creator:id,name,email', 'lesson:id,title,slug']);

        return $this->success(['assignment' => $assignment]);
    }

    public function update(Request $request, Assignment $assignment)
    {
        /** @var \Modules\Auth\Models\User $user */
        $user = auth('api')->user();

        // Load lesson and course relationship
        $assignment->loadMissing('lesson.unit.course');
        $course = $assignment->lesson?->unit?->course;

        if (! $course || ! $this->userCanManageCourse($user, $course)) {
            return $this->error('Anda tidak memiliki akses untuk mengubah assignment ini.', 403);
        }

        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'submission_type' => ['sometimes', Rule::enum(SubmissionType::class)],
            'max_score' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'available_from' => ['nullable', 'date'],
            'deadline_at' => ['nullable', 'date', 'after_or_equal:available_from'],
            'status' => ['sometimes', Rule::enum(AssignmentStatus::class)],
            'allow_resubmit' => ['nullable', 'boolean'],
            'late_penalty_percent' => ['nullable', 'integer', 'min:0', 'max:100'],
        ]);

        $updated = $this->service->update($assignment, $validated);

        return $this->success(['assignment' => $updated], 'Assignment berhasil diperbarui.');
    }

    public function destroy(Assignment $assignment)
    {
        /** @var \Modules\Auth\Models\User $user */
        $user = auth('api')->user();

        // Load lesson and course relationship
        $assignment->loadMissing('lesson.unit.course');
        $course = $assignment->lesson?->unit?->course;

        if (! $course || ! $this->userCanManageCourse($user, $course)) {
            return $this->error('Anda tidak memiliki akses untuk menghapus assignment ini.', 403);
        }

        $this->service->delete($assignment);

        return $this->success([], 'Assignment berhasil dihapus.');
    }

    public function publish(Assignment $assignment)
    {
        $updated = $this->service->publish($assignment);

        return $this->success(['assignment' => $updated], 'Assignment berhasil dipublish.');
    }

    public function unpublish(Assignment $assignment)
    {
        $updated = $this->service->unpublish($assignment);

        return $this->success(['assignment' => $updated], 'Assignment berhasil diunpublish.');
    }

    /**
     * Check if user can manage a course.
     */
    private function userCanManageCourse(\Modules\Auth\Models\User $user, \Modules\Schemes\Models\Course $course): bool
    {
        if ($user->hasRole('Superadmin')) {
            return true;
        }

        if ($user->hasRole('Admin') || $user->hasRole('Instructor')) {
            if ((int) $course->instructor_id === (int) $user->id) {
                return true;
            }

            if (method_exists($course, 'hasAdmin') && $course->hasAdmin($user)) {
                return true;
            }
        }

        return false;
    }
}
