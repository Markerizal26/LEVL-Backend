<?php

namespace App\Support\Helpers;

use Modules\Schemes\Models\Course;
use Modules\Schemes\Models\Lesson;

class UrlHelper
{
    /**
     * Get the frontend URL
     */
    public static function getFrontendUrl(): string
    {
        return rtrim(config('app.frontend_url', 'http://localhost:3000'), '/');
    }

    /**
     * Get course URL
     */
    public static function getCourseUrl(Course $course): string
    {
        return self::getFrontendUrl().'/courses/'.$course->slug;
    }

    /**
     * Get enrollments URL for a course
     */
    public static function getEnrollmentsUrl(Course $course): string
    {
        return self::getFrontendUrl().'/courses/'.$course->slug.'/enrollments';
    }

    /**
     * Get lesson URL
     */
    public static function getLessonUrl(Course $course, Lesson $lesson): string
    {
        return self::getFrontendUrl().'/courses/'.$course->slug.'/lessons/'.$lesson->slug;
    }

    /**
     * Get unit URL
     */
    public static function getUnitUrl(Course $course, $unit): string
    {
        return self::getFrontendUrl().'/courses/'.$course->slug.'/units/'.$unit->slug;
    }

    /**
     * Get profile URL
     */
    public static function getProfileUrl(int $userId): string
    {
        return self::getFrontendUrl().'/profile/'.$userId;
    }

    /**
     * Get dashboard URL
     */
    public static function getDashboardUrl(): string
    {
        return self::getFrontendUrl().'/dashboard';
    }
}
