<?php

namespace App\Models;

class ArchitectureViolation
{
    public string $type;

    public string $file;

    public int $line;

    public string $description;

    public string $suggestion;

    public string $severity;

    public array $context;

    public function __construct(array $data)
    {
        $this->type = $data['type'] ?? '';
        $this->file = $data['file'] ?? '';
        $this->line = $data['line'] ?? 0;
        $this->description = $data['description'] ?? '';
        $this->suggestion = $data['suggestion'] ?? '';
        $this->severity = $data['severity'] ?? 'medium';
        $this->context = $data['context'] ?? [];
    }

    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'file' => $this->file,
            'line' => $this->line,
            'description' => $this->description,
            'suggestion' => $this->suggestion,
            'severity' => $this->severity,
            'context' => $this->context,
        ];
    }

    public function isCritical(): bool
    {
        return $this->severity === 'critical';
    }

    public function isHigh(): bool
    {
        return $this->severity === 'high';
    }

    public function getRelativePath(): string
    {
        return str_replace(base_path().'/', '', $this->file);
    }
}
