<?php

declare(strict_types=1);

namespace Modules\Learning\Repositories;

class LearningRepository
{
    public function view(string $template): string
    {
        return sprintf('learning::%s', $template);
    }
}
