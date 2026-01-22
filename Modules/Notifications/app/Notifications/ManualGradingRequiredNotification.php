<?php

declare(strict_types=1);

namespace Modules\Notifications\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Learning\Models\Submission;

/**
 * Notification sent to instructors when a submission requires manual grading.
 *
 * @see Requirements 21.3: WHEN a submission requires manual grading, THE System SHALL notify assigned instructors
 * @see Requirements 21.6: THE System SHALL support email and in-app notification channels
 */
class ManualGradingRequiredNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public readonly Submission $submission,
        public readonly int $questionsRequiringGrading = 0
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
        $studentName = $this->submission->student?->name ?? 'A student';

        $message = (new MailMessage)
            ->subject("Manual grading required for \"{$assignmentTitle}\"")
            ->greeting("Hello {$notifiable->name}!")
            ->line("{$studentName} has submitted \"{$assignmentTitle}\" and it requires manual grading.");

        if ($this->questionsRequiringGrading > 0) {
            $message->line("Number of questions requiring grading: {$this->questionsRequiringGrading}");
        }

        return $message
            ->action('Grade Submission', $this->getGradingUrl())
            ->line('Please review and grade the submission at your earliest convenience.');
    }

    /**
     * Get the array representation of the notification for database storage.
     *
     * @param  mixed  $notifiable
     */
    public function toArray($notifiable): array
    {
        return [
            'type' => 'manual_grading_required',
            'submission_id' => $this->submission->id,
            'assignment_id' => $this->submission->assignment_id,
            'assignment_title' => $this->submission->assignment?->title,
            'student_id' => $this->submission->student_id,
            'student_name' => $this->submission->student?->name,
            'questions_requiring_grading' => $this->questionsRequiringGrading,
            'submitted_at' => $this->submission->submitted_at?->toIso8601String(),
            'message' => "A submission for \"{$this->submission->assignment?->title}\" requires manual grading.",
        ];
    }

    /**
     * Get the URL to grade the submission.
     */
    protected function getGradingUrl(): string
    {
        return config('app.url')."/grading/submissions/{$this->submission->id}";
    }
}
