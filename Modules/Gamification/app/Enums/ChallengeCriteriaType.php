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
            self::LessonsCompleted => __('enums.challenge_criteria_type.lessons_completed'),
            self::AssignmentsSubmitted => __('enums.challenge_criteria_type.assignments_submitted'),
            self::ExercisesCompleted => __('enums.challenge_criteria_type.exercises_completed'),
            self::XpEarned => __('enums.challenge_criteria_type.xp_earned'),
            self::StreakDays => __('enums.challenge_criteria_type.streak_days'),
            self::CoursesCompleted => __('enums.challenge_criteria_type.courses_completed'),
        };
    }
}
