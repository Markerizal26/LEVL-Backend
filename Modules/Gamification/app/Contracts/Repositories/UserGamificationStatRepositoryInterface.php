<?php

namespace Modules\Gamification\Contracts\Repositories;

use Modules\Gamification\Models\UserGamificationStat;

interface UserGamificationStatRepositoryInterface
{
    
    public function findByUserId(int $userId): ?UserGamificationStat;

    public function create(array $data): UserGamificationStat;

    public function update(int $userId, array $data): bool;
}
