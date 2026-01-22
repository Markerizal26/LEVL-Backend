<?php

declare(strict_types=1);

namespace Modules\Learning\Services;

use Illuminate\Contracts\View\View;
use Modules\Learning\Repositories\LearningRepository;

class LearningPageService
{
    public function __construct(private readonly LearningRepository $repository) {}

    public function render(string $template, array $data = []): View
    {
        return view($this->repository->view($template), $data);
    }
}
