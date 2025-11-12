<?php

namespace Modules\Auth\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Facades\JWTAuth;

class AllowExpiredToken
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->is('api/v1/auth/refresh') && !$request->routeIs('auth.refresh')) {
            return response()->json([
                'status' => 'error',
                'message' => 'Middleware ini hanya untuk endpoint refresh.',
            ], 403);
        }

        if (!$request->has('refresh_token') || empty($request->input('refresh_token'))) {
            return response()->json([
                'status' => 'error',
                'message' => 'Refresh token diperlukan.',
            ], 400);
        }

        try {
            $user = JWTAuth::parseToken()->authenticate();
            
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Tidak terotorisasi.',
                ], 401);
            }

            auth('api')->setUser($user);
        } catch (TokenExpiredException $e) {
            try {
                $token = JWTAuth::parseToken();
                $payload = $token->getPayload();
                
                if (!$payload->has('sub') || !$payload->has('exp')) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Token tidak valid.',
                    ], 401);
                }

                $userId = $payload->get('sub');

                if (!$userId || !is_numeric($userId)) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Token tidak valid.',
                    ], 401);
                }

                $user = \Modules\Auth\Models\User::find($userId);
                if (!$user) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'User tidak ditemukan.',
                    ], 401);
                }

                if ($user->status !== 'active') {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Akun tidak aktif.',
                    ], 403);
                }

                auth('api')->setUser($user);
            } catch (TokenInvalidException $e) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Token tidak valid atau telah diubah.',
                ], 401);
            } catch (\Exception $e) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Token tidak valid atau tidak dapat didecode.',
                ], 401);
            }
        } catch (TokenInvalidException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token tidak valid.',
            ], 401);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tidak terotorisasi.',
            ], 401);
        }

        return $next($request);
    }
}
