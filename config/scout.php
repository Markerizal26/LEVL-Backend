<?php

$defaultTypoTolerance = [
    'enabled' => true,
    'minWordSizeForTypos' => ['oneTypo' => 6, 'twoTypos' => 10],
];

$strictTypoTolerance = [
    'enabled' => false,
];

return [

    'driver' => env('SCOUT_DRIVER', 'collection'),

    'prefix' => env('SCOUT_PREFIX', ''),

    'queue' => env('SCOUT_QUEUE', false),

    'after_commit' => true,

    'chunk' => [
        'searchable' => 500,
        'unsearchable' => 500,
    ],

    'soft_delete' => false,

    'identify' => env('SCOUT_IDENTIFY', false),

    'meilisearch' => [
        'host' => env('MEILISEARCH_HOST', 'http://localhost:7700'),
        'key' => env('MEILISEARCH_KEY'),
        'index-settings' => [
            'courses_index' => [
                'filterableAttributes' => [
                    'category_id',
                    'level_tag',
                    'instructor_id',
                    'status',
                    'type',
                    'duration_estimate',
                ],
                'sortableAttributes' => [
                    'published_at',
                    'duration_estimate',
                    'title',
                ],
                'searchableAttributes' => [
                    'title',
                    'short_desc',
                    'code',
                    'category_name',
                    'instructor_name',
                    'tags',
                ],
                'typoTolerance' => $defaultTypoTolerance,
            ],
            'categories_index' => [
                'filterableAttributes' => [
                    'status',
                ],
                'sortableAttributes' => [
                    'name',
                    'created_at',
                ],
                'searchableAttributes' => [
                    'name',
                    'value',
                    'description',
                ],
                'typoTolerance' => $defaultTypoTolerance,
            ],
            'tags_index' => [
                'filterableAttributes' => [],
                'sortableAttributes' => [
                    'name',
                    'created_at',
                ],
                'searchableAttributes' => [
                    'name',
                    'slug',
                    'description',
                ],
                'typoTolerance' => $defaultTypoTolerance,
            ],
            'users_index' => [
                'filterableAttributes' => [
                    'status',
                    'account_status',
                ],
                'sortableAttributes' => [
                    'name',
                    'created_at',
                ],
                'searchableAttributes' => [
                    'name',
                    'email',
                    'username',
                ],
                'typoTolerance' => $defaultTypoTolerance,
            ],
            'units_index' => [
                'filterableAttributes' => [
                    'course_id',
                    'status',
                ],
                'sortableAttributes' => [
                    'title',
                    'order',
                ],
                'searchableAttributes' => [
                    'title',
                    'description',
                    'code',
                    'course_title',
                ],
                'typoTolerance' => $defaultTypoTolerance,
            ],
            'lessons_index' => [
                'filterableAttributes' => [
                    'unit_id',
                    'course_id',
                    'status',
                    'content_type',
                ],
                'sortableAttributes' => [
                    'title',
                    'order',
                ],
                'searchableAttributes' => [
                    'title',
                    'description',
                    'markdown_content',
                    'unit_title',
                    'course_title',
                ],
                'typoTolerance' => $defaultTypoTolerance,
            ],
            'master_data_index' => [
                'filterableAttributes' => [
                    'type',
                    'is_active',
                    'is_system',
                ],
                'sortableAttributes' => [
                    'value',
                    'label',
                    'sort_order',
                    'created_at',
                ],
                'searchableAttributes' => [
                    'type',
                    'value',
                    'label',
                ],
                'typoTolerance' => $defaultTypoTolerance,
            ],
            'submissions_index' => [
                'filterableAttributes' => [
                    'assignment_id',
                    'user_id',
                    'enrollment_id',
                    'state',
                    'score',
                    'submitted_at',
                    'attempt_number',
                    'is_late',
                    'created_at',
                ],
                'sortableAttributes' => [
                    'submitted_at',
                    'score',
                    'created_at',
                    'attempt_number',
                    'student_name',
                    'assignment_title',
                ],
                'searchableAttributes' => [
                    'student_name',
                    'student_email',
                    'assignment_title',
                    'state',
                ],
                'typoTolerance' => $defaultTypoTolerance,
            ],
            'enrollments_index' => [
                'filterableAttributes' => [
                    'status',
                    'user_id',
                    'course_id',
                    'enrolled_at',
                    'completed_at',
                    'created_at',
                ],
                'sortableAttributes' => [
                    'enrolled_at',
                    'completed_at',
                    'created_at',
                ],
                'searchableAttributes' => [
                    'user_name',
                    'user_email',
                    'course_title',
                    'course_code',
                ],
                'typoTolerance' => $defaultTypoTolerance,
            ],
            'threads_index' => [
                'filterableAttributes' => ['course_id', 'author_id', 'is_pinned', 'is_closed', 'is_resolved'],
                'sortableAttributes' => ['created_at', 'last_activity_at', 'views_count', 'replies_count'],
                'searchableAttributes' => ['title', 'content', 'author_name'],
                'typoTolerance' => $defaultTypoTolerance,
            ],
            'replies_index' => [
                'filterableAttributes' => ['thread_id', 'author_id', 'is_accepted_answer'],
                'sortableAttributes' => ['created_at'],
                'searchableAttributes' => ['content', 'author_name'],
                'typoTolerance' => $defaultTypoTolerance,
            ],
            'badges_index' => [
                'filterableAttributes' => ['type', 'is_active'],
                'sortableAttributes' => ['name', 'points', 'created_at'],
                'searchableAttributes' => ['name', 'description'],
                'typoTolerance' => $defaultTypoTolerance,
            ],
            'challenges_index' => [
                'filterableAttributes' => ['type', 'badge_id', 'start_at', 'end_at'],
                'sortableAttributes' => ['title', 'points_reward', 'created_at'],
                'searchableAttributes' => ['title', 'description'],
                'typoTolerance' => $defaultTypoTolerance,
            ],
            'grades_index' => [
                'filterableAttributes' => ['source_type', 'source_id', 'user_id', 'graded_by', 'status', 'is_override', 'is_draft'],
                'sortableAttributes' => ['score', 'graded_at', 'released_at'],
                'searchableAttributes' => ['feedback'],
                'typoTolerance' => $defaultTypoTolerance,
            ],
            'assignments_index' => [
                'filterableAttributes' => ['lesson_id', 'assignable_type', 'assignable_id', 'created_by', 'type', 'submission_type', 'status'],
                'sortableAttributes' => ['title', 'max_score', 'available_from', 'deadline_at', 'created_at'],
                'searchableAttributes' => ['title', 'description'],
                'typoTolerance' => $defaultTypoTolerance,
            ],
            'questions_index' => [
                'filterableAttributes' => ['assignment_id', 'type', 'difficulty'],
                'sortableAttributes' => ['order', 'points'],
                'searchableAttributes' => ['question_text', 'explanation'],
                'typoTolerance' => $defaultTypoTolerance,
            ],
            'audit_logs_index' => [
                'filterableAttributes' => ['action', 'actor_type', 'actor_id', 'subject_type', 'subject_id', 'user_id'],
                'sortableAttributes' => ['created_at'],
                'searchableAttributes' => ['action', 'event'],
                'typoTolerance' => $strictTypoTolerance,
            ],
            'level_configs_index' => [
                'filterableAttributes' => ['is_active'],
                'sortableAttributes' => ['level', 'min_points'],
                'searchableAttributes' => ['name', 'description'],
                'typoTolerance' => $defaultTypoTolerance,
            ],
        ],
    ],

];