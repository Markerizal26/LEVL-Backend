<?php

namespace Modules\Notifications\Enums;

enum NotificationType: string
{
    case System = 'system';
    case Assignment = 'assignment';
    case Assessment = 'assessment';
    case Grading = 'grading';
    case Gamification = 'gamification';
    case News = 'news';
    case Custom = 'custom';
    case CourseCompleted = 'course_completed';
    case Enrollment = 'enrollment';
    case ForumReplyToThread = 'forum_reply_to_thread';
    case ForumReplyToReply = 'forum_reply_to_reply';

    /**
     * Get all enum values as array.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get enum for validation rules.
     */
    public static function rule(): string
    {
        return 'in:'.implode(',', self::values());
    }

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::System => __('enums.notification_type.system'),
            self::Assignment => __('enums.notification_type.assignment'),
            self::Assessment => __('enums.notification_type.assessment'),
            self::Grading => __('enums.notification_type.grading'),
            self::Gamification => __('enums.notification_type.gamification'),
            self::News => __('enums.notification_type.news'),
            self::Custom => __('enums.notification_type.custom'),
            self::CourseCompleted => __('enums.notification_type.course_completed'),
            self::Enrollment => __('enums.notification_type.enrollment'),
            self::ForumReplyToThread => __('enums.notification_type.forum_reply_to_thread'),
            self::ForumReplyToReply => __('enums.notification_type.forum_reply_to_reply'),
        };
    }
}
