<?php

namespace App\Support;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use ReflectionException;
use RegexIterator;

class ArchitectureValidator
{
    private array $violations = [];

    /**
     * Scan for services that don't implement their interfaces
     */
    public function scanForServiceInterfaces(): array
    {
        $violations = [];
        $servicePaths = $this->getServicePaths();

        foreach ($servicePaths as $servicePath) {
            $className = $this->getClassNameFromFile($servicePath);
            if (! $className) {
                continue;
            }

            try {
                $reflection = new ReflectionClass($className);

                // Skip interfaces and abstract classes
                if ($reflection->isInterface() || $reflection->isAbstract()) {
                    continue;
                }

                // Check if it's a service class
                if (! str_ends_with($className, 'Service')) {
                    continue;
                }

                // Expected interface name
                $interfaceName = $className.'Interface';

                // Check if interface exists
                if (! interface_exists($interfaceName)) {
                    $violations[] = [
                        'type' => 'missing_service_interface',
                        'file' => $servicePath,
                        'class' => $className,
                        'description' => 'Service class does not have a corresponding interface',
                        'suggestion' => "Create interface: {$interfaceName}",
                        'severity' => 'high',
                    ];

                    continue;
                }

                // Check if service implements the interface
                if (! $reflection->implementsInterface($interfaceName)) {
                    $violations[] = [
                        'type' => 'service_not_implementing_interface',
                        'file' => $servicePath,
                        'class' => $className,
                        'interface' => $interfaceName,
                        'description' => 'Service class exists but does not implement its interface',
                        'suggestion' => "Add 'implements {$interfaceName}' to class declaration",
                        'severity' => 'critical',
                    ];
                }
            } catch (ReflectionException $e) {
                // Skip classes that can't be reflected
                continue;
            }
        }

        return $violations;
    }

    /**
     * Scan for direct model queries in controllers
     */
    public function scanForDirectModelQueries(): array
    {
        $violations = [];
        $controllerPaths = $this->getControllerPaths();

        $patterns = [
            '/\:\:query\(\)/',
            '/\:\:where\(/',
            '/\:\:find\(/',
            '/\:\:create\(/',
            '/\:\:update\(/',
            '/\:\:delete\(/',
            '/\:\:first\(/',
            '/\:\:get\(/',
            '/\:\:all\(/',
            '/\:\:firstOrNew\(/',
            '/\:\:firstOrCreate\(/',
            '/\:\:updateOrCreate\(/',
        ];

        foreach ($controllerPaths as $controllerPath) {
            $content = file_get_contents($controllerPath);
            $lines = explode("\n", $content);

            foreach ($lines as $lineNumber => $line) {
                foreach ($patterns as $pattern) {
                    if (preg_match($pattern, $line)) {
                        // Check if it's not in a comment
                        $trimmedLine = trim($line);
                        if (str_starts_with($trimmedLine, '//') || str_starts_with($trimmedLine, '*')) {
                            continue;
                        }

                        $violations[] = [
                            'type' => 'direct_model_query_in_controller',
                            'file' => $controllerPath,
                            'line' => $lineNumber + 1,
                            'code' => trim($line),
                            'description' => 'Controller contains direct model query',
                            'suggestion' => 'Move query logic to service or repository',
                            'severity' => 'high',
                        ];
                        break; // Only report once per line
                    }
                }
            }
        }

        return $violations;
    }

    /**
     * Scan for manual authorization checks (Gate::forUser, etc.)
     */
    public function scanForManualAuthorization(): array
    {
        $violations = [];
        $controllerPaths = $this->getControllerPaths();

        $patterns = [
            '/Gate\:\:forUser\(/',
            '/Gate\:\:allows\(/',
            '/Gate\:\:denies\(/',
            '/Gate\:\:check\(/',
            '/\$user\->can\(/',
            '/\$user\->cannot\(/',
        ];

        foreach ($controllerPaths as $controllerPath) {
            $content = file_get_contents($controllerPath);
            $lines = explode("\n", $content);

            foreach ($lines as $lineNumber => $line) {
                foreach ($patterns as $pattern) {
                    if (preg_match($pattern, $line)) {
                        // Check if it's not in a comment
                        $trimmedLine = trim($line);
                        if (str_starts_with($trimmedLine, '//') || str_starts_with($trimmedLine, '*')) {
                            continue;
                        }

                        $violations[] = [
                            'type' => 'manual_authorization_check',
                            'file' => $controllerPath,
                            'line' => $lineNumber + 1,
                            'code' => trim($line),
                            'description' => 'Controller uses manual authorization check instead of policy',
                            'suggestion' => 'Use $this->authorize() method with policy',
                            'severity' => 'medium',
                        ];
                        break;
                    }
                }
            }
        }

        return $violations;
    }

