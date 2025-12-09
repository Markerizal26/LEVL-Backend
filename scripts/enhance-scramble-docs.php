<?php

/**
 * Scramble API Documentation Auto-Enhancement Script
 *
 * Automatically enhances controller docblocks with Scramble-specific tags:
 * - @summary (from method name or first line)
 * - @bodyParam (from FormRequest validation rules)
 * - @queryParam (from inline validation or route params)
 * - Improved @response format with scenarios
 *
 * Usage:
 *   php scripts/enhance-scramble-docs.php --dry-run  # Preview changes
 *   php scripts/enhance-scramble-docs.php            # Apply changes
 */

namespace App\Scripts;

class ScrambleDocsEnhancer
{
    private array $stats = [
        'scanned' => 0,
        'enhanced' => 0,
        'skipped' => 0,
    ];

    private bool $dryRun = false;

    private array $methodTitleMap = [
        'index' => 'Daftar Data',
        'show' => 'Detail Data',
        'store' => 'Buat Baru',
        'create' => 'Form Buat Baru',
        'update' => 'Perbarui Data',
        'edit' => 'Form Edit',
        'destroy' => 'Hapus Data',
        'delete' => 'Hapus Data',
    ];

    public function __construct(bool $dryRun = false)
    {
        $this->dryRun = $dryRun;
    }

