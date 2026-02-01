<?php

declare(strict_types=1);

namespace Modules\Gamification\Services\Support;

use Illuminate\Support\Facades\DB;
use Modules\Gamification\Models\UserBadge;
use Modules\Gamification\Repositories\GamificationRepository;

class BadgeManager
{
    public function __construct(
        private readonly GamificationRepository $repository
    ) {}

    public function awardBadge(
        int $userId,
        string $code,
        string $name,
        ?string $description = null
    ): ?UserBadge {
        return DB::transaction(function () use ($userId, $code, $name, $description) {
            $badge = $this->repository->firstOrCreateBadge($code, [
                'name' => $name,
                'description' => $description,
            ]);

            $existing = $this->repository->findUserBadge($userId, $badge->id);
            if ($existing) {
                return null;
            }

            return $this->repository->createUserBadge([
                'user_id' => $userId,
                'badge_id' => $badge->id,
                'awarded_at' => now(),
                'description' => $description,
            ]);
        });
    }

    public function countUserBadges(int $userId): int
    {
        return $this->repository->countByUserId($userId);
    }

    public function getUserBadges(int $userId): \Illuminate\Support\Collection
    {
        return $this->repository->findByUserId($userId);
    }
}
