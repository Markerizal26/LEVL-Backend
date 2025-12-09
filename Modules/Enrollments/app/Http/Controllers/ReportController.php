<?php

namespace Modules\Enrollments\Http\Controllers;

use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Enrollments\Models\CourseProgress;
use Modules\Enrollments\Models\Enrollment;
use Modules\Schemes\Models\Course;

/**
 * @tags Laporan & Statistik
 */
class ReportController extends Controller
{
    use ApiResponse;

    /**
     * Statistik Tingkat Penyelesaian Kursus
     *
     * Get completion statistics including total enrolled, active, completed, and completion percentage for a specific course.
     *
     *
     * @summary Statistik Tingkat Penyelesaian Kursus
     *
     * @response 200 scenario="Success" {"success":true,"message":"Success","data":{"id":1,"name":"Example Report"}}
     * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
     * @authenticated
     */
    public function courseCompletionRate(Request $request, Course $course)
    {
        /** @var \Modules\Auth\Models\User $user */
        $user = auth('api')->user();

        if (! $this->userCanManageCourse($user, $course)) {
            return $this->error('Anda tidak memiliki akses untuk melihat laporan course ini.', 403);
        }

        $stats = Enrollment::query()
            ->where('course_id', $course->id)
            ->selectRaw('
                COUNT(*) as total_enrolled,
                SUM(CASE WHEN status = "active" THEN 1 ELSE 0 END) as active_count,
                SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed_count,
                SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending_count,
                SUM(CASE WHEN status = "cancelled" THEN 1 ELSE 0 END) as cancelled_count
            ')
            ->first();

        $totalEnrolled = max(1, $stats->total_enrolled);
        $completionRate = round(($stats->completed_count / $totalEnrolled) * 100, 2);

        $avgProgress = CourseProgress::query()
            ->join('enrollments', 'course_progress.enrollment_id', '=', 'enrollments.id')
            ->where('enrollments.course_id', $course->id)
            ->whereIn('enrollments.status', ['active', 'completed'])
            ->avg('course_progress.progress_percent') ?? 0;

        return $this->success([
            'course' => [
                'id' => $course->id,
                'slug' => $course->slug,
                'title' => $course->title,
            ],
            'statistics' => [
                'total_enrolled' => (int) $stats->total_enrolled,
                'active_count' => (int) $stats->active_count,
                'completed_count' => (int) $stats->completed_count,
                'pending_count' => (int) $stats->pending_count,
                'cancelled_count' => (int) $stats->cancelled_count,
                'completion_rate' => $completionRate,
                'avg_progress_percent' => round($avgProgress, 2),
            ],
        ]);
    }

    /**
     * Statistik Funnel Pendaftaran
     *
     * Get funnel statistics showing enrollment journey from request to completion.
     *
     *
     * @summary Statistik Funnel Pendaftaran
     *
     * @response 200 scenario="Success" {"success":true,"message":"Success","data":{"id":1,"name":"Example Report"}}
     * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
     * @authenticated
     */
    public function enrollmentFunnel(Request $request)
    {
        /** @var \Modules\Auth\Models\User $user */
        $user = auth('api')->user();

        if (! $user->hasRole('Admin') && ! $user->hasRole('Instructor') && ! $user->hasRole('Superadmin')) {
            return $this->error('Anda tidak memiliki akses untuk melihat laporan ini.', 403);
        }

        $courseId = $request->query('course_id');

        $query = Enrollment::query();

        if ($courseId) {
            $query->where('course_id', $courseId);
        } elseif (! $user->hasRole('Superadmin')) {
            // Filter to managed courses only
            $courseIds = Course::query()
                ->where(function ($q) use ($user) {
                    $q->where('instructor_id', $user->id)
                        ->orWhereHas('admins', fn ($aq) => $aq->where('user_id', $user->id));
                })
                ->pluck('id');
            $query->whereIn('course_id', $courseIds);
        }

        $stats = $query->selectRaw('
            COUNT(*) as total_requests,
            SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = "active" THEN 1 ELSE 0 END) as active,
            SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN status = "cancelled" THEN 1 ELSE 0 END) as cancelled
        ')->first();

        $total = max(1, $stats->total_requests);

        return $this->success([
            'funnel' => [
                'total_requests' => (int) $stats->total_requests,
                'pending' => [
                    'count' => (int) $stats->pending,
                    'percentage' => round(($stats->pending / $total) * 100, 2),
                ],
                'active' => [
                    'count' => (int) $stats->active,
                    'percentage' => round(($stats->active / $total) * 100, 2),
                ],
                'completed' => [
                    'count' => (int) $stats->completed,
                    'percentage' => round(($stats->completed / $total) * 100, 2),
                ],
                'cancelled' => [
                    'count' => (int) $stats->cancelled,
                    'percentage' => round(($stats->cancelled / $total) * 100, 2),
                ],
            ],
        ]);
    }

    /**
     * Ekspor Data Pendaftaran CSV
     *
     * Export enrollment data for a course to CSV format.
     *
     *
     * @summary Ekspor Data Pendaftaran CSV
     *
     * @response 200 scenario="Success" {"success":true,"message":"Success","data":{"id":1,"name":"Example Report"}}
     * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
     * @authenticated
     */
    public function exportEnrollmentsCsv(Request $request, Course $course)
    {
        /** @var \Modules\Auth\Models\User $user */
        $user = auth('api')->user();

        if (! $this->userCanManageCourse($user, $course)) {
            return $this->error('Anda tidak memiliki akses untuk export data course ini.', 403);
        }

        $enrollments = Enrollment::query()
            ->where('course_id', $course->id)
            ->with(['user:id,name,email', 'courseProgress'])
            ->orderByDesc('created_at')
            ->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="enrollments-'.$course->slug.'-'.now()->format('Y-m-d').'.csv"',
        ];

        $callback = function () use ($enrollments) {
            $file = fopen('php://output', 'w');

            // CSV Headers
            fputcsv($file, ['ID', 'Student Name', 'Email', 'Status', 'Progress %', 'Enrolled At', 'Completed At']);

            foreach ($enrollments as $enrollment) {
                $progress = $enrollment->courseProgress;
                fputcsv($file, [
                    $enrollment->id,
                    $enrollment->user?->name ?? 'N/A',
                    $enrollment->user?->email ?? 'N/A',
                    $enrollment->status,
                    $progress?->progress_percent ?? 0,
                    $enrollment->enrolled_at?->toDateTimeString(),
                    $enrollment->completed_at?->toDateTimeString() ?? 'N/A',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function userCanManageCourse($user, Course $course): bool
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
