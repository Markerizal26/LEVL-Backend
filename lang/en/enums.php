<?php

return [
    // Auth
    'user_status' => [
        'pending' => 'Pending',
        'active' => 'Active',
        'inactive' => 'Inactive',
        'banned' => 'Banned',
    ],

    'roles' => [
        'superadmin' => 'Super Admin',
        'admin' => 'Admin',
        'instructor' => 'Instructor',
        'student' => 'Student',
    ],

    // Schemes
    'course_status' => [
        'draft' => 'Draft',
        'published' => 'Published',
        'archived' => 'Archived',
    ],

    'course_type' => [
        'okupasi' => 'Occupation',
        'kluster' => 'Cluster',
    ],

    'enrollment_type' => [
        'auto_accept' => 'Auto Accept',
        'key_based' => 'Key Based',
        'approval' => 'Approval Required',
    ],

    'level_tag' => [
        'dasar' => 'Basic',
        'menengah' => 'Intermediate',
        'mahir' => 'Advanced',
    ],

    'progression_mode' => [
        'sequential' => 'Sequential',
        'free' => 'Free',
    ],

    'content_type' => [
        'markdown' => 'Markdown',
        'video' => 'Video',
        'link' => 'Link',
    ],

    // Enrollments
    'enrollment_status' => [
        'pending' => 'Pending',
        'active' => 'Active',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ],

    'progress_status' => [
        'not_started' => 'Not Started',
        'in_progress' => 'In Progress',
        'completed' => 'Completed',
    ],

    // Learning
    'assignment_status' => [
        'draft' => 'Draft',
        'published' => 'Published',
        'archived' => 'Archived',
    ],

    'submission_status' => [
        'draft' => 'Draft',
        'submitted' => 'Submitted',
        'graded' => 'Graded',
        'late' => 'Late',
        'missing' => 'Missing',
    ],

    'submission_type' => [
        'text' => 'Text',
        'file' => 'File',
        'mixed' => 'Mixed',
    ],

    'submission_state' => [
        'in_progress' => 'In Progress',
        'submitted' => 'Submitted',
        'auto_graded' => 'Auto Graded',
        'pending_manual_grading' => 'Pending Manual Grading',
        'graded' => 'Graded',
        'released' => 'Released',
    ],

    'override_type' => [
        'prerequisite' => 'Prerequisite',
        'deadline' => 'Deadline',
        'attempts' => 'Attempts',
    ],

    'override_type_desc' => [
        'prerequisite' => 'Allows student to bypass prerequisite requirements',
        'deadline' => 'Extends the assignment deadline for the student',
        'attempts' => 'Grants additional attempts beyond the maximum limit',
    ],

    'randomization_type' => [
        'static' => 'Static',
        'random_order' => 'Random Order',
        'bank' => 'Question Bank',
    ],

    'review_mode' => [
        'immediate' => 'Immediate',
        'deferred' => 'Deferred',
        'hidden' => 'Hidden',
    ],

    // Content
    'content_status' => [
        'draft' => 'Draft',
        'submitted' => 'Submitted',
        'in_review' => 'In Review',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'scheduled' => 'Scheduled',
        'published' => 'Published',
        'archived' => 'Archived',
    ],

    'priority' => [
        'low' => 'Low',
        'medium' => 'Medium',
        'high' => 'High',
    ],

    'target_type' => [
        'all' => 'All',
        'role' => 'By Role',
        'user' => 'Specific User',
    ],

    // Gamification
    'challenge_type' => [
        'daily' => 'Daily',
        'weekly' => 'Weekly',
        'special' => 'Special',
    ],

    'challenge_assignment_status' => [
        'pending' => 'Pending',
        'in_progress' => 'In Progress',
        'completed' => 'Completed',
        'claimed' => 'Claimed',
        'expired' => 'Expired',
    ],

    'badge_type' => [
        'achievement' => 'Achievement',
        'milestone' => 'Milestone',
        'completion' => 'Completion',
    ],

    'point_source_type' => [
        'lesson' => 'Lesson',
        'assignment' => 'Assignment',
        'attempt' => 'Attempt',
        'challenge' => 'Challenge',
        'system' => 'System',
        'grade' => 'Grade Achievement',
    ],

    'challenge_criteria_type' => [
        'lessons_completed' => 'Complete Lessons',
        'assignments_submitted' => 'Submit Assignments',
        'exercises_completed' => 'Complete Exercises',
        'xp_earned' => 'Earn XP',
        'streak_days' => 'Maintain Streak',
        'courses_completed' => 'Complete Courses',
    ],

    'setting_type' => [
        'string' => 'String',
        'number' => 'Number',
        'boolean' => 'Boolean',
        'json' => 'JSON',
    ],

    'point_reason' => [
        'completion' => 'Completion',
        'score' => 'Score',
        'bonus' => 'Bonus',
        'penalty' => 'Penalty',
    ],

    // Notifications
    'notification_type' => [
        'system' => 'System',
        'assignment' => 'Assignment',
        'assessment' => 'Assessment',
        'grading' => 'Grading',
        'gamification' => 'Gamification',
        'news' => 'News',
        'custom' => 'Custom',
        'course_completed' => 'Course Completed',
        'enrollment' => 'Enrollment',
        'forum_reply_to_thread' => 'Forum Thread Reply',
        'forum_reply_to_reply' => 'Forum Reply Reply',
    ],

    'notification_channel' => [
        'in_app' => 'In App',
        'email' => 'Email',
        'push' => 'Push Notification',
    ],

    'notification_frequency' => [
        'immediate' => 'Immediate',
        'daily' => 'Daily',
        'weekly' => 'Weekly',
    ],

    // Grading
    'grade_status' => [
        'pending' => 'Pending',
        'graded' => 'Graded',
        'reviewed' => 'Reviewed',
    ],

    'source_type' => [
        'assignment' => 'Assignment',
        'attempt' => 'Attempt',
    ],

    // Common
    'category_status' => [
        'active' => 'Active',
        'inactive' => 'Inactive',
    ],

    // Questions
    'question_type' => [
        'multiple_choice' => 'Multiple Choice',
        'checkbox' => 'Checkbox',
        'essay' => 'Essay',
        'file_upload' => 'File Upload',
        'true_false' => 'True/False',
    ],

    'question_difficulty' => [
        'easy' => 'Easy',
        'medium' => 'Medium',
        'hard' => 'Hard',
    ],

    'question_status' => [
        'active' => 'Active',
        'inactive' => 'Inactive',
        'archived' => 'Archived',
    ],
];
