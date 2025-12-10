<?php

/**
 * Scramble API Documentation Validation Script
 *
 * Validates completeness of Scramble documentation tags in all controllers.
 * Checks for @authenticated, @summary, @response, and validates JSON format.
 *
 * Usage:
 *   php scripts/validate-api-docs.php
 *   php scripts/validate-api-docs.php --strict  # Exit 1 if coverage < 90%
 */

namespace App\Scripts;

class ScrambleDocsValidator
{
    private array $stats = [
        'total_controllers' => 0,
        'total_methods' => 0,
        'methods_with_auth' => 0,
        'methods_with_summary' => 0,
        'methods_with_response' => 0,
        'invalid_json' => 0,
    ];

    private array $moduleStats = [];

    private array $issues = [];

    private bool $strictMode = false;

    public function __construct(bool $strictMode = false)
    {
        $this->strictMode = $strictMode;
    }

    public function run(): int
    {
        echo "📊 Scramble API Documentation Validation\n";
        echo str_repeat('=', 50)."\n\n";

        $modulesPath = base_path('Modules');
        $this->scanDirectory($modulesPath);

        $this->printReport();

        return $this->getExitCode();
    }

    private function scanDirectory(string $path): void
    {
        if (! is_dir($path)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $filePath = $file->getPathname();

                if (strpos($filePath, 'Controller.php') !== false) {
                    $this->validateController($filePath);
                }
            }
        }
    }

    private function validateController(string $filePath): void
    {
        $this->stats['total_controllers']++;

        $content = file_get_contents($filePath);
        $moduleName = $this->extractModuleName($filePath);

        if (! isset($this->moduleStats[$moduleName])) {
            $this->moduleStats[$moduleName] = [
                'total' => 0,
                'complete' => 0,
                'issues' => [],
            ];
        }

        $methods = $this->extractPublicMethods($content, $filePath);

        foreach ($methods as $method) {
            $this->stats['total_methods']++;
            $this->moduleStats[$moduleName]['total']++;

            $docblock = $this->extractDocblock($content, $method);
            $issues = $this->validateMethod($docblock, $method, $filePath);

            if (empty($issues)) {
                $this->moduleStats[$moduleName]['complete']++;
            } else {
                $this->moduleStats[$moduleName]['issues'][] = [
                    'method' => $method,
                    'issues' => $issues,
                    'file' => $this->getRelativePath($filePath),
                ];
            }
        }
    }

    private function extractPublicMethods(string $content, string $filePath): array
    {
        preg_match_all('/public function (\w+)\(/', $content, $matches);

        $methods = [];
        foreach ($matches[1] as $method) {
            // Skip magic methods and non-route methods
            if (strpos($method, '__') === 0 ||
                in_array($method, ['middleware', 'authorize', 'callAction'])) {
                continue;
            }

            $methods[] = $method;
        }

        return $methods;
    }

    private function extractDocblock(string $content, string $methodName): string
    {
        $pattern = '/\/\*\*\s*\n((?:\s*\*.*\n)*?)\s*\*\/\s*\n\s*public function '.$methodName.'\(/';

        if (preg_match($pattern, $content, $matches)) {
            return $matches[0];
        }

        return '';
    }

    private function validateMethod(string $docblock, string $methodName, string $filePath): array
    {
        $issues = [];

        // Check @authenticated or @unauthenticated
        if (str_contains($docblock, '@authenticated') || str_contains($docblock, '@unauthenticated')) {
            $this->stats['methods_with_auth']++;
        } else {
            $issues[] = 'Missing @authenticated or @unauthenticated';
        }

        // Check @summary
        if (str_contains($docblock, '@summary')) {
            $this->stats['methods_with_summary']++;
        } else {
            $issues[] = 'Missing @summary';
        }

        // Check @response
        if (str_contains($docblock, '@response')) {
            $this->stats['methods_with_response']++;

            // Validate JSON
            $jsonIssues = $this->validateResponseJson($docblock);
            if (! empty($jsonIssues)) {
                $this->stats['invalid_json']++;
                $issues = array_merge($issues, $jsonIssues);
            }
        } else {
            $issues[] = 'Missing @response';
        }

        if (! empty($issues)) {
            $this->issues[] = [
                'file' => $this->getRelativePath($filePath),
                'method' => $methodName,
                'issues' => $issues,
            ];
        }

        return $issues;
    }

    private function validateResponseJson(string $docblock): array
    {
        $issues = [];

        // Find all @response tags
        preg_match_all('/@response\s+\d+[^\{]*(\{)/s', $docblock, $matches, PREG_OFFSET_CAPTURE);

        foreach ($matches[1] as $match) {
            $startPos = $match[1];
            $json = $this->extractBalancedJson($docblock, $startPos);

            if ($json === null) {
                $issues[] = 'Invalid JSON in @response: Could not extract balanced braces';
                break;
            }

            // Clean up the JSON (remove comments, extra spaces)
            $json = preg_replace('/\/\/.*$/m', '', $json);
            $json = trim($json);

            json_decode($json);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $issues[] = 'Invalid JSON in @response: '.json_last_error_msg();
                break; // Only report once per method
            }
        }

        return $issues;
    }

    /**
     * Extract balanced JSON string starting from a position
     */
    private function extractBalancedJson(string $content, int $startPos): ?string
    {
        $depth = 0;
        $inString = false;
        $escape = false;
        $len = strlen($content);

        for ($i = $startPos; $i < $len; $i++) {
            $char = $content[$i];

            if ($escape) {
                $escape = false;

                continue;
            }

            if ($char === '\\' && $inString) {
                $escape = true;

                continue;
            }

            if ($char === '"') {
                $inString = ! $inString;

                continue;
            }

            if (! $inString) {
                if ($char === '{') {
                    $depth++;
                } elseif ($char === '}') {
                    $depth--;
                    if ($depth === 0) {
                        return substr($content, $startPos, $i - $startPos + 1);
                    }
                }
            }
        }

        return null; // Unbalanced braces
    }

    private function extractModuleName(string $filePath): string
    {
        if (preg_match('/Modules\/(\w+)\//', $filePath, $matches)) {
            return $matches[1];
        }

        return 'App';
    }

    private function getRelativePath(string $filePath): string
    {
        return str_replace(base_path().'/', '', $filePath);
    }

    private function printReport(): void
    {
        // Module-level report
        echo "📁 Coverage by Module:\n";
        echo str_repeat('-', 50)."\n";

        ksort($this->moduleStats);

        foreach ($this->moduleStats as $module => $stats) {
            $coverage = $stats['total'] > 0
                ? round(($stats['complete'] / $stats['total']) * 100, 1)
                : 0;

            $icon = $coverage >= 90 ? '✅' : ($coverage >= 70 ? '⚠️ ' : '❌');

            echo sprintf(
                "%s %-20s %5.1f%% (%d/%d endpoints)\n",
                $icon,
                $module.':',
                $coverage,
                $stats['complete'],
                $stats['total']
            );

            // Show issues for this module
            if (! empty($stats['issues']) && count($stats['issues']) <= 3) {
                foreach ($stats['issues'] as $issue) {
                    echo "   └─ {$issue['method']}: ".implode(', ', $issue['issues'])."\n";
                }
            } elseif (! empty($stats['issues'])) {
                echo '   └─ '.count($stats['issues'])." endpoints need improvement\n";
            }
        }

        echo "\n";

        // Overall statistics
        echo "📊 Overall Statistics:\n";
        echo str_repeat('-', 50)."\n";

        $overallCoverage = $this->stats['total_methods'] > 0
            ? round(($this->moduleStats ? array_sum(array_column($this->moduleStats, 'complete')) : 0) / $this->stats['total_methods'] * 100, 1)
            : 0;

        echo "Total Controllers: {$this->stats['total_controllers']}\n";
        echo "Total Endpoints:   {$this->stats['total_methods']}\n";
        echo "\n";

        $authCoverage = $this->calculateCoverage($this->stats['methods_with_auth']);
        $summaryCoverage = $this->calculateCoverage($this->stats['methods_with_summary']);
        $responseCoverage = $this->calculateCoverage($this->stats['methods_with_response']);

        echo sprintf("@authenticated:    %5.1f%% (%d/%d)\n", $authCoverage, $this->stats['methods_with_auth'], $this->stats['total_methods']);
        echo sprintf("@summary:          %5.1f%% (%d/%d)\n", $summaryCoverage, $this->stats['methods_with_summary'], $this->stats['total_methods']);
        echo sprintf("@response:         %5.1f%% (%d/%d)\n", $responseCoverage, $this->stats['methods_with_response'], $this->stats['total_methods']);

        if ($this->stats['invalid_json'] > 0) {
            echo sprintf("\n⚠️  Invalid JSON:    %d endpoints\n", $this->stats['invalid_json']);
        }

        echo "\n";
        echo str_repeat('=', 50)."\n";
        echo sprintf("Overall Coverage:  %5.1f%%\n", $overallCoverage);
        echo str_repeat('=', 50)."\n";

        // Missing tags summary
        $missingSummary = $this->stats['total_methods'] - $this->stats['methods_with_summary'];
        $missingResponse = $this->stats['total_methods'] - $this->stats['methods_with_response'];

        if ($missingSummary > 0 || $missingResponse > 0) {
            echo "\n⚠️  Missing Tags:\n";
            if ($missingSummary > 0) {
                echo "   - @summary: {$missingSummary} endpoints\n";
            }
            if ($missingResponse > 0) {
                echo "   - @response: {$missingResponse} endpoints\n";
            }
        }

        if ($this->strictMode) {
            echo "\n";
            if ($overallCoverage >= 90) {
                echo "✅ PASSED: Coverage >= 90%\n";
            } else {
                echo "❌ FAILED: Coverage < 90%\n";
            }
        }

        echo "\n";
    }

    private function calculateCoverage(int $count): float
    {
        return $this->stats['total_methods'] > 0
            ? round(($count / $this->stats['total_methods']) * 100, 1)
            : 0;
    }

    private function getExitCode(): int
    {
        if (! $this->strictMode) {
            return 0;
        }

        $overallCoverage = $this->stats['total_methods'] > 0
            ? ($this->moduleStats ? array_sum(array_column($this->moduleStats, 'complete')) : 0) / $this->stats['total_methods'] * 100
            : 0;

        return $overallCoverage >= 90 ? 0 : 1;
    }
}

// Run the script
if (php_sapi_name() === 'cli') {
    require_once __DIR__.'/../vendor/autoload.php';

    $app = require_once __DIR__.'/../bootstrap/app.php';
    $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

    $strictMode = in_array('--strict', $argv ?? []);
    $validator = new ScrambleDocsValidator($strictMode);
    $exitCode = $validator->run();

    exit($exitCode);
}
