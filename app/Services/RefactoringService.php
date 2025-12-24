<?php

namespace App\Services;

use App\Support\ArchitectureValidator;
use App\Support\InterfaceGenerator;
use App\Support\RepositoryGenerator;

class RefactoringService
{
    public function __construct(
        private ArchitectureValidator $validator,
        private InterfaceGenerator $interfaceGenerator,
        private RepositoryGenerator $repositoryGenerator
    ) {}

    /**
     * Analyze a module for architectural violations
     */
    public function analyzeModule(string $moduleName): array
    {
        $report = $this->validator->generateReport();

        // Filter violations by module
        $moduleViolations = [];
        foreach ($report as $category => $violations) {
            $filtered = array_filter($violations, function ($violation) use ($moduleName) {
                return str_contains($violation['file'], "Modules/{$moduleName}/");
            });

            if (! empty($filtered)) {
                $moduleViolations[$category] = array_values($filtered);
            }
        }

        return [
            'module' => $moduleName,
            'violations' => $moduleViolations,
            'total_violations' => array_sum(array_map('count', $moduleViolations)),
            'severity_breakdown' => $this->calculateSeverityBreakdown($moduleViolations),
        ];
    }

    /**
     * Generate comprehensive report for all modules
     */
    public function generateReport(): array
    {
        $report = $this->validator->generateReport();

        $summary = [
            'total_violations' => 0,
            'by_category' => [],
            'by_severity' => [
                'critical' => 0,
                'high' => 0,
                'medium' => 0,
                'low' => 0,
            ],
            'by_module' => [],
        ];

        foreach ($report as $category => $violations) {
            $summary['total_violations'] += count($violations);
            $summary['by_category'][$category] = count($violations);

            foreach ($violations as $violation) {
                // Count by severity
                $severity = $violation['severity'] ?? 'medium';
                $summary['by_severity'][$severity]++;

                // Count by module
                if (preg_match('/Modules\/([^\/]+)\//', $violation['file'], $matches)) {
                    $module = $matches[1];
                    if (! isset($summary['by_module'][$module])) {
                        $summary['by_module'][$module] = 0;
                    }
                    $summary['by_module'][$module]++;
                }
            }
        }

        return [
            'summary' => $summary,
            'violations' => $report,
            'generated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Suggest refactoring for a specific file
     */
    public function suggestRefactoring(string $file): array
    {
        $report = $this->validator->generateReport();
        $suggestions = [];

        foreach ($report as $category => $violations) {
            foreach ($violations as $violation) {
                if ($violation['file'] === $file) {
                    $suggestions[] = [
                        'type' => $violation['type'],
                        'description' => $violation['description'],
                        'suggestion' => $violation['suggestion'],
                        'severity' => $violation['severity'],
                        'line' => $violation['line'] ?? null,
                    ];
                }
            }
        }

        return [
            'file' => $file,
            'suggestions' => $suggestions,
            'total_issues' => count($suggestions),
        ];
    }

    /**
     * Validate that refactoring was successful
     */
    public function validateRefactoring(string $file): bool
    {
        $suggestions = $this->suggestRefactoring($file);

        return $suggestions['total_issues'] === 0;
    }

    /**
     * Generate missing interface for a service
     */
    public function generateServiceInterface(string $serviceClass): array
    {
        try {
            $interfaceContent = $this->interfaceGenerator->generateServiceInterface($serviceClass);

            // Determine interface path
            $reflection = new \ReflectionClass($serviceClass);
            $filePath = $reflection->getFileName();
            $interfacePath = str_replace('/Services/', '/Contracts/Services/', $filePath);
            $interfacePath = str_replace('.php', 'Interface.php', $interfacePath);

            return [
                'success' => true,
                'interface_path' => $interfacePath,
                'interface_content' => $interfaceContent,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Generate missing repository for a model
     */
    public function generateRepository(string $modelClass): array
    {
        try {
            $repositoryContent = $this->repositoryGenerator->generateRepository($modelClass);
            $interfaceContent = $this->repositoryGenerator->generateRepositoryInterface($modelClass);

            // Determine paths
            $reflection = new \ReflectionClass($modelClass);
            $filePath = $reflection->getFileName();

            $repositoryPath = str_replace('/Models/', '/Repositories/', $filePath);
            $repositoryPath = str_replace('.php', 'Repository.php', $repositoryPath);

            $interfacePath = str_replace('/Models/', '/Contracts/Repositories/', $filePath);
            $interfacePath = str_replace('.php', 'RepositoryInterface.php', $interfacePath);

            return [
                'success' => true,
                'repository_path' => $repositoryPath,
                'repository_content' => $repositoryContent,
                'interface_path' => $interfacePath,
                'interface_content' => $interfaceContent,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Calculate severity breakdown
     */
    private function calculateSeverityBreakdown(array $violations): array
    {
        $breakdown = [
            'critical' => 0,
            'high' => 0,
            'medium' => 0,
            'low' => 0,
        ];

        foreach ($violations as $category => $items) {
            foreach ($items as $item) {
                $severity = $item['severity'] ?? 'medium';
                $breakdown[$severity]++;
            }
        }

        return $breakdown;
    }
}
