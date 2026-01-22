<?php

declare(strict_types=1);

namespace Modules\Grading\Contracts\Services;

use Illuminate\Support\Collection;
use Modules\Grading\Models\Appeal;

/**
 * Interface for managing late submission appeals.
 *
 * Requirements: 17.1, 17.2, 17.3, 17.4, 17.5
 */
interface AppealServiceInterface
{
    /**
     * Submit an appeal for a late submission.
     *
     * Requirements: 17.1, 17.2
     *
     * @param  int  $submissionId  The ID of the rejected submission
     * @param  string  $reason  The reason for the appeal
     * @param  array<string, mixed>  $documents  Optional supporting documents
     * @return Appeal The created appeal
     *
     * @throws \InvalidArgumentException if reason is empty or submission is not eligible for appeal
     */
    public function submitAppeal(int $submissionId, string $reason, array $documents = []): Appeal;

    /**
     * Approve an appeal and grant deadline extension.
     *
     * Requirements: 17.3, 17.4
     *
     * @param  int  $appealId  The ID of the appeal to approve
     * @param  int  $instructorId  The ID of the instructor approving the appeal
     *
     * @throws \InvalidArgumentException if appeal is already decided
     */
    public function approveAppeal(int $appealId, int $instructorId): void;

    /**
     * Deny an appeal with a reason.
     *
     * Requirements: 17.5
     *
     * @param  int  $appealId  The ID of the appeal to deny
     * @param  int  $instructorId  The ID of the instructor denying the appeal
     * @param  string  $reason  The reason for denial
     *
     * @throws \InvalidArgumentException if appeal is already decided or reason is empty
     */
    public function denyAppeal(int $appealId, int $instructorId, string $reason): void;

    /**
     * Get pending appeals for an instructor.
     *
     * Requirements: 17.3
     *
     * @param  int  $instructorId  The ID of the instructor
     * @return Collection<int, Appeal> Collection of pending appeals
     */
    public function getPendingAppeals(int $instructorId): Collection;
}
