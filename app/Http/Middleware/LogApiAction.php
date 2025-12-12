<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\Common\Models\Audit;
use Symfony\Component\HttpFoundation\Response;

class LogApiAction
{
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        if (! $request->is('api/*')) {
            return;
        }

        if ($request->method() === 'GET') {
            return;
        }

        if ($this->shouldSkipLogging($request)) {
            return;
        }

        try {
            $action = $this->mapMethodToAction($request->method());
            $user = auth('api')->user();
            $module = $this->extractModule($request);
            $targetTable = $this->extractTargetTable($request);
            $targetId = $this->extractTargetId($request);

            $meta = [
                'method' => $request->method(),
                'route' => $request->route()?->getName() ?? $request->path(),
                'url' => $request->fullUrl(),
                'status_code' => $response->getStatusCode(),
            ];

            if (in_array($request->method(), ['POST', 'PUT', 'PATCH'])) {
                // Limit request body logging to prevent memory issues
                $body = $request->except(['password', 'password_confirmation', 'old_password', 'token', 'api_key', 'secret']);

                // Filter out file uploads as they cannot be JSON encoded
                $body = array_filter($body, function ($value) {
                    return ! ($value instanceof \Illuminate\Http\UploadedFile);
                });

                // Also handle arrays of files
                foreach ($body as $key => $value) {
                    if (is_array($value)) {
                        $body[$key] = array_filter($value, function ($item) {
                            return ! ($item instanceof \Illuminate\Http\UploadedFile);
                        });
                    }
                }

                // Truncate large string values to prevent memory issues
                foreach ($body as $key => $value) {
                    if (is_string($value) && strlen($value) > 1000) {
                        $body[$key] = substr($value, 0, 1000).'... [truncated]';
                    }
                }

                $meta['request_body'] = $body;
            }

            if ($request->route()) {
                $meta['route_parameters'] = $request->route()->parameters();
            }

            Audit::create([
                'action' => $action,
                'actor_id' => $user?->id,
                'actor_type' => $user ? get_class($user) : null,
                'user_id' => $user?->id,
                'module' => $module,
                'target_table' => $targetTable,
                'target_id' => $targetId,
                'context' => 'application',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'meta' => $meta,
                'logged_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to log API action: '.$e->getMessage(), [
                'request_path' => $request->path(),
                'exception' => $e,
            ]);
        }
    }

    private function mapMethodToAction(string $method): string
    {
        return match ($method) {
            'POST' => 'create',
            'PUT', 'PATCH' => 'update',
            'DELETE' => 'delete',
            default => 'access',
        };
    }

    private function extractModule(Request $request): ?string
    {
        $path = $request->path();

        if (preg_match('#api/v1/([^/]+)#', $path, $matches)) {
            $moduleSegment = $matches[1];

            $moduleMap = [
                'courses' => 'Schemes',
                'course-tags' => 'Schemes',
                'units' => 'Schemes',
                'lessons' => 'Schemes',
                'assignments' => 'Learning',
                'submissions' => 'Learning',
                'enrollments' => 'Enrollments',
                'auth' => 'Auth',
                'users' => 'Auth',
                'categories' => 'Common',
                'system-settings' => 'Common',
            ];

            return $moduleMap[$moduleSegment] ?? ucfirst($moduleSegment);
        }

        return null;
    }

    private function extractTargetTable(Request $request): ?string
    {
        $path = $request->path();

        if (preg_match('#api/v1/[^/]+/([^/]+)(?:/.*)?$#', $path, $matches)) {
            $resource = $matches[1];

            $tableMap = [
                'courses' => 'courses',
                'course-tags' => 'tags',
                'tags' => 'tags',
                'units' => 'units',
                'lessons' => 'lessons',
                'assignments' => 'assignments',
                'submissions' => 'submissions',
                'enrollments' => 'enrollments',
                'exercises' => 'exercises',
                'attempts' => 'attempts',
                'users' => 'users',
                'categories' => 'categories',
            ];

            return $tableMap[$resource] ?? $resource;
        }

        return null;
    }

    private function extractTargetId(Request $request): ?int
    {
        $route = $request->route();

        if (! $route) {
            return null;
        }

        $parameters = $route->parameters();

        $idKeys = ['id', 'course', 'unit', 'lesson', 'assignment', 'submission', 'enrollment', 'exercise', 'attempt', 'user', 'category'];

        foreach ($idKeys as $key) {
            if (isset($parameters[$key])) {
                $value = $parameters[$key];
                if (is_object($value) && method_exists($value, 'getKey')) {
                    return (int) $value->getKey();
                }
                if (is_numeric($value)) {
                    return (int) $value;
                }
            }
        }

        return null;
    }

    private function shouldSkipLogging(Request $request): bool
    {
        $path = $request->path();

        $skipPaths = [
            'up',
            'health',
            'api/v1/system-audits',
        ];

        foreach ($skipPaths as $skipPath) {
            if (str_contains($path, $skipPath)) {
                return true;
            }
        }

        return false;
    }
}
