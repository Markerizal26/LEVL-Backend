<?php

namespace App\Support;

use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionUnionType;

class InterfaceGenerator
{
    /**
     * Generate service interface from service class
     */
    public function generateServiceInterface(string $serviceClass): string
    {
        $reflection = new ReflectionClass($serviceClass);

        $namespace = $reflection->getNamespaceName();
        $interfaceNamespace = str_replace('\\Services', '\\Contracts\\Services', $namespace);
        $interfaceName = $reflection->getShortName().'Interface';

        $methods = $this->extractPublicMethods($reflection);

        return $this->buildInterfaceContent($interfaceNamespace, $interfaceName, $methods, $reflection);
    }

    /**
     * Generate repository interface from repository class
     */
    public function generateRepositoryInterface(string $repositoryClass): string
    {
        $reflection = new ReflectionClass($repositoryClass);

        $namespace = $reflection->getNamespaceName();
        $interfaceNamespace = str_replace('\\Repositories', '\\Contracts\\Repositories', $namespace);
        $interfaceName = $reflection->getShortName().'Interface';

        $methods = $this->extractPublicMethods($reflection);

        return $this->buildInterfaceContent($interfaceNamespace, $interfaceName, $methods, $reflection);
    }

    /**
     * Write interface to file
     */
    public function writeInterface(string $path, string $content): void
    {
        $directory = dirname($path);

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        file_put_contents($path, $content);
    }

    /**
     * Extract public methods from class
     */
    private function extractPublicMethods(ReflectionClass $reflection): array
    {
        $methods = [];

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            // Skip constructor and magic methods
            if ($method->isConstructor() || str_starts_with($method->getName(), '__')) {
                continue;
            }

            // Skip methods from parent classes (except interfaces)
            if ($method->getDeclaringClass()->getName() !== $reflection->getName()) {
                continue;
            }

            $methods[] = [
                'name' => $method->getName(),
                'parameters' => $this->extractParameters($method),
                'returnType' => $this->extractReturnType($method),
                'docComment' => $method->getDocComment(),
            ];
        }

        return $methods;
    }

    /**
     * Extract method parameters
     */
    private function extractParameters(ReflectionMethod $method): array
    {
        $parameters = [];

        foreach ($method->getParameters() as $param) {
            $parameters[] = [
                'name' => $param->getName(),
                'type' => $this->getParameterType($param),
                'default' => $param->isDefaultValueAvailable() ? $param->getDefaultValue() : null,
                'hasDefault' => $param->isDefaultValueAvailable(),
                'nullable' => $param->allowsNull(),
            ];
        }

        return $parameters;
    }

    /**
     * Get parameter type as string
     */
    private function getParameterType(ReflectionParameter $param): ?string
    {
        $type = $param->getType();

        if (! $type) {
            return null;
        }

        if ($type instanceof ReflectionNamedType) {
            $typeName = $type->getName();

            return ($type->allowsNull() && $typeName !== 'mixed' ? '?' : '').$typeName;
        }

        if ($type instanceof ReflectionUnionType) {
            $types = array_map(fn ($t) => $t->getName(), $type->getTypes());

            return implode('|', $types);
        }

        return null;
    }

    /**
     * Extract return type
     */
    private function extractReturnType(ReflectionMethod $method): ?string
    {
        $returnType = $method->getReturnType();

        if (! $returnType) {
            return null;
        }

        if ($returnType instanceof ReflectionNamedType) {
            $typeName = $returnType->getName();

            return ($returnType->allowsNull() && $typeName !== 'mixed' ? '?' : '').$typeName;
        }

        if ($returnType instanceof ReflectionUnionType) {
            $types = array_map(fn ($t) => $t->getName(), $returnType->getTypes());

            return implode('|', $types);
        }

        return null;
    }

    /**
     * Build interface content
     */
    private function buildInterfaceContent(
        string $namespace,
        string $interfaceName,
        array $methods,
        ReflectionClass $reflection
    ): string {
        $content = "<?php\n\n";
        $content .= "namespace {$namespace};\n\n";

        // Add use statements for type hints
        $useStatements = $this->extractUseStatements($reflection, $methods);
        if (! empty($useStatements)) {
            foreach ($useStatements as $use) {
                $content .= "use {$use};\n";
            }
            $content .= "\n";
        }

        $content .= "interface {$interfaceName}\n";
        $content .= "{\n";

        foreach ($methods as $method) {
            // Add doc comment if exists
            if ($method['docComment']) {
                $docLines = explode("\n", $method['docComment']);
                foreach ($docLines as $docLine) {
                    $content .= '    '.trim($docLine)."\n";
                }
            }

            $content .= "    public function {$method['name']}(";

            // Add parameters
            $paramStrings = [];
            foreach ($method['parameters'] as $param) {
                $paramStr = '';
                if ($param['type']) {
                    $paramStr .= $param['type'].' ';
                }
                $paramStr .= '$'.$param['name'];
                if ($param['hasDefault']) {
                    $paramStr .= ' = '.$this->formatDefaultValue($param['default']);
                }
                $paramStrings[] = $paramStr;
            }
            $content .= implode(', ', $paramStrings);

            $content .= ')';

            // Add return type
            if ($method['returnType']) {
                $content .= ": {$method['returnType']}";
            }

            $content .= ";\n\n";
        }

        $content .= "}\n";

        return $content;
    }

    /**
     * Extract use statements needed for interface
     */
    private function extractUseStatements(ReflectionClass $reflection, array $methods): array
    {
        $uses = [];

        // Get use statements from original file
        $fileName = $reflection->getFileName();
        if ($fileName) {
            $content = file_get_contents($fileName);
            preg_match_all('/use\s+([^;]+);/', $content, $matches);
            if (! empty($matches[1])) {
                foreach ($matches[1] as $use) {
                    $use = trim($use);
                    // Only include uses that are referenced in method signatures
                    foreach ($methods as $method) {
                        $methodSignature = json_encode($method);
                        $shortName = substr($use, strrpos($use, '\\') + 1);
                        if (str_contains($methodSignature, $shortName)) {
                            $uses[] = $use;
                            break;
                        }
                    }
                }
            }
        }

        return array_unique($uses);
    }

    /**
     * Format default value for output
     */
    private function formatDefaultValue($value): string
    {
        if ($value === null) {
            return 'null';
        }
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if (is_string($value)) {
            return "'".addslashes($value)."'";
        }
        if (is_array($value)) {
            return '[]';
        }

        return (string) $value;
    }
}
