<?php

namespace Modules\Content\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\Auth\Enums\UserStatus;
use Modules\Auth\Models\User;
use Modules\Content\Events\ContentSubmitted;

class NotifyReviewersOnContentSubmitted
{
    /**
     * Handle the event.
     */
    public function handle(ContentSubmitted $event): void
    {
        // Get all users with reviewer/admin role
        $reviewers = $this->getReviewers();

        if ($reviewers->isEmpty()) {
            Log::info('No reviewers found to notify for content submission');

            return;
        }

        $contentType = class_basename($event->content);
        $contentTitle = $event->content->title ?? 'Untitled';

        // Send notification to reviewers
        foreach ($reviewers as $reviewer) {
            // In a real implementation, this would use the Notifications module
            // Notification::send($reviewer, new ContentSubmittedNotification($event->content, $event->user));

            Log::info("Notifying reviewer {$reviewer->name} about {$contentType} submission: {$contentTitle}");
        }
    }

    /**
     * Get users with reviewer permissions.
     */
    private function getReviewers()
    {
        return User::whereHas('roles', function ($query) {
            $query->whereIn('name', ['admin', 'reviewer', 'instructor']);
        })
            ->where('status', UserStatus::Active->value)
            ->get();
    }
}
