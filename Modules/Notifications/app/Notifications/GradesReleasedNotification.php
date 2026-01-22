<?php

declare(strict_types=1);

namespace Modules\Notifications\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Learning\Models\Assignment;
use Modules\Learning\Models\Submission;

/**
 * Notification sent to students when grades are released in deferred mode.
 *
 * @see Requirements 21.2: WHEN grades are released in deferred mode, THE System SHALL notify affected students
 * @see Requirements 21.6: THE System SHALL support email and in-app notification channels
 */
class GradesReleasedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public readonly Assignment $assignment,
        public readonly ?Submission $submission = null,
        public readonly ?float $score = null
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
        $assignmentTitle = $this->assignment->title ?? 'Assignment';

        $message = (new MailMessage)
            ->subject("Grades released for \"{$assignmentTitle}\"")
            ->greeting("Hello {$notifiable->name}!")
            ->line("Grades have been released for \"{$assignmentTitle}\".");

        if ($this->score !== null) {
            $message->line("Your score: {$this->score}/100");
        }

        return $message
            ->line('You can now view your detailed feedback and answers.')
            ->action('View Results', $this->getResultsUrl())
            ->line('Thank you for your participation!');
    }

    /**
     * Get the array representation of the notification for database storage.
     *
     * @param  mixed  $notifiable
     */
    public function toArray($notifiable): array
    {
        return [
            'type' => 'grades_released',
            'assignment_id' => $this->assignment->id,
            'assignment_title' => $this->assignment->title,
            'submission_id' => $this->submission?->id,
            'score' => $this->score,
            'message' => "Grades have been released for \"{$this->assignment->title}\". You can now view your detailed feedback.",
        ];
    }

    /**
     * Get the URL to view the results.
     */
    protected function getResultsUrl(): string
    {
        if ($this->submission) {
            return config('app.url')."/submissions/{$this->submission->id}";
        }

        return config('app.url')."/assignments/{$this->assignment->id}";
    }
}
