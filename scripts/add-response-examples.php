<?php

/**
 * Add Missing @response Examples
 *
 * Auto-generates @response examples for endpoints that don't have them.
 * Generates responses based on HTTP method, route patterns, and existing docblock info.
 *
 * Usage:
 *   php scripts/add-response-examples.php --dry-run  # Preview
 *   php scripts/add-response-examples.php            # Apply
 */

namespace App\Scripts;

class ResponseExampleGenerator
{
    private array $stats = [
        'scanned' => 0,
        'enhanced' => 0,
        'skipped' => 0,
    ];

    private bool $dryRun = false;

    public function __construct(bool $dryRun = false)
    {
        $this->dryRun = $dryRun;
    }

    public function run(): void
    {
        echo "📝 Adding Missing @response Examples\n";
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

        // Add @response examples
        $content = $this->addResponseExamples($content, $filePath);

        if ($content !== $originalContent) {
            if (!$this->dryRun) {
                file_put_contents($filePath, $content);
            }

            $this->stats['enhanced']++;
            $relativePath = str_replace(base_path() . '/', '', $filePath);

            if ($this->dryRun) {
                echo "📝 Would enhance: {$relativePath}\n";
            } else {
                echo "✅ Enhanced: {$relativePath}\n";
            }
        } else {
            $this->stats['skipped']++;
        }
    }

    private function addResponseExamples(string $content, string $filePath): string
    {
        $pattern = '/\/\*\*\s*\n((?:\s*\*.*\n)*?)\s*\*\/\s*\n(\s*)public function (\w+)\(/';

        return preg_replace_callback($pattern, function ($matches) use ($content, $filePath) {
            $docblock = $matches[1];
            $indent = $matches[2];
            $methodName = $matches[3];

            // Skip if already has @response
            if (str_contains($docblock, '@response')) {
                return $matches[0];
            }

            // Skip magic methods
            if (str_starts_with($methodName, '__')) {
                return $matches[0];
            }

            // Detect HTTP method from route or method name
            $httpMethod = $this->detectHttpMethod($methodName, $content);
            
            // Check if authenticated
            $isAuthenticated = str_contains($docblock, '@authenticated');
            $hasRole = str_contains($docblock, '@role');

            // Generate responses
            $responses = $this->generateResponses($methodName, $httpMethod, $isAuthenticated, $hasRole, $filePath);

            if (empty($responses)) {
                return $matches[0];
            }

            // Find where to insert (before @authenticated or at end)
            $lines = explode("\n", $docblock);
            $insertIndex = $this->findResponseInsertIndex($lines);

            // Add blank line before responses
            array_splice($lines, $insertIndex, 0, array_merge(["     *"], $responses));

            $newDocblock = implode("\n", $lines);

            return "/**\n{$newDocblock}     */\n{$indent}public function {$methodName}(";
        }, $content);
    }

    private function detectHttpMethod(string $methodName, string $content): string
    {
        // Common method name patterns
        $patterns = [
            'index' => 'GET',
            'show' => 'GET',
            'store' => 'POST',
            'create' => 'GET',
            'update' => 'PUT',
            'destroy' => 'DELETE',
            'delete' => 'DELETE',
        ];

        foreach ($patterns as $pattern => $method) {
            if (str_contains(strtolower($methodName), $pattern)) {
                return $method;
            }
        }

        return 'GET'; // Default
    }

