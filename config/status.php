<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Status Enums Registry
    |--------------------------------------------------------------------------
    |
    | Central registry for all status enums used across the application.
    | This ensures consistency and makes it easier to maintain and test.
    |
    */

    'enrollment' => [
        'pending' => 'pending',
        'active' => 'active',
        'completed' => 'completed',
        'cancelled' => 'cancelled',
    ],

    'progress' => [
        'not_started' => 'not_started',
        'in_progress' => 'in_progress',
        'completed' => 'completed',
    ],

    'course' => [
        'draft' => 'draft',
        'published' => 'published',
        'archived' => 'archived',
    ],

    'unit' => [
        'draft' => 'draft',
        'published' => 'published',
        'archived' => 'archived',
    ],

    'lesson' => [
        'draft' => 'draft',
        'published' => 'published',
        'archived' => 'archived',
    ],

    'assignment' => [
        'draft' => 'draft',
        'published' => 'published',
        'archived' => 'archived',
    ],

    'submission' => [
        'draft' => 'draft',
        'submitted' => 'submitted',
        'graded' => 'graded',
        'late' => 'late',
    ],

    'grade' => [
        'pending' => 'pending',
        'graded' => 'graded',
        'reviewed' => 'reviewed',
    ],

    'user' => [
        'pending' => 'pending',
        'active' => 'active',
        'inactive' => 'inactive',
        'banned' => 'banned',
    ],

    'category' => [
        'active' => 'active',
        'inactive' => 'inactive',
    ],

    'notification' => [
        'pending' => 'pending',
        'sent' => 'sent',
        'read' => 'read',
    ],

    /*
    |--------------------------------------------------------------------------
    | Helper Functions
    |--------------------------------------------------------------------------
    |
    | Use these helper functions to get status values:
    | config('status.enrollment.active')
    | config('status.progress.completed')
    |
    */
];

