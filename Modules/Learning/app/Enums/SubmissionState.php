<?php

declare(strict_types=1);

namespace Modules\Learning\Enums;

enum SubmissionState: string
{
    case InProgress = 'in_progress';
    case Submitted = 'submitted';
    case AutoGraded = 'auto_graded';
    case PendingManualGrading = 'pending_manual_grading';
    case Graded = 'graded';
    case Released = 'released';

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
            self::InProgress => __('enums.submission_state.in_progress'),
            self::Submitted => __('enums.submission_state.submitted'),
            self::AutoGraded => __('enums.submission_state.auto_graded'),
            self::PendingManualGrading => __('enums.submission_state.pending_manual_grading'),
            self::Graded => __('enums.submission_state.graded'),
            self::Released => __('enums.submission_state.released'),
        };
    }

    /**
     * Get valid transitions from this state.
     */
    public function validTransitions(): array
    {
        return match ($this) {
            self::InProgress => [self::Submitted],
            self::Submitted => [self::AutoGraded, self::PendingManualGrading],
            self::AutoGraded => [self::Released],
            self::PendingManualGrading => [self::Graded],
            self::Graded => [self::Released],
            self::Released => [],
        };
    }

    /**
     * Check if transition to given state is valid.
     */
    public function canTransitionTo(self $newState): bool
    {
        return in_array($newState, $this->validTransitions(), true);
    }
}