    private function generateResponses(string $methodName, string $httpMethod, bool $isAuthenticated, bool $hasRole, string $filePath): array
    {
        $responses = [];
        $resourceName = $this->extractResourceName($filePath);

        // Success response
        switch ($httpMethod) {
            case 'GET':
                if (str_contains($methodName, 'index') || str_contains($methodName, 'list')) {
                    // List response with pagination
                    $responses[] = "     * @response 200 scenario=\"Success\" {\"success\":true,\"message\":\"Success\",\"data\":[{\"id\":1,\"name\":\"Example {$resourceName}\"}],\"meta\":{\"current_page\":1,\"last_page\":5,\"per_page\":15,\"total\":75},\"links\":{\"first\":\"...\",\"last\":\"...\",\"prev\":null,\"next\":\"...\"}}";
                } else {
                    // Single resource
                    $responses[] = "     * @response 200 scenario=\"Success\" {\"success\":true,\"message\":\"Success\",\"data\":{\"id\":1,\"name\":\"Example {$resourceName}\"}}";
                }
                break;

            case 'POST':
                $responses[] = "     * @response 201 scenario=\"Success\" {\"success\":true,\"message\":\"{$resourceName} berhasil dibuat.\",\"data\":{\"id\":1,\"name\":\"New {$resourceName}\"}}";
                break;

            case 'PUT':
            case 'PATCH':
                $responses[] = "     * @response 200 scenario=\"Success\" {\"success\":true,\"message\":\"{$resourceName} berhasil diperbarui.\",\"data\":{\"id\":1,\"name\":\"Updated {$resourceName}\"}}";
                break;

            case 'DELETE':
                $responses[] = "     * @response 200 scenario=\"Success\" {\"success\":true,\"message\":\"{$resourceName} berhasil dihapus.\",\"data\":[]}";
                break;
        }

        // Error responses
        if ($isAuthenticated) {
            $responses[] = "     * @response 401 scenario=\"Unauthorized\" {\"success\":false,\"message\":\"Tidak terotorisasi.\"}";
        }

        if ($hasRole) {
            $responses[] = "     * @response 403 scenario=\"Forbidden\" {\"success\":false,\"message\":\"Anda tidak memiliki akses.\"}";
        }

        if (in_array($methodName, ['show', 'update', 'destroy', 'delete'])) {
            $responses[] = "     * @response 404 scenario=\"Not Found\" {\"success\":false,\"message\":\"{$resourceName} tidak ditemukan.\"}";
        }

        if (in_array($httpMethod, ['POST', 'PUT', 'PATCH'])) {
            $responses[] = "     * @response 422 scenario=\"Validation Error\" {\"success\":false,\"message\":\"Validasi gagal.\",\"errors\":{\"field\":[\"Field wajib diisi.\"]}}";
        }

        return $responses;
    }

    private function extractResourceName(string $filePath): string
    {
        // Extract from controller name
        if (preg_match('/(\w+)Controller\.php$/', $filePath, $matches)) {
            $name = $matches[1];
            
            // Convert to readable name
            $readable = [
                'Auth' => 'User',
                'Course' => 'Kursus',
                'Lesson' => 'Pelajaran',
                'Unit' => 'Unit',
                'Enrollment' => 'Pendaftaran',
                'Forum' => 'Forum',
                'Thread' => 'Thread',
                'Reply' => 'Balasan',
                'Gamification' => 'Gamifikasi',
                'Challenge' => 'Tantangan',
                'Badge' => 'Badge',
                'Leaderboard' => 'Leaderboard',
                'Assignment' => 'Tugas',
                'Submission' => 'Pengumpulan',
                'News' => 'Berita',
                'Announcement' => 'Pengumuman',
                'Content' => 'Konten',
                'Notification' => 'Notifikasi',
                'Profile' => 'Profil',
                'Category' => 'Kategori',
            ];

            return $readable[$name] ?? $name;
        }

        return 'Resource';
    }

    private function findResponseInsertIndex(array $lines): int
    {
        // Insert before @authenticated or at end
        foreach ($lines as $index => $line) {
            if (str_contains($line, '@authenticated') || str_contains($line, '@unauthenticated')) {
                return $index;
            }
        }

        return count($lines);
    }

    private function printStats(): void
    {
        echo "\n" . str_repeat('=', 50) . "\n";
        echo "📊 Enhancement Statistics:\n";
        echo str_repeat('=', 50) . "\n";
        echo "Scanned:  {$this->stats['scanned']} controllers\n";

        if ($this->dryRun) {
            echo "Would enhance: {$this->stats['enhanced']} controllers\n";
        } else {
            echo "Enhanced: {$this->stats['enhanced']} controllers\n";
        }

        echo "Skipped:  {$this->stats['skipped']} controllers\n";
        echo str_repeat('=', 50) . "\n";

        if ($this->dryRun) {
            echo "\n💡 This was a dry run. Run without --dry-run to apply.\n";
        } else {
            echo "\n✨ Done! Validate results:\n";
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
    $generator = new ResponseExampleGenerator($dryRun);
    $generator->run();
}