    /**
     * Scan for missing repositories
     */
    public function scanForMissingRepositories(): array
    {
        $violations = [];
        $modelPaths = $this->getModelPaths();

        foreach ($modelPaths as $modelPath) {
            $className = $this->getClassNameFromFile($modelPath);
            if (! $className) {
                continue;
            }

            // Extract model name
            $parts = explode('\\', $className);
            $modelName = end($parts);

            // Expected repository name
            $repositoryName = str_replace('\\Models\\', '\\Repositories\\', $className).'Repository';
            $repositoryInterfaceName = str_replace('\\Models\\', '\\Contracts\\Repositories\\', $className).'RepositoryInterface';

            // Check if repository exists
            if (! class_exists($repositoryName)) {
                $violations[] = [
                    'type' => 'missing_repository',
                    'file' => $modelPath,
                    'model' => $className,
                    'description' => 'Model does not have a corresponding repository',
                    'suggestion' => "Create repository: {$repositoryName}",
                    'severity' => 'medium',
                ];
            }

            // Check if repository interface exists
            if (! interface_exists($repositoryInterfaceName)) {
                $violations[] = [
                    'type' => 'missing_repository_interface',
                    'file' => $modelPath,
                    'model' => $className,
                    'description' => 'Model does not have a corresponding repository interface',
                    'suggestion' => "Create interface: {$repositoryInterfaceName}",
                    'severity' => 'medium',
                ];
            }
        }

        return $violations;
    }

    /**
     * Scan for direct env() calls outside config files
     */
    public function scanForDirectEnvCalls(): array
    {
        $violations = [];
        $phpFiles = $this->getAllPhpFiles();

        foreach ($phpFiles as $filePath) {
            // Skip config files
            if (str_contains($filePath, '/config/')) {
                continue;
            }

            $content = file_get_contents($filePath);
            $lines = explode("\n", $content);

            foreach ($lines as $lineNumber => $line) {
                if (preg_match('/env\([\'"]/', $line)) {
                    // Check if it's not in a comment
                    $trimmedLine = trim($line);
                    if (str_starts_with($trimmedLine, '//') || str_starts_with($trimmedLine, '*')) {
                        continue;
                    }

                    $violations[] = [
                        'type' => 'direct_env_call',
                        'file' => $filePath,
                        'line' => $lineNumber + 1,
                        'code' => trim($line),
                        'description' => 'Direct env() call outside config file',
                        'suggestion' => 'Use config() helper instead',
                        'severity' => 'low',
                    ];
                }
            }
        }

        return $violations;
    }

    /**
     * Generate comprehensive report
     */
    public function generateReport(): array
    {
        return [
            'service_interfaces' => $this->scanForServiceInterfaces(),
            'direct_model_queries' => $this->scanForDirectModelQueries(),
            'manual_authorization' => $this->scanForManualAuthorization(),
            'missing_repositories' => $this->scanForMissingRepositories(),
            'direct_env_calls' => $this->scanForDirectEnvCalls(),
        ];
    }

    /**
     * Get all service file paths
     */
    private function getServicePaths(): array
    {
        return $this->getFilesByPattern(base_path(), '/Services\/.*Service\.php$/');
    }

    /**
     * Get all controller file paths
     */
    private function getControllerPaths(): array
    {
        return $this->getFilesByPattern(base_path(), '/Controllers\/.*Controller\.php$/');
    }

    /**
     * Get all model file paths
     */
    private function getModelPaths(): array
    {
        return $this->getFilesByPattern(base_path(), '/Models\/.*\.php$/');
    }

    /**
     * Get all PHP files
     */
    private function getAllPhpFiles(): array
    {
        return $this->getFilesByPattern(base_path(), '/\.php$/');
    }

    /**
     * Get files matching a pattern
     */
    private function getFilesByPattern(string $directory, string $pattern): array
    {
        $files = [];

        if (! is_dir($directory)) {
            return $files;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        $phpFiles = new RegexIterator($iterator, $pattern, RegexIterator::MATCH);

        foreach ($phpFiles as $file) {
            // Skip vendor directory
            if (str_contains($file->getPathname(), '/vendor/')) {
                continue;
            }
            // Skip node_modules
            if (str_contains($file->getPathname(), '/node_modules/')) {
                continue;
            }

            $files[] = $file->getPathname();
        }

        return $files;
    }

    /**
     * Extract class name from file
     */
    private function getClassNameFromFile(string $filePath): ?string
    {
        $content = file_get_contents($filePath);

        // Extract namespace
        if (! preg_match('/namespace\s+([^;]+);/', $content, $namespaceMatches)) {
            return null;
        }
        $namespace = $namespaceMatches[1];

        // Extract class name
        if (! preg_match('/class\s+(\w+)/', $content, $classMatches)) {
            return null;
        }
        $className = $classMatches[1];

        return $namespace.'\\'.$className;
    }
}
