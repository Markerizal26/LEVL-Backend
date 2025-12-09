<?php

/**
 * API Documentation Auto-Enhancer Script
 *
 * This script automatically adds @authenticated tags and basic documentation
 * to all controller methods that need them.
 *
 * Usage: php scripts/enhance-api-docs.php
 */

namespace App\Scripts;

class ApiDocsEnhancer
{
    private array $stats = [
        'scanned' => 0,
        'enhanced' => 0,
        'skipped' => 0,
        'errors' => 0,
    ];

    private array $publicMethods = [
        'register',
        'login',
        'refresh',
        'googleRedirect',
        'googleCallback',
        'verifyEmailByCode',
        'verifyEmailByToken',
    ];

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

    public function run(): void
    {
        echo "🚀 Starting API Documentation Enhancement...\n\n";

        $modulesPath = base_path('Modules');
        $appPath = app_path('Http/Controllers/Api');

        // Scan Modules
        $this->scanDirectory($modulesPath);

        // Scan App Controllers
        if (is_dir($appPath)) {
            $this->scanDirectory($appPath);
        }

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

                // Only process Controllers
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

        // Skip if file is too small or empty
        if (strlen($content) < 100) {
            $this->stats['skipped']++;

            return;
        }

        // Add class-level @tags if missing
        $content = $this->addClassLevelTags($content, $filePath);

        // Add @authenticated to methods that need it
        $content = $this->addAuthenticatedTags($content, $filePath);

        // Only write if content changed
        if ($content !== $originalContent) {
            file_put_contents($filePath, $content);
            $this->stats['enhanced']++;

            $relativePath = str_replace(base_path().'/', '', $filePath);
            echo "✅ Enhanced: {$relativePath}\n";
        } else {
            $this->stats['skipped']++;
        }
    }

    private function addAuthenticatedTags(string $content, string $filePath): string
    {
        // Pattern to find docblocks without @authenticated or @unauthenticated
        $pattern = '/\/\*\*\s*\n((?:\s*\*.*\n)*?)\s*\*\/\s*\n\s*public function (\w+)\(/';

        $content = preg_replace_callback($pattern, function ($matches) use ($filePath) {
            $docblock = $matches[1];
            $methodName = $matches[2];

            // Skip if already has @authenticated or @unauthenticated
            if (strpos($docblock, '@authenticated') !== false ||
                strpos($docblock, '@unauthenticated') !== false) {
                return $matches[0];
            }

            // Skip magic methods and non-route methods
            if (strpos($methodName, '__') === 0 ||
                in_array($methodName, ['middleware', 'authorize'])) {
                return $matches[0];
            }

            // Determine if method should be public or authenticated
            $isPublic = in_array($methodName, $this->publicMethods) ||
                        strpos($filePath, 'PublicProfileController') !== false;

            // Add appropriate tag
            $tag = $isPublic ? '@unauthenticated' : '@authenticated';

            // Add tag before closing */
            $newDocblock = rtrim($docblock)."\n     *\n     * {$tag}\n";

            return "/**\n{$newDocblock}     */\n    public function {$methodName}(";
        }, $content);

        return $content;
    }

    private function addClassLevelTags(string $content, string $filePath): string
    {
        // Extract module name from path
        $moduleName = $this->extractModuleName($filePath);
        $moduleTag = $this->moduleNameMap[$moduleName] ?? $moduleName;

        // Pattern to find class-level docblock
        $pattern = '/\/\*\*\s*\n((?:\s*\*.*\n)*?)\s*\*\/\s*\nclass/';

        $content = preg_replace_callback($pattern, function ($matches) use ($moduleTag) {
            $docblock = $matches[1];

            // Skip if already has @tags
            if (strpos($docblock, '@tags') !== false) {
                return $matches[0];
            }

            // Add @tags
            $newDocblock = rtrim($docblock) . "\n * @tags {$moduleTag}\n";

            return "/**\n{$newDocblock} */\nclass";
        }, $content);

        return $content;
    }

    private function extractModuleName(string $filePath): string
    {
        if (preg_match('/Modules\/(\w+)\//', $filePath, $matches)) {
            return $matches[1];
        }

        return 'App';
    }

    private function printStats(): void
    {
        echo "\n".str_repeat('=', 50)."\n";
        echo "📊 Enhancement Statistics:\n";
        echo str_repeat('=', 50)."\n";
        echo "Scanned:  {$this->stats['scanned']} controllers\n";
        echo "Enhanced: {$this->stats['enhanced']} controllers\n";
        echo "Skipped:  {$this->stats['skipped']} controllers\n";
        echo "Errors:   {$this->stats['errors']} controllers\n";
        echo str_repeat('=', 50)."\n";
        echo "\n✨ Done! Run 'php artisan scramble:export' to verify.\n";
    }
}

// Run the script
if (php_sapi_name() === 'cli') {
    require_once __DIR__.'/../vendor/autoload.php';

    $app = require_once __DIR__.'/../bootstrap/app.php';
    $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

    $enhancer = new ApiDocsEnhancer;
    $enhancer->run();
}
