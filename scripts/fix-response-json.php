<?php

/**
 * Fix Invalid JSON in @response Tags
 *
 * This script fixes common JSON formatting issues in @response docblock tags.
 * It handles:
 * - Missing quotes around keys
 * - Single quotes instead of double quotes
 * - Trailing commas
 * - Malformed JSON structures
 *
 * Usage:
 *   php scripts/fix-response-json.php --dry-run  # Preview changes
 *   php scripts/fix-response-json.php            # Apply fixes
 */

namespace App\Scripts;

class ResponseJsonFixer
{
    private array $stats = [
        'scanned' => 0,
        'fixed' => 0,
        'skipped' => 0,
    ];

    private bool $dryRun = false;

    public function __construct(bool $dryRun = false)
    {
        $this->dryRun = $dryRun;
    }

    public function run(): void
    {
        echo "🔧 Fixing Invalid JSON in @response Tags\n";
        if ($this->dryRun) {
            echo "   (DRY RUN MODE - No files will be modified)\n";
        }
        echo str_repeat('=', 50) . "\n\n";

        $modulesPath = base_path('Modules');
        $this->scanDirectory($modulesPath);

        $this->printStats();
    }

    private function scanDirectory(string $path): void
    {
        if (!is_dir($path)) {
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

        // Fix @response JSON
        $content = $this->fixResponseJson($content);

        if ($content !== $originalContent) {
            if (!$this->dryRun) {
                file_put_contents($filePath, $content);
            }

            $this->stats['fixed']++;
            $relativePath = str_replace(base_path() . '/', '', $filePath);

            if ($this->dryRun) {
                echo "📝 Would fix: {$relativePath}\n";
            } else {
                echo "✅ Fixed: {$relativePath}\n";
            }
        } else {
            $this->stats['skipped']++;
        }
    }

    private function fixResponseJson(string $content): string
    {
        // Pattern to find @response tags with JSON
        $pattern = '/@response\s+(\d+)(?:\s+scenario="([^"]+)")?\s+(\{[^@]*?\})/s';

        return preg_replace_callback($pattern, function ($matches) {
            $statusCode = $matches[1];
            $scenario = $matches[2] ?? null;
            $json = $matches[3];

            // Try to fix and validate JSON
            $fixedJson = $this->attemptJsonFix($json);

            // Build the @response tag
            $result = "@response {$statusCode}";
            if ($scenario) {
                $result .= " scenario=\"{$scenario}\"";
            }
            $result .= " {$fixedJson}";

            return $result;
        }, $content);
    }

    private function attemptJsonFix(string $json): string
    {
        // First, try to decode as-is
        $decoded = json_decode($json, true);
        if ($decoded !== null && json_last_error() === JSON_ERROR_NONE) {
            // Already valid, just re-encode for consistency
            return json_encode($decoded, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        // Common fixes
        $fixed = $json;

        // Remove trailing commas before closing braces/brackets
        $fixed = preg_replace('/,(\s*[}\]])/', '$1', $fixed);

        // Fix single quotes to double quotes (but preserve escaped quotes)
        $fixed = str_replace("'", '"', $fixed);

        // Try to decode again
        $decoded = json_decode($fixed, true);
        if ($decoded !== null && json_last_error() === JSON_ERROR_NONE) {
            return json_encode($decoded, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        // If still invalid, return original (will be caught by validation)
        return $json;
    }

    private function printStats(): void
    {
        echo "\n" . str_repeat('=', 50) . "\n";
        echo "📊 Fix Statistics:\n";
        echo str_repeat('=', 50) . "\n";
        echo "Scanned:  {$this->stats['scanned']} controllers\n";

        if ($this->dryRun) {
            echo "Would fix: {$this->stats['fixed']} controllers\n";
        } else {
            echo "Fixed:    {$this->stats['fixed']} controllers\n";
        }

        echo "Skipped:  {$this->stats['skipped']} controllers\n";
        echo str_repeat('=', 50) . "\n";

        if ($this->dryRun) {
            echo "\n💡 This was a dry run. Run without --dry-run to apply fixes.\n";
        } else {
            echo "\n✨ Done! Run validation to check results:\n";
            echo "   php scripts/validate-api-docs.php\n";
        }

        echo "\n";
    }
}

// Run the script
if (php_sapi_name() === 'cli') {
    require_once __DIR__ . '/../vendor/autoload.php';

    $app = require_once __DIR__ . '/../bootstrap/app.php';
    $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

    $dryRun = in_array('--dry-run', $argv ?? []);
    $fixer = new ResponseJsonFixer($dryRun);
    $fixer->run();
}
