<?php

declare(strict_types=1);

namespace Modules\Notifications\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Learning\Models\Submission;

/**
 * Notification sent to students when their submission has been graded.
 *
 * @see Requirements 21.1: WHEN a submission is graded, THE System SHALL notify the student
 * @see Requirements 21.6: THE System SHALL support email and in-app notification channels
 */
class SubmissionGradedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public readonly Submission $submission,
        public readonly float $score,
        public readonly ?string $feedback = null
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
        $assignmentTitle = $this->submission->assignment?->title ?? 'Assignment';

        return (new MailMessage)
            ->subject("Your submission for \"{$assignmentTitle}\" has been graded")
            ->greeting("Hello {$notifiable->name}!")
            ->line("Your submission for \"{$assignmentTitle}\" has been graded.")
            ->line("Your score: {$this->score}/100")
            ->when($this->feedback, function (MailMessage $message) {
                return $message->line("Feedback: {$this->feedback}");
            })
            ->action('View Submission', $this->getSubmissionUrl())
            ->line('Thank you for your hard work!');
    }

    /**
     * Get the array representation of the notification for database storage.
     *
     * @param  mixed  $notifiable
     */
    public function toArray($notifiable): array
    {
        return [
            'type' => 'submission_graded',
            'submission_id' => $this->submission->id,
            'assignment_id' => $this->submission->assignment_id,
            'assignment_title' => $this->submission->assignment?->title,
            'score' => $this->score,
            'feedback' => $this->feedback,
            'message' => "Your submission for \"{$this->submission->assignment?->title}\" has been graded. Score: {$this->score}/100",
        ];
    }

    /**
     * Get the URL to view the submission.
     */
    protected function getSubmissionUrl(): string
    {
        return config('app.url')."/submissions/{$this->submission->id}";
    }
}
