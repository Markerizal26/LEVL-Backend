<?php

declare(strict_types=1);

namespace Modules\Notifications\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Learning\Models\Submission;

/**
 * Notification sent to students when their grade has been recalculated due to answer key changes.
 *
 * @see Requirements 15.5: THE System SHALL notify affected students of grade changes
 * @see Requirements 21.6: THE System SHALL support email and in-app notification channels
 */
class GradeRecalculatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public readonly Submission $submission,
        public readonly float $oldScore,
        public readonly float $newScore
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
        $scoreChange = $this->newScore - $this->oldScore;
        $changeDirection = $scoreChange > 0 ? 'increased' : 'decreased';
        $changeAmount = abs($scoreChange);

        return (new MailMessage)
            ->subject("Your grade for \"{$assignmentTitle}\" has been updated")
            ->greeting("Hello {$notifiable->name}!")
            ->line("Your grade for \"{$assignmentTitle}\" has been recalculated due to answer key updates.")
            ->line("Previous score: {$this->oldScore}/100")
            ->line("New score: {$this->newScore}/100")
            ->line("Your score has {$changeDirection} by {$changeAmount} points.")
            ->action('View Submission', $this->getSubmissionUrl())
            ->line('If you have any questions about this change, please contact your instructor.');
    }

    /**
     * Get the array representation of the notification for database storage.
     *
     * @param  mixed  $notifiable
     */
    public function toArray($notifiable): array
    {
        $scoreChange = $this->newScore - $this->oldScore;

        return [
            'type' => 'grade_recalculated',
            'submission_id' => $this->submission->id,
            'assignment_id' => $this->submission->assignment_id,
            'assignment_title' => $this->submission->assignment?->title,
            'old_score' => $this->oldScore,
            'new_score' => $this->newScore,
            'score_change' => $scoreChange,
            'message' => "Your grade for \"{$this->submission->assignment?->title}\" has been updated from {$this->oldScore} to {$this->newScore}.",
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
