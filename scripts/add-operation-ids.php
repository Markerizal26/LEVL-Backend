<?php

/**
 * Add @operationId to Controller Methods
 *
 * Auto-generates @operationId tags for Scramble documentation.
 * Format: {Module}{Method}{Resource}
 *
 * Usage:
 *   php scripts/add-operation-ids.php --dry-run  # Preview
 *   php scripts/add-operation-ids.php            # Apply
 */

namespace App\Scripts;

class OperationIdGenerator
{
    private array $stats = [
        'scanned' => 0,
        'updated' => 0,
        'skipped' => 0,
    ];

    private bool $dryRun = false;

    public function __construct(bool $dryRun = false)
    {
        $this->dryRun = $dryRun;
    }

    public function run(): void
    {
        echo "🏷️  Adding @operationId to Controllers\n";
        if ($this->dryRun) {
            echo "   (DRY RUN MODE - No files will be modified)\n";
        }
        echo str_repeat('=', 50)."\n\n";

        $modulesPath = base_path('Modules');
        $this->scanDirectory($modulesPath);

        $this->printStats();
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
                    $this->processController($filePath);
                }
            }
        }
    }

    private function processController(string $filePath): void
    {
        $this->stats['scanned']++;

        $content = file_get_contents($filePath);
        $originalContent = $content;

        $moduleName = $this->extractModuleName($filePath);
        $resourceName = $this->extractResourceName($filePath);

        // Add @operationId to docblocks that don't have it
        $content = $this->addOperationIds($content, $moduleName, $resourceName);

        if ($content !== $originalContent) {
            if (! $this->dryRun) {
                file_put_contents($filePath, $content);
            }

            $this->stats['updated']++;
            $relativePath = str_replace(base_path().'/', '', $filePath);

            if ($this->dryRun) {
                echo "📝 Would update: {$relativePath}\n";
            } else {
                echo "✅ Updated: {$relativePath}\n";
            }
        } else {
            $this->stats['skipped']++;
        }
    }

    private function addOperationIds(string $content, string $moduleName, string $resourceName): string
    {
        // Pattern to find docblocks with @summary but no @operationId
        $pattern = '/\/\*\*\s*\n((?:\s*\*.*\n)*?\s*\*\s*@summary\s+[^\n]+\n)((?:\s*\*[^\/]*\n)*)\s*\*\/\s*\n(\s*)public function (\w+)\(/';

        return preg_replace_callback($pattern, function ($matches) use ($moduleName, $resourceName) {
            $beforeSummary = $matches[1];
            $afterSummary = $matches[2];
            $indent = $matches[3];
            $methodName = $matches[4];

            // Skip if already has @operationId
            if (str_contains($beforeSummary.$afterSummary, '@operationId')) {
                return $matches[0];
            }

            // Skip magic methods
            if (str_starts_with($methodName, '__')) {
                return $matches[0];
            }

            // Generate operationId: ModuleMethodResource
            $operationId = $this->generateOperationId($moduleName, $methodName, $resourceName);

            // Insert @operationId after @summary line
            $summaryLine = trim($beforeSummary);
            $operationIdLine = "     * @operationId {$operationId}\n";

            return "/**\n{$beforeSummary}{$operationIdLine}{$afterSummary}     */\n{$indent}public function {$methodName}(";
        }, $content);
    }

    private function generateOperationId(string $module, string $method, string $resource): string
    {
        // Convert method names to standard API operations
        $methodMap = [
            'index' => 'List',
            'show' => 'Get',
            'store' => 'Create',
            'update' => 'Update',
            'destroy' => 'Delete',
            'publish' => 'Publish',
            'unpublish' => 'Unpublish',
        ];

        $action = $methodMap[$method] ?? ucfirst($method);

        // Format: auth.listUsers, schemes.createCourse
        return strtolower($module).'.'.lcfirst($action).$resource;
    }

    private function extractModuleName(string $filePath): string
    {
        if (preg_match('/Modules\/(\w+)\//', $filePath, $matches)) {
            return $matches[1];
        }

        return 'App';
    }

    private function extractResourceName(string $filePath): string
    {
        if (preg_match('/(\w+)Controller\.php$/', $filePath, $matches)) {
            return $matches[1];
        }

        return 'Resource';
    }

    private function printStats(): void
    {
        echo "\n".str_repeat('=', 50)."\n";
        echo "📊 Statistics:\n";
        echo str_repeat('=', 50)."\n";
        echo "Scanned:  {$this->stats['scanned']} controllers\n";

        if ($this->dryRun) {
            echo "Would update: {$this->stats['updated']} controllers\n";
        } else {
            echo "Updated:  {$this->stats['updated']} controllers\n";
        }

        echo "Skipped:  {$this->stats['skipped']} controllers\n";
        echo str_repeat('=', 50)."\n";

        if ($this->dryRun) {
            echo "\n💡 This was a dry run. Run without --dry-run to apply.\n";
        } else {
            echo "\n✨ Done!\n";
        }

        echo "\n";
    }
}

// Run the script
if (php_sapi_name() === 'cli') {
    require_once __DIR__.'/../vendor/autoload.php';

    $app = require_once __DIR__.'/../bootstrap/app.php';
    $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

    $dryRun = in_array('--dry-run', $argv ?? []);
    $generator = new OperationIdGenerator($dryRun);
    $generator->run();
}
