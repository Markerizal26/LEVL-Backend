<?php

namespace App\Support;

use ReflectionClass;

class RepositoryGenerator
{
    /**
     * Generate repository class for a model
     */
    public function generateRepository(string $modelClass): string
    {
        $reflection = new ReflectionClass($modelClass);

        $modelNamespace = $reflection->getNamespaceName();
        $modelName = $reflection->getShortName();

        $repositoryNamespace = str_replace('\\Models', '\\Repositories', $modelNamespace);
        $repositoryName = $modelName.'Repository';
        $interfaceName = $modelName.'RepositoryInterface';

        $content = "<?php\n\n";
        $content .= "namespace {$repositoryNamespace};\n\n";
        $content .= "use {$modelClass};\n";
        $content .= 'use '.str_replace('\\Repositories', '\\Contracts\\Repositories', $repositoryNamespace)."\\{$interfaceName};\n";
        $content .= "use Illuminate\\Contracts\\Pagination\\LengthAwarePaginator;\n\n";

        $content .= "class {$repositoryName} implements {$interfaceName}\n";
        $content .= "{\n";

        // findById method
        $content .= "    /**\n";
        $content .= "     * Find {$modelName} by ID\n";
        $content .= "     */\n";
        $content .= "    public function findById(int \$id): ?{$modelName}\n";
        $content .= "    {\n";
        $content .= "        return {$modelName}::find(\$id);\n";
        $content .= "    }\n\n";

        // findByIdOrFail method
        $content .= "    /**\n";
        $content .= "     * Find {$modelName} by ID or fail\n";
        $content .= "     */\n";
        $content .= "    public function findByIdOrFail(int \$id): {$modelName}\n";
        $content .= "    {\n";
        $content .= "        return {$modelName}::findOrFail(\$id);\n";
        $content .= "    }\n\n";

        // create method
        $content .= "    /**\n";
        $content .= "     * Create a new {$modelName}\n";
        $content .= "     */\n";
        $content .= "    public function create(array \$data): {$modelName}\n";
        $content .= "    {\n";
        $content .= "        return {$modelName}::create(\$data);\n";
        $content .= "    }\n\n";

        // update method
        $content .= "    /**\n";
        $content .= "     * Update {$modelName}\n";
        $content .= "     */\n";
        $content .= "    public function update({$modelName} \${$this->camelCase($modelName)}, array \$data): {$modelName}\n";
        $content .= "    {\n";
        $content .= "        \${$this->camelCase($modelName)}->update(\$data);\n";
        $content .= "        return \${$this->camelCase($modelName)}->fresh();\n";
        $content .= "    }\n\n";

        // delete method
        $content .= "    /**\n";
        $content .= "     * Delete {$modelName}\n";
        $content .= "     */\n";
        $content .= "    public function delete({$modelName} \${$this->camelCase($modelName)}): bool\n";
        $content .= "    {\n";
        $content .= "        return \${$this->camelCase($modelName)}->delete();\n";
        $content .= "    }\n\n";

        // paginate method
        $content .= "    /**\n";
        $content .= "     * Paginate {$modelName} records\n";
        $content .= "     */\n";
        $content .= "    public function paginate(int \$perPage = 15): LengthAwarePaginator\n";
        $content .= "    {\n";
        $content .= "        return {$modelName}::paginate(\$perPage);\n";
        $content .= "    }\n";

        $content .= "}\n";

        return $content;
    }

    /**
     * Generate repository interface for a model
     */
    public function generateRepositoryInterface(string $modelClass): string
    {
        $reflection = new ReflectionClass($modelClass);

        $modelNamespace = $reflection->getNamespaceName();
        $modelName = $reflection->getShortName();

        $interfaceNamespace = str_replace('\\Models', '\\Contracts\\Repositories', $modelNamespace);
        $interfaceName = $modelName.'RepositoryInterface';

        $content = "<?php\n\n";
        $content .= "namespace {$interfaceNamespace};\n\n";
        $content .= "use {$modelClass};\n";
        $content .= "use Illuminate\\Contracts\\Pagination\\LengthAwarePaginator;\n\n";

        $content .= "interface {$interfaceName}\n";
        $content .= "{\n";

        // findById method
        $content .= "    /**\n";
        $content .= "     * Find {$modelName} by ID\n";
        $content .= "     */\n";
        $content .= "    public function findById(int \$id): ?{$modelName};\n\n";

        // findByIdOrFail method
        $content .= "    /**\n";
        $content .= "     * Find {$modelName} by ID or fail\n";
        $content .= "     */\n";
        $content .= "    public function findByIdOrFail(int \$id): {$modelName};\n\n";

        // create method
        $content .= "    /**\n";
        $content .= "     * Create a new {$modelName}\n";
        $content .= "     */\n";
        $content .= "    public function create(array \$data): {$modelName};\n\n";

        // update method
        $content .= "    /**\n";
        $content .= "     * Update {$modelName}\n";
        $content .= "     */\n";
        $content .= "    public function update({$modelName} \${$this->camelCase($modelName)}, array \$data): {$modelName};\n\n";

        // delete method
        $content .= "    /**\n";
        $content .= "     * Delete {$modelName}\n";
        $content .= "     */\n";
        $content .= "    public function delete({$modelName} \${$this->camelCase($modelName)}): bool;\n\n";

        // paginate method
        $content .= "    /**\n";
        $content .= "     * Paginate {$modelName} records\n";
        $content .= "     */\n";
        $content .= "    public function paginate(int \$perPage = 15): LengthAwarePaginator;\n";

        $content .= "}\n";

        return $content;
    }

    /**
     * Write repository to file
     */
    public function writeRepository(string $path, string $content): void
    {
        $directory = dirname($path);

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        file_put_contents($path, $content);
    }

    /**
     * Convert string to camelCase
     */
    private function camelCase(string $string): string
    {
        return lcfirst($string);
    }
}
