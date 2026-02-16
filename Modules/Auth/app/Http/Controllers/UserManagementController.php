<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Auth\Http\Requests\UpdateUserStatusRequest;
use Modules\Auth\Http\Requests\CreateUserRequest;
use Modules\Auth\Contracts\Services\UserManagementServiceInterface;
use Modules\Auth\Http\Resources\UserResource;

class UserManagementController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly UserManagementServiceInterface $userManagementService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $users = $this->userManagementService->listUsersForIndex(
            $request->user(),
            $request->all(),
            (int) $request->query('per_page', 15)
        );

        $users->getCollection()->transform(fn($user) => new \Modules\Auth\Http\Resources\UserIndexResource($user));

        return $this->paginateResponse($users);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $user = $this->userManagementService->showUser(auth()->user(), $id, $request);
        
        return $this->success(new UserResource($user), 'messages.data_retrieved');
    }

    public function store(CreateUserRequest $request): JsonResponse
    {
        $user = $this->userManagementService->createUser(
            $request->user(),
            $request->validated()
        );
        
        return $this->created(new UserResource($user), 'messages.auth.user_created_success');
    }

    public function update(UpdateUserStatusRequest $request, int $id): JsonResponse
    {
        $user = $this->userManagementService->updateUserStatus(
            auth()->user(),
            $id,
            $request->input('status')
        );

        return $this->success(new UserResource($user), 'messages.auth.status_updated');
    }

    public function destroy(int $id): JsonResponse
    {
        $this->userManagementService->deleteUser(auth()->user(), $id);
        
        return $this->success(null, 'messages.deleted');
    }
}
