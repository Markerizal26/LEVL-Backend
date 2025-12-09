<?php

/**
 * Postman Collection Export Script
 *
 * Generates Postman Collection v2.1 from Laravel routes and controller docblocks.
 * Includes authentication setup, example requests, and response examples.
 *
 * Usage:
 *   php scripts/export-postman-collection.php
 *   php scripts/export-postman-collection.php --output=/path/to/collection.json
 */

namespace App\Scripts;

use Illuminate\Support\Facades\Route;

class PostmanCollectionExporter
{
    private array $collection;
    private string $outputPath;

    private array $moduleNameMap = [
        'Auth' => 'Autentikasi',
        'Content' => 'Konten',
        'Enrollments' => 'Pendaftaran Kursus',
        'Forums' => 'Forum Diskusi',
        'Gamification' => 'Gamifikasi',
        'Grading' => 'Penilaian',
        'Learning' => 'Pembelajaran',
        'Notifications' => 'Notifikasi',
        'Operations' => 'Operasional',
        'Schemes' => 'Skema & Kursus',
        'Search' => 'Pencarian',
        'Common' => 'Umum',
    ];

    public function __construct(?string $outputPath = null)
    {
        $this->outputPath = $outputPath ?? storage_path('api-docs/postman-collection.json');
    }

    public function run(): void
    {
        echo "📦 Postman Collection Export\n";
        echo str_repeat('=', 50) . "\n\n";

        $this->initializeCollection();
        $this->processRoutes();
        $this->saveCollection();

        echo "\n✨ Done! Postman collection exported to:\n";
        echo "   {$this->outputPath}\n\n";
        echo "Import this file into Postman to test your API.\n\n";
    }

    private function initializeCollection(): void
    {
        $this->collection = [
            'info' => [
                'name' => 'LMS API Collection',
                'description' => 'Auto-generated Postman collection for LMS API',
                'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
            ],
            'auth' => [
                'type' => 'bearer',
                'bearer' => [
                    [
                        'key' => 'token',
                        'value' => '{{access_token}}',
                        'type' => 'string',
                    ],
                ],
            ],
            'variable' => [
                [
                    'key' => 'base_url',
                    'value' => 'http://localhost:8000/api/v1',
                    'type' => 'string',
                ],
                [
                    'key' => 'access_token',
                    'value' => '',
                    'type' => 'string',
                ],
            ],
            'item' => [],
        ];
    }

    private function processRoutes(): void
    {
        $routes = $this->getApiRoutes();
        $groupedRoutes = $this->groupByModule($routes);

        foreach ($groupedRoutes as $module => $moduleRoutes) {
            $moduleFolder = [
                'name' => $this->moduleNameMap[$module] ?? $module,
                'item' => [],
            ];

            foreach ($moduleRoutes as $route) {
                $request = $this->createPostmanRequest($route);
                if ($request) {
                    $moduleFolder['item'][] = $request;
                }
            }

            if (!empty($moduleFolder['item'])) {
                $this->collection['item'][] = $moduleFolder;
            }
        }

        echo "Processed " . count($routes) . " routes\n";
        echo "Created " . count($this->collection['item']) . " module folders\n";
    }

    private function getApiRoutes(): array
    {
        $routes = [];

        foreach (Route::getRoutes() as $route) {
            $uri = $route->uri();

            // Only include API routes
            if (!str_starts_with($uri, 'api/')) {
                continue;
            }

            $action = $route->getAction();

            if (!isset($action['controller'])) {
                continue;
            }

            [$controller, $method] = explode('@', $action['controller']);

            $routes[] = [
                'uri' => $uri,
                'method' => $route->methods()[0],
                'controller' => $controller,
                'action' => $method,
                'name' => $route->getName(),
            ];
        }

        return $routes;
    }

    private function groupByModule(array $routes): array
    {
        $grouped = [];

        foreach ($routes as $route) {
            $module = $this->extractModuleName($route['controller']);
            $grouped[$module][] = $route;
        }

        ksort($grouped);

        return $grouped;
    }

    private function extractModuleName(string $controller): string
    {
        if (preg_match('/Modules\\\\(\w+)\\\\/', $controller, $matches)) {
            return $matches[1];
        }

        return 'App';
    }

    private function createPostmanRequest(array $route): ?array
    {
        $docblock = $this->getMethodDocblock($route['controller'], $route['action']);

        if (!$docblock) {
            return null;
        }

        $summary = $this->extractSummary($docblock);
        $isAuthenticated = str_contains($docblock, '@authenticated');

        $request = [
            'name' => $summary ?: $route['action'],
            'request' => [
                'method' => strtoupper($route['method']),
                'header' => [],
                'url' => $this->buildUrl($route['uri']),
            ],
        ];

        // Add auth if required
        if ($isAuthenticated) {
            $request['request']['auth'] = [
                'type' => 'bearer',
                'bearer' => [
                    [
                        'key' => 'token',
                        'value' => '{{access_token}}',
                        'type' => 'string',
                    ],
                ],
            ];
        } else {
            $request['request']['auth'] = [
                'type' => 'noauth',
            ];
        }

        // Add body if POST/PUT/PATCH
        if (in_array($route['method'], ['POST', 'PUT', 'PATCH'])) {
            $bodyParams = $this->extractBodyParams($docblock);
            if (!empty($bodyParams)) {
                $request['request']['body'] = [
                    'mode' => 'raw',
                    'raw' => json_encode($bodyParams, JSON_PRETTY_PRINT),
                    'options' => [
                        'raw' => [
                            'language' => 'json',
                        ],
                    ],
                ];
            }
        }

        // Add query params for GET requests with filters/sorts/pagination
        if ($route['method'] === 'GET') {
            $queryParams = $this->extractQueryParams($docblock);
            if (!empty($queryParams)) {
                // Postman doesn't have a specific query param field in request,
                // but we can add them to the URL
                $request['request']['url']['query'] = $queryParams;
            }
        }

        // Add example responses
        $responses = $this->extractResponses($docblock);
        if (!empty($responses)) {
            $request['response'] = $responses;
        }

        return $request;
    }

