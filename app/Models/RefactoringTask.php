<?php

namespace App\Models;

class RefactoringTask
{
    public string $module;

    public string $type;

    public string $targetFile;

    public array $violations;

    public string $status;

    public ?string $newFile;

    public function __construct(array $data)
    {
        $this->module = $data['module'] ?? '';
        $this->type = $data['type'] ?? '';
        $this->targetFile = $data['targetFile'] ?? '';
        $this->violations = $data['violations'] ?? [];
        $this->status = $data['status'] ?? 'pending';
        $this->newFile = $data['newFile'] ?? null;
    }

    public function toArray(): array
    {
        return [
            'module' => $this->module,
            'type' => $this->type,
            'targetFile' => $this->targetFile,
            'violations' => $this->violations,
            'status' => $this->status,
            'newFile' => $this->newFile,
        ];
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function markInProgress(): void
    {
        $this->status = 'in_progress';
    }

    public function markCompleted(?string $newFile = null): void
    {
        $this->status = 'completed';
        if ($newFile) {
            $this->newFile = $newFile;
        }
    }

    public function getViolationCount(): int
    {
        return count($this->violations);
    }
}
