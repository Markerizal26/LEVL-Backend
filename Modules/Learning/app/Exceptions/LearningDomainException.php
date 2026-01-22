<?php

declare(strict_types=1);

namespace Modules\Learning\Exceptions;

use Exception;

class LearningDomainException extends Exception
{
    public function __construct(string $message = "", int $code = 0, \Throwable $previous = null, private array $errors = [])
    {
        parent::__construct($message, $code, $previous);
    }

    public function render($request)
    {
        $status = $this->getCode() ?: 400;
        
        $response = [
            'status' => 'error',
            'message' => $this->getMessage(),
        ];

        if (! empty($this->errors)) {
            $response['errors'] = $this->errors;
            $status = 422;
        }

        return response()->json($response, $status);
    }
}
