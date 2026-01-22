<?php

declare(strict_types=1);

namespace Modules\Learning\Repositories;

use Modules\Learning\Contracts\Repositories\LearningRepositoryInterface;

class LearningRepository implements LearningRepositoryInterface
{
    public function view(string $template): string
    {
        return sprintf('learning::%s', $template);
    }
}
