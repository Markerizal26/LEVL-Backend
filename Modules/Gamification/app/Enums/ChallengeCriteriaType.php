<?php

namespace Modules\Gamification\Enums;

enum ChallengeCriteriaType: string
{
    case LessonsCompleted = 'lessons_completed';
    case AssignmentsSubmitted = 'assignments_submitted';
    case ExercisesCompleted = 'exercises_completed';
    case XpEarned = 'xp_earned';
    case StreakDays = 'streak_days';
    case CoursesCompleted = 'courses_completed';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function rule(): string
    {
        return 'in:'.implode(',', self::values());
    }

    public function label(): string
    {
        return match ($this) {
            self::LessonsCompleted => 'Selesaikan Lesson',
            self::AssignmentsSubmitted => 'Kumpulkan Tugas',
            self::ExercisesCompleted => 'Selesaikan Latihan',
            self::XpEarned => 'Kumpulkan XP',
            self::StreakDays => 'Pertahankan Streak',
            self::CoursesCompleted => 'Selesaikan Course',
        };
    }
}
