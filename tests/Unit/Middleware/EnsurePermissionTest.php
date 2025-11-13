<?php

use App\Http\Middleware\EnsurePermission;
use Illuminate\Http\Request;
use Modules\Auth\Models\User;
use Spatie\Permission\Models\Permission;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->middleware = new EnsurePermission();
});

test('middleware returns 401 when user not authenticated', function () {
    $request = Request::create('/test', 'GET');
    $next = fn ($req) => response()->json(['status' => 'success']);

    $response = $this->middleware->handle($request, $next, 'courses.create');

    expect($response->getStatusCode())->toEqual(401);
    $responseData = json_decode($response->getContent(), true);
    expect($responseData['status'])->toEqual('error');
    expect($responseData['message'])->toEqual('Tidak terotorisasi.');
});

test('middleware returns 403 when user does not have permission', function () {
    $user = User::factory()->create();
    $permission = Permission::create(['name' => 'courses.create', 'guard_name' => 'api']);

    $request = Request::create('/test', 'GET');
    $request->setUserResolver(fn () => $user);
    $next = fn ($req) => response()->json(['status' => 'success']);

    $response = $this->middleware->handle($request, $next, 'courses.create');

    expect($response->getStatusCode())->toEqual(403);
    $responseData = json_decode($response->getContent(), true);
    expect($responseData['message'])->toContain('Akses ditolak');
});

test('middleware allows access when user has permission', function () {
    $user = User::factory()->create();
    $permission = Permission::create(['name' => 'courses.create', 'guard_name' => 'api']);
    $user->givePermissionTo($permission);

    $request = Request::create('/test', 'GET');
    $request->setUserResolver(fn () => $user);
    $next = fn ($req) => response()->json(['status' => 'success']);

    $response = $this->middleware->handle($request, $next, 'courses.create');

    expect($response->getStatusCode())->toEqual(200);
    $responseData = json_decode($response->getContent(), true);
    expect($responseData['status'])->toEqual('success');
});

test('middleware allows access when user has any of multiple permissions', function () {
    $user = User::factory()->create();
    $permission1 = Permission::create(['name' => 'courses.create', 'guard_name' => 'api']);
    $permission2 = Permission::create(['name' => 'courses.update', 'guard_name' => 'api']);
    $user->givePermissionTo($permission1);

    $request = Request::create('/test', 'GET');
    $request->setUserResolver(fn () => $user);
    $next = fn ($req) => response()->json(['status' => 'success']);

    $response = $this->middleware->handle($request, $next, 'courses.create', 'courses.update');

    expect($response->getStatusCode())->toEqual(200);
});