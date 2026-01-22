<?php

declare(strict_types=1);

namespace Modules\Notifications\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Grading\Models\Appeal;

/**
 * Notification sent to instructors when a student submits an appeal.
 *
 * @see Requirements 21.4: WHEN an appeal is submitted, THE System SHALL notify instructors
 * @see Requirements 21.6: THE System SHALL support email and in-app notification channels
 */
class AppealSubmittedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public readonly Appeal $appeal
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
        $studentName = $this->appeal->student?->name ?? 'A student';
        $assignmentTitle = $this->appeal->submission?->assignment?->title ?? 'Assignment';

        return (new MailMessage)
            ->subject("New appeal submitted for \"{$assignmentTitle}\"")
            ->greeting("Hello {$notifiable->name}!")
            ->line("{$studentName} has submitted an appeal for \"{$assignmentTitle}\".")
            ->line("Reason: {$this->appeal->reason}")
            ->action('Review Appeal', $this->getAppealUrl())
            ->line('Please review the appeal and make a decision.');
    }

    /**
     * Get the array representation of the notification for database storage.
     *
     * @param  mixed  $notifiable
     */
    public function toArray($notifiable): array
    {
        return [
            'type' => 'appeal_submitted',
            'appeal_id' => $this->appeal->id,
            'submission_id' => $this->appeal->submission_id,
            'assignment_id' => $this->appeal->submission?->assignment_id,
            'assignment_title' => $this->appeal->submission?->assignment?->title,
            'student_id' => $this->appeal->student_id,
            'student_name' => $this->appeal->student?->name,
            'reason' => $this->appeal->reason,
            'submitted_at' => $this->appeal->submitted_at?->toIso8601String(),
            'message' => "{$this->appeal->student?->name} has submitted an appeal for \"{$this->appeal->submission?->assignment?->title}\".",
        ];
    }

    /**
     * Get the URL to review the appeal.
     */
    protected function getAppealUrl(): string
    {
        return config('app.url')."/appeals/{$this->appeal->id}";
    }
}
