<?php

declare(strict_types=1);

namespace Modules\Learning\Contracts\Repositories;

interface LearningRepositoryInterface
{
    public function view(string $template): string;
}
