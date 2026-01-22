<?php

declare(strict_types=1);

namespace Modules\Grading\Http\Controllers;

use App\Support\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Modules\Grading\Contracts\Services\AppealServiceInterface;
use Modules\Grading\Http\Requests\DenyAppealRequest;
use Modules\Grading\Http\Requests\SubmitAppealRequest;
use Modules\Grading\Http\Resources\AppealResource;
use Modules\Grading\Models\Appeal;
use Modules\Learning\Models\Submission;

/**
 * Controller for late submission appeals.
 *
 * Handles:
 * - Appeal submission by students (Requirement 17.1, 17.2)
 * - Appeal approval by instructors (Requirement 17.4)
 * - Appeal denial by instructors (Requirement 17.5)
 * - Pending appeals listing for instructors (Requirement 17.3)
 *
 * @tags Appeals
 */
class AppealController extends Controller
{
    use ApiResponse;
    use AuthorizesRequests;

    public function __construct(
        private readonly AppealServiceInterface $appealService
    ) {}

    // =========================================================================
    // Appeal Submission (Requirements 17.1, 17.2)
    // =========================================================================

    /**
     * Submit an appeal for a late submission.
     *
     * POST /submissions/{submission}/appeals
     *
     * Allows students to submit an appeal for a submission that was
     * rejected due to lateness. Requires a reason and optionally
     * accepts supporting documents.
     *
     * Requirements: 17.1, 17.2
     *
     * @authenticated
     */
    public function submit(SubmitAppealRequest $request, Submission $submission): JsonResponse
    {
        $user = auth('api')->user();

        // Only the student who owns the submission can submit an appeal
        if ($submission->user_id !== $user->id) {
            return $this->forbidden(__('messages.appeals.not_owner'));
        }

        /** @var array<string, mixed> $documents */
        $documents = [];

        try {
            $validated = $request->validated();

            // Handle file uploads if present
            if ($request->hasFile('documents')) {
                foreach ($request->file('documents') as $file) {
                    $path = $file->store('appeals/'.$submission->id, 'local');
                    $documents[] = [
                        'path' => $path,
                        'original_name' => $file->getClientOriginalName(),
                        'mime_type' => $file->getMimeType(),
                        'size' => $file->getSize(),
                    ];
                }
            }

            $appeal = $this->appealService->submitAppeal(
                $submission->id,
                $validated['reason'],
                $documents
            );

            $appeal->load(['submission.assignment', 'student']);

            return $this->created([
                'appeal' => AppealResource::make($appeal),
            ], __('messages.appeals.submitted'));
        } catch (InvalidArgumentException $e) {
            // Clean up uploaded files on error
            foreach ($documents as $doc) {
                if (isset($doc['path']) && is_string($doc['path'])) {
                    Storage::disk('local')->delete($doc['path']);
                }
            }

            return $this->error($e->getMessage(), [], 422);
        } catch (\Exception $e) {
            // Clean up uploaded files on error
            foreach ($documents as $doc) {
                if (isset($doc['path']) && is_string($doc['path'])) {
                    Storage::disk('local')->delete($doc['path']);
                }
            }

            return $this->error($e->getMessage(), [], 500);
        }
    }

    // =========================================================================
    // Appeal Approval (Requirements 17.4)
    // =========================================================================

    /**
     * Approve an appeal.
     *
     * POST /appeals/{appeal}/approve
     *
     * Allows instructors to approve a pending appeal, granting the
     * student permission to submit despite the deadline having passed.
     *
     * Requirements: 17.4
     *
     * @authenticated
     */
    public function approve(Appeal $appeal): JsonResponse
    {
        $user = auth('api')->user();

        // Only instructors and admins can approve appeals
        if (
            ! $user->hasRole('Admin') &&
            ! $user->hasRole('Instructor') &&
            ! $user->hasRole('Superadmin')
        ) {
            return $this->forbidden(__('messages.appeals.no_access'));
        }

        try {
            $this->appealService->approveAppeal($appeal->id, $user->id);

            $appeal->refresh();
            $appeal->load(['submission.assignment', 'student', 'reviewer']);

            return $this->success([
                'appeal' => AppealResource::make($appeal),
            ], __('messages.appeals.approved'));
        } catch (InvalidArgumentException $e) {
            return $this->error($e->getMessage(), [], 422);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), [], 500);
        }
    }

    // =========================================================================
    // Appeal Denial (Requirements 17.5)
    // =========================================================================

    /**
     * Deny an appeal.
     *
     * POST /appeals/{appeal}/deny
     *
     * Allows instructors to deny a pending appeal with a required
     * reason. The student will be notified of the denial with the
     * provided reason.
     *
     * Requirements: 17.5
     *
     * @authenticated
     */
    public function deny(DenyAppealRequest $request, Appeal $appeal): JsonResponse
    {
        $user = auth('api')->user();

        // Only instructors and admins can deny appeals
        if (
            ! $user->hasRole('Admin') &&
            ! $user->hasRole('Instructor') &&
            ! $user->hasRole('Superadmin')
        ) {
            return $this->forbidden(__('messages.appeals.no_access'));
        }

        try {
            $validated = $request->validated();

            $this->appealService->denyAppeal(
                $appeal->id,
                $user->id,
                $validated['reason']
            );

            $appeal->refresh();
            $appeal->load(['submission.assignment', 'student', 'reviewer']);

            return $this->success([
                'appeal' => AppealResource::make($appeal),
            ], __('messages.appeals.denied'));
        } catch (InvalidArgumentException $e) {
            return $this->error($e->getMessage(), [], 422);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), [], 500);
        }
    }

    // =========================================================================
    // Pending Appeals (Requirements 17.3)
    // =========================================================================

    /**
     * Get pending appeals for the authenticated instructor.
     *
     * GET /appeals/pending
     *
     * Returns all pending appeals for assignments created by the
     * authenticated instructor, ordered by submission date (oldest first).
     *
     * Requirements: 17.3
     *
     * @authenticated
     */
    public function pending(): JsonResponse
    {
        $user = auth('api')->user();

        // Only instructors and admins can view pending appeals
        if (
            ! $user->hasRole('Admin') &&
            ! $user->hasRole('Instructor') &&
            ! $user->hasRole('Superadmin')
        ) {
            return $this->forbidden(__('messages.appeals.no_access'));
        }

        $appeals = $this->appealService->getPendingAppeals($user->id);

        return $this->success([
            'appeals' => AppealResource::collection($appeals),
            'meta' => [
                'total' => $appeals->count(),
            ],
        ]);
    }

    // =========================================================================
    // Appeal Details
    // =========================================================================

    /**
     * Get details of a specific appeal.
     *
     * GET /appeals/{appeal}
     *
     * Returns the details of a specific appeal. Students can only
     * view their own appeals, while instructors can view appeals
     * for their assignments.
     *
     * @authenticated
     */
    public function show(Appeal $appeal): JsonResponse
    {
        $user = auth('api')->user();

        // Students can only view their own appeals
        $isOwner = $appeal->student_id === $user->id;
        $isInstructor = $user->hasRole('Admin') ||
            $user->hasRole('Instructor') ||
            $user->hasRole('Superadmin');

        if (! $isOwner && ! $isInstructor) {
            return $this->forbidden(__('messages.appeals.no_access'));
        }

        $appeal->load(['submission.assignment', 'student', 'reviewer']);

        return $this->success([
            'appeal' => AppealResource::make($appeal),
        ]);
    }
}
