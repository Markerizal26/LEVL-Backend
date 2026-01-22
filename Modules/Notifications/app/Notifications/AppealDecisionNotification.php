<?php

declare(strict_types=1);

namespace Modules\Notifications\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Grading\Models\Appeal;

/**
 * Notification sent to students when their appeal has been decided.
 *
 * @see Requirements 21.5: WHEN an appeal is decided, THE System SHALL notify the student
 * @see Requirements 21.6: THE System SHALL support email and in-app notification channels
 */
class AppealDecisionNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public readonly Appeal $appeal,
        public readonly string $decision,
        public readonly ?string $decisionReason = null
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     */
    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     */
    public function toMail($notifiable): MailMessage
    {
        $assignmentTitle = $this->appeal->submission?->assignment?->title ?? 'Assignment';
        $isApproved = $this->decision === 'approved';

        $message = (new MailMessage)
            ->subject("Your appeal for \"{$assignmentTitle}\" has been ".($isApproved ? 'approved' : 'denied'))
            ->greeting("Hello {$notifiable->name}!");

        if ($isApproved) {
            $message->line("Good news! Your appeal for \"{$assignmentTitle}\" has been approved.")
                ->line('You may now submit your assignment.');
        } else {
            $message->line("Your appeal for \"{$assignmentTitle}\" has been denied.");
        }

        if ($this->decisionReason) {
            $message->line("Reason: {$this->decisionReason}");
        }

        return $message
            ->action('View Appeal', $this->getAppealUrl())
            ->line('If you have any questions, please contact your instructor.');
    }

    /**
     * Get the array representation of the notification for database storage.
     *
     * @param  mixed  $notifiable
     */
    public function toArray($notifiable): array
    {
        $isApproved = $this->decision === 'approved';

        return [
            'type' => 'appeal_decision',
            'appeal_id' => $this->appeal->id,
            'submission_id' => $this->appeal->submission_id,
            'assignment_id' => $this->appeal->submission?->assignment_id,
            'assignment_title' => $this->appeal->submission?->assignment?->title,
            'decision' => $this->decision,
            'decision_reason' => $this->decisionReason,
            'decided_at' => $this->appeal->decided_at?->toIso8601String(),
            'message' => "Your appeal for \"{$this->appeal->submission?->assignment?->title}\" has been ".($isApproved ? 'approved' : 'denied').'.',
        ];
    }

    /**
     * Get the URL to view the appeal.
     */
    protected function getAppealUrl(): string
    {
        return config('app.url')."/appeals/{$this->appeal->id}";
    }
}
