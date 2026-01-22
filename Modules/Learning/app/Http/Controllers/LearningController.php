<?php

declare(strict_types=1);

namespace Modules\Learning\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Modules\Learning\Services\LearningPageService;

class LearningController extends Controller
{
  use ApiResponse;

  public function __construct(private readonly LearningPageService $service) {}

  public function index(): mixed
  {
    return $this->service->render("index");
  }

  public function create(): mixed
  {
    return $this->service->render("create");
  }

  public function store(Request $request): JsonResponse
  {
    return $this->error(__("messages.feature_unavailable"), 501);
  }

  public function show(string $id): mixed
  {
    return $this->service->render("show");
  }

  public function edit(string $id): mixed
  {
    return $this->service->render("edit");
  }

  public function update(Request $request, string $id): JsonResponse
  {
    return $this->error(__("messages.feature_unavailable"), 501);
  }

  public function destroy(string $id): JsonResponse
  {
    return $this->error(__("messages.feature_unavailable"), 501);
  }
}
