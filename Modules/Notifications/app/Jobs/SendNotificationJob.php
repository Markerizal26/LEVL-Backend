<?php

declare(strict_types=1);

namespace Modules\Notifications\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Modules\Auth\Models\User;

/**
 * Job to send notifications in the background.
 *
 * This job handles sending notifications asynchronously through Laravel's
 * queue system, ensuring that notification delivery doesn't block
 * user-facing operations.
 *
 * Requirements: 21.6 - THE System SHALL support email and in-app notification channels
 * Requirements: 28.6 - THE System SHALL process updates in background jobs using Laravel queues
 */
class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 60;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 10;

    /**
     * Create a new job instance.
     *
     * @param  array<int>  $userIds  Array of user IDs to notify
     * @param  string  $notificationClass  The fully qualified notification class name
     * @param  array<string, mixed>  $notificationData  Data to pass to the notification constructor
     */
    public function __construct(
        public array $userIds,
        public string $notificationClass,
        public array $notificationData = []
    ) {
        $this->onQueue('notifications');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if (empty($this->userIds)) {
            Log::info('SendNotificationJob: No user IDs provided, skipping');

            return;
        }

        if (! class_exists($this->notificationClass)) {
            Log::error('SendNotificationJob: Notification class does not exist', [
                'notification_class' => $this->notificationClass,
            ]);

            return;
        }

        Log::info('SendNotificationJob: Starting notification dispatch', [
            'user_count' => count($this->userIds),
            'notification_class' => $this->notificationClass,
        ]);

        try {
            $users = User::whereIn('id', $this->userIds)->get();

            if ($users->isEmpty()) {
                Log::warning('SendNotificationJob: No users found for provided IDs', [
                    'user_ids' => $this->userIds,
                ]);

                return;
            }

            // Create the notification instance with the provided data
            $notification = new $this->notificationClass(...array_values($this->notificationData));

            // Send the notification to all users
            Notification::send($users, $notification);

            Log::info('SendNotificationJob: Completed notification dispatch', [
                'users_notified' => $users->count(),
                'notification_class' => $this->notificationClass,
            ]);
        } catch (\Throwable $e) {
            Log::error('SendNotificationJob: Failed to send notification', [
                'user_ids' => $this->userIds,
                'notification_class' => $this->notificationClass,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SendNotificationJob: Job failed after all retries', [
            'user_ids' => $this->userIds,
            'notification_class' => $this->notificationClass,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }

    /**
     * Get the tags that should be assigned to the job.
     *
     * @return array<string>
     */
    public function tags(): array
    {
        $className = class_basename($this->notificationClass);

        return [
            'send-notification',
            'notification:'.$className,
            'users:'.count($this->userIds),
        ];
    }
}
