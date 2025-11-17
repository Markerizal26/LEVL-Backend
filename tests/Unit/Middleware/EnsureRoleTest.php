<?php

use App\Http\Middleware\EnsureRole;
use Illuminate\Http\Request;
use Modules\Auth\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->middleware = new EnsureRole();
    createTestRoles();
});

test('middleware returns 401 when user not authenticated', function () {
    $request = Request::create('/test', 'GET');
    $next = fn ($req) => response()->json(['status' => 'success']);

    $response = $this->middleware->handle($request, $next, 'admin');

    expect($response->getStatusCode())->toEqual(401);
    expect($response->getContent())->toBeJson();
    $responseData = json_decode($response->getContent(), true);
    expect($responseData['status'])->toEqual('error');
    expect($responseData['message'])->toEqual('Tidak terotorisasi.');
});

test('middleware returns 403 when user does not have required role', function () {
    $user = User::factory()->create();
    $user->assignRole('Student');

    $request = Request::create('/test', 'GET');
    $request->setUserResolver(fn () => $user);
    $next = fn ($req) => response()->json(['status' => 'success']);

    $response = $this->middleware->handle($request, $next, 'admin', 'instructor');

    expect($response->getStatusCode())->toEqual(403);
    $responseData = json_decode($response->getContent(), true);
    expect($responseData['message'])->toEqual('Forbidden: insufficient role.');
});

test('middleware allows access when user has required role', function () {
    $user = User::factory()->create();
    $user->assignRole('Admin');

    $request = Request::create('/test', 'GET');
    $request->setUserResolver(fn () => $user);
    $next = fn ($req) => response()->json(['status' => 'success']);

    $response = $this->middleware->handle($request, $next, 'Admin');

    expect($response->getStatusCode())->toEqual(200);
    $responseData = json_decode($response->getContent(), true);
    expect($responseData['status'])->toEqual('success');
});

test('middleware allows access when user has any of multiple roles', function () {
    $user = User::factory()->create();
    $user->assignRole('Instructor');

    $request = Request::create('/test', 'GET');
    $request->setUserResolver(fn () => $user);
    $next = fn ($req) => response()->json(['status' => 'success']);

    $response = $this->middleware->handle($request, $next, 'Admin', 'Instructor');

    expect($response->getStatusCode())->toEqual(200);
});

test('middleware allows superadmin access to any role', function () {
    $user = User::factory()->create();
    $user->assignRole('Superadmin');

    $request = Request::create('/test', 'GET');
    $request->setUserResolver(fn () => $user);
    $next = fn ($req) => response()->json(['status' => 'success']);

    $response = $this->middleware->handle($request, $next, 'Admin', 'Instructor');

    expect($response->getStatusCode())->toEqual(200);
});