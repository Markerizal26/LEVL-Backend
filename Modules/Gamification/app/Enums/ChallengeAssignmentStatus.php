<?php

namespace Modules\Gamification\Enums;

enum ChallengeAssignmentStatus: string
{
    case Pending = 'pending';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Claimed = 'claimed';
    case Expired = 'expired';

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
            self::Pending => 'Menunggu',
            self::InProgress => 'Sedang Dikerjakan',
            self::Completed => 'Selesai',
            self::Claimed => 'Reward Diklaim',
            self::Expired => 'Kadaluarsa',
        };
    }

    public function isActive(): bool
    {
        return in_array($this, [self::Pending, self::InProgress]);
    }

    public function canClaimReward(): bool
    {
        return $this === self::Completed;
    }
}
