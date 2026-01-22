<?php

declare(strict_types=1);

namespace Modules\Learning\Enums;

enum SubmissionStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Graded = 'graded';
    case Late = 'late';

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
            self::Draft => __('enums.submission_status.draft'),
            self::Submitted => __('enums.submission_status.submitted'),
            self::Graded => __('enums.submission_status.graded'),
            self::Late => __('enums.submission_status.late'),
        };
    }
}
