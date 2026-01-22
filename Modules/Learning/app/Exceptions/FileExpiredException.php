<?php

declare(strict_types=1);

namespace Modules\Learning\Exceptions;

use Exception;

class FileExpiredException extends Exception
{
    /**
     * @param  array<array{name: string, size: int, type: string, uploaded_at: string}>|null  $fileMetadata
     */
    public function __construct(
        string $message = 'The requested file has expired and is no longer available.',
        private ?array $fileMetadata = null
    ) {
        parent::__construct($message);
    }

    /**
     * Get the preserved file metadata.
     *
     * @return array<array{name: string, size: int, type: string, uploaded_at: string}>|null
     */
    public function getFileMetadata(): ?array
    {
        return $this->fileMetadata;
    }
}