    public function run(): void
    {
        echo "🚀 Scramble API Documentation Enhancement\n";
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

        // Add @summary tags
        $content = $this->addSummaryTags($content, $filePath);

        // Add pagination @queryParam if method uses pagination
        $content = $this->addPaginationParams($content);

        // Add filter/sort documentation improvements
        $content = $this->improveFilterSortDocs($content);

        // Add @bodyParam from inline validation
        $content = $this->addBodyParams($content, $filePath);

        // Improve @response format
        $content = $this->improveResponseFormat($content);

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

    private function addSummaryTags(string $content, string $filePath): string
    {
        $pattern = '/\/\*\*\s*\n((?:\s*\*.*\n)*?)\s*\*\/\s*\n(\s*)public function (\w+)\(/';

        return preg_replace_callback($pattern, function ($matches) use ($filePath) {
            $docblock = $matches[1];
            $indent = $matches[2];
            $methodName = $matches[3];

            // Skip if already has @summary
            if (str_contains($docblock, '@summary')) {
                return $matches[0];
            }

            // Skip magic methods
            if (str_starts_with($methodName, '__')) {
                return $matches[0];
            }

            // Generate summary
            $summary = $this->generateSummary($docblock, $methodName);

            // Find where to insert @summary (after description, before @tags/@bodyParam/@response/@authenticated)
            $lines = explode("\n", $docblock);
            $insertIndex = $this->findInsertIndex($lines);

            array_splice($lines, $insertIndex, 0, [
                "     *",
                "     * @summary {$summary}",
            ]);

            $newDocblock = implode("\n", $lines);

            return "/**\n{$newDocblock}     */{$indent}\n{$indent}public function {$methodName}(";
        }, $content);
    }

    private function generateSummary(string $docblock, string $methodName): string
    {
        // Try to extract from first line of docblock
        $lines = explode("\n", $docblock);
        foreach ($lines as $line) {
            $line = trim($line, " \t\n\r\0\x0B*");
            if (!empty($line) && !str_starts_with($line, '@')) {
                // Limit to 60 chars for Scramble sidebar
                return mb_substr($line, 0, 60);
            }
        }

        // Fallback: convert method name to title
        return $this->methodNameToTitle($methodName);
    }

    private function methodNameToTitle(string $methodName): string
    {
        // Check predefined map
        if (isset($this->methodTitleMap[$methodName])) {
            return $this->methodTitleMap[$methodName];
        }

        // Convert camelCase to Title Case
        $title = preg_replace('/([A-Z])/', ' $1', $methodName);
        $title = ucfirst(trim($title));

        return $title;
    }

    private function findInsertIndex(array $lines): int
    {
        // Find first line with @ tag
        foreach ($lines as $index => $line) {
            if (str_contains($line, '@')) {
                return $index;
            }
        }

        // If no @ tags, insert before closing
        return count($lines);
    }

    private function addBodyParams(string $content, string $filePath): string
    {
        // Find methods with inline validation
        $pattern = '/\/\*\*\s*\n((?:\s*\*.*\n)*?)\s*\*\/\s*\n(\s*)public function (\w+)\([^)]*Request \$request[^)]*\)\s*(?::\s*\w+)?\s*\{([^}]*\$request->validate\(\[(.*?)\]\);)/s';

        $result = preg_replace_callback($pattern, function ($matches) {
            $docblock = $matches[1];
            $indent = $matches[2];
            $methodName = $matches[3];
            $validationRules = $matches[5];

            // Skip if already has @bodyParam
            if (str_contains($docblock, '@bodyParam')) {
                return $matches[0];
            }

            // Parse validation rules
            $params = $this->parseValidationRules($validationRules);

            if (empty($params)) {
                return $matches[0];
            }

            // Find where to insert @bodyParam (after @summary, before @response)
            $lines = explode("\n", $docblock);
            $insertIndex = $this->findBodyParamInsertIndex($lines);

            $bodyParamLines = ["     *"];
            foreach ($params as $param) {
                $required = $param['required'] ? 'required' : 'optional';
                $bodyParamLines[] = "     * @bodyParam {$param['field']} {$param['type']} {$required} {$param['description']}";
            }

            array_splice($lines, $insertIndex, 0, $bodyParamLines);

            $newDocblock = implode("\n", $lines);

            return "/**\n{$newDocblock}     */{$indent}\n{$indent}public function {$methodName}(" . substr($matches[0], strpos($matches[0], 'Request'));
        }, $content);

        // Return original content if no matches found
        return $result ?? $content;
    }

    private function addPaginationParams(string $content): string
    {
        // Find methods that use pagination - simpler approach
        $pattern = '/\/\*\*\s*\n((?:\s*\*.*\n)*?)\s*\*\/\s*\n(\s*)public function (\w+)\(/';

        return preg_replace_callback($pattern, function ($matches) use ($content) {
            $docblock = $matches[1];
            $indent = $matches[2];
            $methodName = $matches[3];

            // Skip if already has pagination @queryParam
            if (str_contains($docblock, '@queryParam per_page') || str_contains($docblock, '@queryParam page')) {
                return $matches[0];
            }

            // Check if method body contains pagination
            // Find the method body
            $methodStart = strpos($content, $matches[0]);
            if ($methodStart === false) {
                return $matches[0];
            }

            // Get next 1000 characters to check for pagination
            $methodBody = substr($content, $methodStart, 1000);
            
            // Check if uses pagination
            if (!preg_match('/\b(paginate|simplePaginate|cursorPaginate)\b/', $methodBody)) {
                return $matches[0];
            }

            // Find where to insert (after @allowedSorts, before @response)
            $lines = explode("\n", $docblock);
            $insertIndex = $this->findQueryParamInsertIndex($lines);

            $paginationParams = [
                "     *",
                "     * @queryParam page integer Halaman yang ingin ditampilkan. Example: 1",
                "     * @queryParam per_page integer Jumlah item per halaman (default: 15, max: 100). Example: 15",
            ];

            array_splice($lines, $insertIndex, 0, $paginationParams);

            $newDocblock = implode("\n", $lines);

            return "/**\n{$newDocblock}     */\n{$indent}public function {$methodName}(";
        }, $content);
    }

    private function improveFilterSortDocs(string $content): string
    {
        // Improve @allowedFilters documentation
        $content = preg_replace_callback(
            '/@allowedFilters\s+([^\n]+)/',
            function ($matches) {
                $filters = array_map('trim', explode(',', $matches[1]));
                $filterDocs = [];

                foreach ($filters as $filter) {
                    // Clean filter name (remove filter[] wrapper if exists)
                    $cleanFilter = str_replace(['filter[', ']'], '', $filter);
                    $description = $this->getFilterDescription($cleanFilter);
                    $filterDocs[] = "     * @queryParam {$filter} string Filter berdasarkan {$description}. Example: ";
                }

                // Keep original @allowedFilters and add @queryParam docs
                return $matches[0] . "\n     *\n" . implode("\n", $filterDocs);
            },
            $content
        );

        // Improve @allowedSorts documentation  
        $content = preg_replace_callback(
            '/@allowedSorts\s+([^\n]+)/',
            function ($matches) {
                $sorts = array_map('trim', explode(',', $matches[1]));
                $sortList = implode(', ', $sorts);

                // Add @queryParam for sort
                return $matches[0] . "\n     *\n     * @queryParam sort string Field untuk sorting. Allowed: {$sortList}. Prefix dengan '-' untuk descending. Example: -created_at";
            },
            $content
        );

        return $content;
    }

    private function getFilterDescription(string $filter): string
    {
        $descriptions = [
            'search' => 'pencarian (nama, email, dll)',
            'status' => 'status',
            'role' => 'role pengguna',
            'course_id' => 'ID kursus',
            'user_id' => 'ID pengguna',
            'category_id' => 'ID kategori',
            'tag_id' => 'ID tag',
            'tag' => 'tag',
            'priority' => 'prioritas',
            'unread' => 'status belum dibaca',
            'featured' => 'status featured',
            'type' => 'tipe',
            'level_tag' => 'level tag',
            'created_from' => 'tanggal dibuat (dari)',
            'created_to' => 'tanggal dibuat (sampai)',
            'date_from' => 'tanggal (dari)',
            'date_to' => 'tanggal (sampai)',
            'enrollment_date' => 'tanggal pendaftaran',
            'completion_date' => 'tanggal selesai',
            'content_type' => 'tipe konten',
            'is_pinned' => 'status di-pin',
            'is_solved' => 'status terjawab',
            'is_locked' => 'status terkunci',
        ];

        return $descriptions[$filter] ?? $filter;
    }

    private function findQueryParamInsertIndex(array $lines): int
    {
        // Insert after @allowedSorts or @allowedFilters, before @response
        $lastFilterSortIndex = -1;

        foreach ($lines as $index => $line) {
            if (str_contains($line, '@allowedSorts') || str_contains($line, '@allowedFilters')) {
                $lastFilterSortIndex = $index;
            }
            if (str_contains($line, '@response')) {
                return $lastFilterSortIndex > -1 ? $lastFilterSortIndex + 1 : $index;
            }
        }

        return count($lines);
    }

    private function parseValidationRules(string $rulesContent): array
    {
        $params = [];

        // Match 'field' => ['rules']
        preg_match_all("/'(\w+)'\s*=>\s*\[(.*?)\]/s", $rulesContent, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $field = $match[1];
            $rules = $match[2];

            $type = $this->inferType($rules);
            $required = str_contains($rules, 'required');
            $description = $this->generateFieldDescription($field, $rules);

            $params[] = [
                'field' => $field,
                'type' => $type,
                'required' => $required,
                'description' => $description,
            ];
        }

        return $params;
    }

    private function inferType(string $rules): string
    {
        if (str_contains($rules, 'integer') || str_contains($rules, 'numeric')) {
            return 'integer';
        }
        if (str_contains($rules, 'boolean')) {
            return 'boolean';
        }
        if (str_contains($rules, 'array')) {
            return 'array';
        }
        if (str_contains($rules, 'file') || str_contains($rules, 'image')) {
            return 'file';
        }

        return 'string';
    }

    private function generateFieldDescription(string $field, string $rules): string
    {
        // Convert field name to readable description
        $description = ucfirst(str_replace('_', ' ', $field));

        // Add specific info based on rules
        if (str_contains($rules, 'email')) {
            $description .= ' (format email)';
        }
        if (str_contains($rules, 'max:')) {
            preg_match('/max:(\d+)/', $rules, $matches);
            if (!empty($matches[1])) {
                $description .= " (max {$matches[1]} karakter)";
            }
        }
        if (str_contains($rules, 'min:')) {
            preg_match('/min:(\d+)/', $rules, $matches);
            if (!empty($matches[1])) {
                $description .= " (min {$matches[1]} karakter)";
            }
        }

        return $description . '.';
    }

    private function findBodyParamInsertIndex(array $lines): int
    {
        // Insert after @summary, before @response/@authenticated
        foreach ($lines as $index => $line) {
            if (str_contains($line, '@response') || str_contains($line, '@authenticated')) {
                return $index;
            }
        }

        return count($lines);
    }

    private function improveResponseFormat(string $content): string
    {
        // Add scenario to @response if missing
        $pattern = '/@response\s+(\d+)\s+(\{.+?\})/s';

        return preg_replace_callback($pattern, function ($matches) {
            $statusCode = $matches[1];
            $json = $matches[2];

            // Skip if already has scenario
            if (str_contains($matches[0], 'scenario=')) {
                return $matches[0];
            }

            // Determine scenario from status code
            $scenario = $this->getScenarioFromStatusCode($statusCode);

            return "@response {$statusCode} scenario=\"{$scenario}\" {$json}";
        }, $content);
    }

    private function getScenarioFromStatusCode(string $code): string
    {
        $scenarios = [
            '200' => 'Success',
            '201' => 'Created',
            '204' => 'No Content',
            '400' => 'Bad Request',
            '401' => 'Unauthorized',
            '403' => 'Forbidden',
            '404' => 'Not Found',
            '422' => 'Validation Error',
            '429' => 'Rate Limited',
            '500' => 'Server Error',
        ];

        return $scenarios[$code] ?? 'Response';
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
            echo "\n💡 This was a dry run. Run without --dry-run to apply changes.\n";
        } else {
            echo "\n✨ Done! Next steps:\n";
            echo "1. Review changes: git diff\n";
            echo "2. Validate: php scripts/validate-api-docs.php\n";
            echo "3. Generate Scramble docs: php artisan scramble:export\n";
            echo "4. Check UI: http://localhost:8000/docs/api\n";
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
    $enhancer = new ScrambleDocsEnhancer($dryRun);
    $enhancer->run();
}
