<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | File Retention Settings
    |--------------------------------------------------------------------------
    |
    | Configure how long uploaded files should be retained before being
    | marked for deletion. This helps manage storage costs and comply
    | with data retention regulations.
    |
    */

    'file_retention' => [
        /*
        |--------------------------------------------------------------------------
        | Retention Period (Days)
        |--------------------------------------------------------------------------
        |
        | The number of days to retain uploaded files after submission.
        | After this period, files will be marked as expired and can be
        | cleaned up using the grading:cleanup-expired-files command.
        | Set to null for unlimited retention.
        |
        */
        'retention_days' => env('GRADING_FILE_RETENTION_DAYS', 365),

        /*
        |--------------------------------------------------------------------------
        | Batch Size
        |--------------------------------------------------------------------------
        |
        | The number of files to process in each batch during cleanup.
        | This helps prevent memory issues when processing large numbers
        | of expired files.
        |
        */
        'cleanup_batch_size' => env('GRADING_FILE_CLEANUP_BATCH_SIZE', 100),
    ],
];
