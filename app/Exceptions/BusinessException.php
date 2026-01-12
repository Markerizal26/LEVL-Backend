<?php

namespace App\Exceptions;

use Exception;

class BusinessException extends Exception
{
    /**
     * @var array<string, array<string>>
     */
    protected array $errors = [];

    /**
     * HTTP status code for this exception.
     */
    protected int $statusCode = 500;

    /**
     * Application-specific error code.
     */
    protected string $errorCode = 'BUSINESS_ERROR';

    /**
     * @param  array<string, array<string>>  $errors
     */
    public function __construct(string $message, array $errors = [], int $code = 422)
    {
        parent::__construct($message, $code);
        $this->errors = $errors;
    }

    /**
     * Get the validation errors.
     *
     * @return array<string, array<string>>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get the HTTP status code for this exception.
     */
    public function getStatusCode(): int
    {
        return $this->statusCode ?? 500;
    }

    /**
     * Get the application-specific error code.
     */
    public function getErrorCode(): string
    {
        return $this->errorCode ?? 'INTERNAL_SERVER_ERROR';
    }
}
