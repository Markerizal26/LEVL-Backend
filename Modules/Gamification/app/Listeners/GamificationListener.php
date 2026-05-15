<?php

declare(strict_types=1);

namespace Modules\Gamification\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Modules\Gamification\Traits\CachesUsers;

abstract class GamificationListener implements ShouldQueue
{
    use CachesUsers;
    use InteractsWithQueue;

    public string $queue = 'notifications';

    public bool $afterCommit = true;

    public int $tries = 3;

    public int $maxExceptions = 2;

    public array $backoff = [5, 30, 120];
}
