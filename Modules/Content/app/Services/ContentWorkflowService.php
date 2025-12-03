<?php

namespace Modules\Content\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Modules\Auth\Models\User;
use Modules\Content\Contracts\ContentWorkflowServiceInterface;
use Modules\Content\Enums\ContentStatus;
use Modules\Content\Events\ContentApproved;
use Modules\Content\Events\ContentPublished;
use Modules\Content\Events\ContentRejected;
use Modules\Content\Events\ContentScheduled;
use Modules\Content\Events\ContentSubmitted;
use Modules\Content\Exceptions\InvalidTransitionException;
use Modules\Content\Models\ContentWorkflowHistory;

class ContentWorkflowService implements ContentWorkflowServiceInterface
{
    // Workflow states
    const STATE_DRAFT = 'draft';

    const STATE_SUBMITTED = 'submitted';

    const STATE_IN_REVIEW = 'in_review';

    const STATE_APPROVED = 'approved';

    const STATE_REJECTED = 'rejected';

    const STATE_SCHEDULED = 'scheduled';

    const STATE_PUBLISHED = 'published';

    const STATE_ARCHIVED = 'archived';

    // Allowed transitions
    protected array $transitions = [
        self::STATE_DRAFT => [self::STATE_SUBMITTED],
        self::STATE_SUBMITTED => [self::STATE_IN_REVIEW, self::STATE_DRAFT],
        self::STATE_IN_REVIEW => [self::STATE_APPROVED, self::STATE_REJECTED, self::STATE_DRAFT],
        self::STATE_APPROVED => [self::STATE_SCHEDULED, self::STATE_PUBLISHED, self::STATE_DRAFT],
        self::STATE_REJECTED => [self::STATE_DRAFT],
        self::STATE_SCHEDULED => [self::STATE_PUBLISHED, self::STATE_DRAFT],
        self::STATE_PUBLISHED => [self::STATE_ARCHIVED, self::STATE_DRAFT],
        self::STATE_ARCHIVED => [self::STATE_DRAFT],
    ];

    /**
     * Transition content to new state.
     *
     * @throws InvalidTransitionException
     */
    public function transition(Model $content, string $newState, User $user, ?string $note = null): bool
    {
        // Get the current status as string for comparison
        $currentStatus = $content->status instanceof ContentStatus
            ? $content->status->value
            : $content->status;

        if (! $this->canTransition($currentStatus, $newState)) {
            throw new InvalidTransitionException(
                "Cannot transition from {$currentStatus} to {$newState}"
            );
        }

        return DB::transaction(function () use ($content, $newState, $user, $note, $currentStatus) {
            // Save workflow history
            ContentWorkflowHistory::create([
                'content_type' => get_class($content),
                'content_id' => $content->id,
                'from_state' => $currentStatus,
                'to_state' => $newState,
                'user_id' => $user->id,
                'note' => $note,
            ]);

            // Update content status
            $content->update(['status' => $newState]);

            // Fire appropriate event
            $this->fireTransitionEvent($content, $newState, $user);

            return true;
        });
    }

    /**
     * Check if transition is valid.
     */
    public function canTransition(string|ContentStatus $currentState, string $newState): bool
    {
        // Handle enum values by extracting the string value
        $currentStateString = $currentState instanceof ContentStatus
            ? $currentState->value
            : $currentState;

        return in_array($newState, $this->transitions[$currentStateString] ?? []);
    }

    /**
     * Get allowed transitions for current state.
     */
    public function getAllowedTransitions(string $currentState): array
    {
        return $this->transitions[$currentState] ?? [];
    }

    /**
     * Fire appropriate event for transition.
     */
    /**
     * Submit content for review.
     *
     * @throws InvalidTransitionException
     */
    public function submitForReview(Model $content, User $user): bool
    {
        return $this->transition($content, self::STATE_SUBMITTED, $user);
    }

    /**
     * Approve content.
     *
     * @throws InvalidTransitionException
     */
    public function approve(Model $content, User $user, ?string $note = null): bool
    {
        return $this->transition($content, self::STATE_APPROVED, $user, $note);
    }

    /**
     * Reject content with reason.
     *
     * @throws InvalidTransitionException
     */
    public function reject(Model $content, User $user, string $reason): bool
    {
        return $this->transition($content, self::STATE_REJECTED, $user, $reason);
    }

    /**
     * Schedule content for future publication.
     *
     * @throws InvalidTransitionException
     */
    public function schedule(Model $content, User $user, \DateTime $publishDate): bool
    {
        $content->update(['scheduled_at' => $publishDate]);

        return $this->transition($content, self::STATE_SCHEDULED, $user);
    }

    /**
     * Publish content.
     *
     * @throws InvalidTransitionException
     */
    public function publish(Model $content, User $user): bool
    {
        $content->update(['published_at' => now()]);

        return $this->transition($content, self::STATE_PUBLISHED, $user);
    }

    protected function fireTransitionEvent(Model $content, string $newState, User $user): void
    {
        switch ($newState) {
            case self::STATE_SUBMITTED:
                event(new ContentSubmitted($content, $user));
                break;
            case self::STATE_APPROVED:
                event(new ContentApproved($content, $user));
                break;
            case self::STATE_REJECTED:
                event(new ContentRejected($content, $user));
                break;
            case self::STATE_SCHEDULED:
                event(new ContentScheduled($content));
                break;
            case self::STATE_PUBLISHED:
                event(new ContentPublished($content));
                break;
        }
    }
}