    private function getMethodDocblock(string $controller, string $method): ?string
    {
        try {
            $reflection = new \ReflectionClass($controller);

            if (!$reflection->hasMethod($method)) {
                return null;
            }

            $methodReflection = $reflection->getMethod($method);
            $docComment = $methodReflection->getDocComment();

            return $docComment ?: null;
        } catch (\ReflectionException $e) {
            // Controller class doesn't exist, skip it
            return null;
        }
    }

    private function extractSummary(string $docblock): ?string
    {
        if (preg_match('/@summary\s+(.+?)(?:\n|\*\/)/s', $docblock, $matches)) {
            return trim($matches[1]);
        }

        // Fallback: extract first line
        if (preg_match('/\*\s*(.+?)\n/', $docblock, $matches)) {
            $line = trim($matches[1]);
            if (!str_starts_with($line, '@')) {
                return $line;
            }
        }

        return null;
    }

    private function extractBodyParams(string $docblock): array
    {
        $params = [];

        preg_match_all('/@bodyParam\s+(\w+)\s+(\w+)\s+(required|optional)\s+(.+?)(?:\n|Example:)/s', $docblock, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $field = $match[1];
            $type = $match[2];
            $required = $match[3] === 'required';

            $value = $this->getExampleValue($type);
            $params[$field] = $value;
        }

        return $params;
    }

    private function extractQueryParams(string $docblock): array
    {
        $params = [];

        // Extract from @queryParam tags
        preg_match_all('/@queryParam\s+(\w+)\s+(\w+)(?:\s+(.+?))?(?:\n|Example:)/s', $docblock, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $field = $match[1];
            $type = $match[2];

            $params[] = [
                'key' => $field,
                'value' => '',
                'description' => $match[3] ?? '',
                'disabled' => true,
            ];
        }

        return $params;
    }

    private function getExampleValue(string $type): mixed
    {
        return match ($type) {
            'string' => 'example string',
            'integer' => 1,
            'boolean' => true,
            'array' => [],
            default => null,
        };
    }

    private function extractResponses(string $docblock): array
    {
        $responses = [];

        preg_match_all('/@response\s+(\d+)(?:\s+scenario="([^"]+)")?\s+(\{.+?\})/s', $docblock, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $statusCode = $match[1];
            $scenario = $match[2] ?? 'Example';
            $body = $match[3];

            // Try to format JSON
            $decoded = json_decode($body, true);
            if ($decoded !== null) {
                $body = json_encode($decoded, JSON_PRETTY_PRINT);
            }

            $responses[] = [
                'name' => "{$statusCode} - {$scenario}",
                'originalRequest' => null,
                'status' => $this->getStatusText($statusCode),
                'code' => (int) $statusCode,
                '_postman_previewlanguage' => 'json',
                'header' => [
                    [
                        'key' => 'Content-Type',
                        'value' => 'application/json',
                    ],
                ],
                'body' => $body,
            ];
        }

        return $responses;
    }

    private function getStatusText(string $code): string
    {
        $statuses = [
            '200' => 'OK',
            '201' => 'Created',
            '204' => 'No Content',
            '400' => 'Bad Request',
            '401' => 'Unauthorized',
            '403' => 'Forbidden',
            '404' => 'Not Found',
            '422' => 'Unprocessable Entity',
            '429' => 'Too Many Requests',
            '500' => 'Internal Server Error',
        ];

        return $statuses[$code] ?? 'Response';
    }

    private function buildUrl(string $uri): array
    {
        // Replace route parameters with Postman variables
        $uri = preg_replace('/\{(\w+)\}/', ':$1', $uri);

        $parts = explode('/', trim($uri, '/'));

        return [
            'raw' => '{{base_url}}/' . implode('/', $parts),
            'host' => ['{{base_url}}'],
            'path' => $parts,
        ];
    }

    private function saveCollection(): void
    {
        $dir = dirname($this->outputPath);

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $json = json_encode($this->collection, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        file_put_contents($this->outputPath, $json);

        echo "✅ Saved to: {$this->outputPath}\n";
    }
}

// Run the script
if (php_sapi_name() === 'cli') {
    require_once __DIR__ . '/../vendor/autoload.php';

    $app = require_once __DIR__ . '/../bootstrap/app.php';
    $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

    // Parse output path from arguments
    $outputPath = null;
    foreach ($argv ?? [] as $arg) {
        if (str_starts_with($arg, '--output=')) {
            $outputPath = substr($arg, 9);
        }
    }

    $exporter = new PostmanCollectionExporter($outputPath);
    $exporter->run();
}
