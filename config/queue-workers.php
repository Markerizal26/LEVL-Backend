<?php

/**
 * Queue Workers Configuration
 *
 * This file documents the recommended queue worker configuration for the
 * Assessment & Grading System. Use this configuration with Laravel Horizon
 * or Supervisor to manage queue workers.
 *
 * Requirements: 28.6 - THE System SHALL process updates in background jobs using Laravel queues
 *
 * Queue Priority (high to low):
 * 1. grading - Grade recalculation and bulk operations (user-facing, high priority)
 * 2. notifications - Notification delivery (medium priority)
 * 3. file-processing - File validation and storage (lower priority)
 * 4. default - General background tasks (lowest priority)
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Worker Configurations
    |--------------------------------------------------------------------------
    |
    | Define the worker configurations for each queue. These settings can be
    | used with Supervisor or Laravel Horizon to manage queue workers.
    |
    */

    'workers' => [

        /*
         * Grading Queue Worker
         *
         * Handles grade recalculation and bulk operations.
         * High priority for user-facing operations.
         *
         * Jobs:
         * - RecalculateGradesJob
         * - BulkReleaseGradesJob
         * - BulkApplyFeedbackJob
         */
        'grading' => [
            'queue' => 'grading',
            'processes' => env('QUEUE_GRADING_PROCESSES', 2),
            'tries' => 3,
            'timeout' => 300,
            'memory' => 256,
            'sleep' => 3,
            'max_jobs' => 1000,
            'max_time' => 3600,
        ],

        /*
         * Notifications Queue Worker
         *
         * Handles notification delivery for grading events.
         * Medium priority for timely notification delivery.
         *
         * Jobs:
         * - SendNotificationJob
         * - All notification classes implementing ShouldQueue
         */
        'notifications' => [
            'queue' => 'notifications',
            'processes' => env('QUEUE_NOTIFICATIONS_PROCESSES', 2),
            'tries' => 3,
            'timeout' => 60,
            'memory' => 128,
            'sleep' => 3,
            'max_jobs' => 1000,
            'max_time' => 3600,
        ],

        /*
         * File Processing Queue Worker
         *
         * Handles file validation and storage operations.
         * Lower priority as file processing can be deferred.
         *
         * Jobs:
         * - ProcessFileUploadJob
         */
        'file-processing' => [
            'queue' => 'file-processing',
            'processes' => env('QUEUE_FILE_PROCESSING_PROCESSES', 1),
            'tries' => 3,
            'timeout' => 120,
            'memory' => 512,
            'sleep' => 5,
            'max_jobs' => 500,
            'max_time' => 3600,
        ],

        /*
         * Default Queue Worker
         *
         * Handles general background tasks.
         * Lowest priority for non-critical operations.
         */
        'default' => [
            'queue' => 'default',
            'processes' => env('QUEUE_DEFAULT_PROCESSES', 1),
            'tries' => 3,
            'timeout' => 90,
            'memory' => 128,
            'sleep' => 3,
            'max_jobs' => 1000,
            'max_time' => 3600,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Supervisor Configuration Example
    |--------------------------------------------------------------------------
    |
    | Example Supervisor configuration for running queue workers.
    | Place this in /etc/supervisor/conf.d/laravel-worker.conf
    |
    | [program:laravel-grading-worker]
    | process_name=%(program_name)s_%(process_num)02d
    | command=php /path/to/artisan queue:work database --queue=grading --tries=3 --timeout=300 --memory=256
    | autostart=true
    | autorestart=true
    | stopasgroup=true
    | killasgroup=true
    | user=www-data
    | numprocs=2
    | redirect_stderr=true
    | stdout_logfile=/path/to/storage/logs/grading-worker.log
    | stopwaitsecs=3600
    |
    | [program:laravel-notifications-worker]
    | process_name=%(program_name)s_%(process_num)02d
    | command=php /path/to/artisan queue:work database --queue=notifications --tries=3 --timeout=60 --memory=128
    | autostart=true
    | autorestart=true
    | stopasgroup=true
    | killasgroup=true
    | user=www-data
    | numprocs=2
    | redirect_stderr=true
    | stdout_logfile=/path/to/storage/logs/notifications-worker.log
    | stopwaitsecs=3600
    |
    | [program:laravel-file-processing-worker]
    | process_name=%(program_name)s_%(process_num)02d
    | command=php /path/to/artisan queue:work database --queue=file-processing --tries=3 --timeout=120 --memory=512
    | autostart=true
    | autorestart=true
    | stopasgroup=true
    | killasgroup=true
    | user=www-data
    | numprocs=1
    | redirect_stderr=true
    | stdout_logfile=/path/to/storage/logs/file-processing-worker.log
    | stopwaitsecs=3600
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Combined Worker Command
    |--------------------------------------------------------------------------
    |
    | For development or single-worker setups, you can run a combined worker
    | that processes all queues with priority ordering:
    |
    | php artisan queue:work --queue=grading,notifications,file-processing,default
    |
    | This ensures high-priority queues (grading) are processed before
    | lower-priority queues (file-processing, default).
    |
    */

    'combined_queue_order' => 'grading,notifications,file-processing,default',

];
