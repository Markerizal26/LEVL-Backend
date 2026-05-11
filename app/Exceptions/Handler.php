<?php

namespace App\Exceptions;

use App\Support\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\Exceptions\InvalidFilterQuery;
use Spatie\QueryBuilder\Exceptions\InvalidSortQuery;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    use ApiResponse;

    protected $dontFlash = ['current_password', 'password', 'password_confirmation'];

    protected $dontReport = [
        BusinessException::class,
        ForbiddenException::class,
        UnauthorizedException::class,
        AuthorizationException::class,
    ];

    public function register(): void
    {
        $this->reportable(function (Throwable $e) {});

        $this->renderable(function (AccessDeniedHttpException $e, Request $request) {
            if ($this->isApiRequest($request)) {
                $previous = $e->getPrevious();
                $message = null;

                if ($previous instanceof \Illuminate\Auth\Access\AuthorizationException) {
                    $message = $previous->getMessage() ?: null;
                    if (! $message && method_exists($previous, 'response') && $previous->response()) {
                        $message = $previous->response()->message();
                    }
                }

                return $this->forbidden($message ?: __('messages.forbidden'));
            }
        });

        $this->renderable(function (AuthorizationException $e, Request $request) {
            if ($this->isApiRequest($request)) {
                $message = $e->getMessage() ?: __('messages.forbidden');

                return $this->forbidden($message);
            }
        });
    }

    protected function isApiRequest(Request $request): bool
    {
        return $request->is('api/*') || $request->is('v1/*') || $request->expectsJson();
    }

    public function render($request, Throwable $e): Response
    {
        if ($this->isApiRequest($request)) {
            return $this->handleApiException($request, $e);
        }

        return parent::render($request, $e);
    }

    protected function shouldReturnJson($request, Throwable $e): bool
    {

        if ($request->is('api/*') || $request->is('v1/*')) {
            return true;
        }

        return parent::shouldReturnJson($request, $e);
    }

    protected function handleApiException(Request $request, Throwable $e): JsonResponse
    {

        if ($e instanceof \Illuminate\Validation\ValidationException) {
            $errors = $e->errors();

            $message = 'messages.validation_failed';
            if (! empty($errors)) {
                $firstError = reset($errors);
                if (is_array($firstError) && ! empty($firstError)) {
                    $message = $firstError[0];
                }
            }

            return $this->validationError($errors, $message);
        }

        if ($e instanceof \App\Exceptions\ValidationException) {
            $message = $e->getMessage() ?: 'messages.validation_failed';

            return $this->validationError([], $message);
        }

        if ($e instanceof ModelNotFoundException) {
            $messageKey = $this->getExceptionMessageKey($e);

            return $this->notFound($messageKey);
        }

        if ($e instanceof AuthenticationException) {
            $messageKey = $this->getExceptionMessageKey($e);

            return $this->unauthorized($messageKey);
        }

        if ($e instanceof AuthorizationException) {
            $message = $e->getMessage();

            if (empty($message) && method_exists($e, 'response') && $e->response() && $e->response()->message()) {
                $message = $e->response()->message();
            }

            if (empty($message)) {
                $message = $this->getExceptionMessageKey($e);
            }

            return $this->forbidden($message);
        }

        if ($e instanceof \Modules\Schemes\Exceptions\LessonCompletionException) {
            $message = $e->getMessage();
            $statusCode = $e->getCode() ?: 422;

            return $this->error($message, [], $statusCode);
        }

        if ($e instanceof InvalidFilterException) {

            $message = $e->getMessage() ?: 'messages.invalid_request';

            return $this->error(
                $message,
                [],
                400,
                ['filter' => $e->getInvalidFilters()]
            );
        }

        if ($e instanceof InvalidSortException) {

            $message = $e->getMessage() ?: 'messages.invalid_request';

            return $this->error(
                $message,
                [],
                400,
                ['sort' => [$e->getInvalidSort()]]
            );
        }

        if ($e instanceof InvalidFilterQuery) {
            return $this->error($e->getMessage(), [], 400);
        }

        if ($e instanceof InvalidSortQuery) {
            return $this->error($e->getMessage(), [], 400);
        }

        if ($e instanceof ResourceNotFoundException) {

            $message = $e->getMessage() ?: 'messages.not_found';

            return $this->notFound($message);
        }

        if ($e instanceof UnauthorizedException) {

            $message = $e->getMessage() ?: 'messages.unauthorized';

            return $this->unauthorized($message);
        }

        if ($e instanceof ForbiddenException) {

            $message = $e->getMessage() ?: 'messages.forbidden';

            return $this->forbidden($message);
        }

        if ($e instanceof DuplicateResourceException) {

            $message = $e->getMessage() ?: 'messages.duplicate_entry';

            return $this->error($message, [], 409);
        }

        if ($e instanceof BusinessException) {

            $message = $e->getMessage() ?: 'messages.error';

            return $this->error($message, [], 422);
        }

        if ($e instanceof NotFoundHttpException) {
            $messageKey = $this->getExceptionMessageKey($e);

            return $this->notFound($messageKey);
        }

        if ($e instanceof UnauthorizedHttpException) {
            $messageKey = $this->getExceptionMessageKey($e);

            return $this->unauthorized($messageKey);
        }

        if ($e instanceof AccessDeniedHttpException) {
            $messageKey = $this->getExceptionMessageKey($e);

            return $this->forbidden($messageKey);
        }

        if ($e instanceof HttpExceptionInterface || $e instanceof HttpException) {
            $statusCode = $e->getStatusCode();
            $messageKey = $this->getExceptionMessageKey($e);

            if (config('app.debug')) {
                return $this->error($messageKey, [], $statusCode, [
                    'exception' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }

            return $this->error($messageKey, [], $statusCode);
        }

        $messageKey = $this->getExceptionMessageKey($e);

        if (config('app.debug')) {
            return $this->error($messageKey, [], 500, [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return $this->error($messageKey, [], 500);
    }

    protected function getExceptionMessageKey(Throwable $e): string
    {

        return match (true) {
            $e instanceof ModelNotFoundException => 'messages.not_found',
            $e instanceof AuthenticationException => 'messages.unauthenticated',
            $e instanceof AuthorizationException => 'messages.forbidden',
            $e instanceof NotFoundHttpException => 'messages.not_found',
            $e instanceof UnauthorizedHttpException => 'messages.unauthorized',
            $e instanceof AccessDeniedHttpException => 'messages.forbidden',
            default => 'messages.server_error',
        };
    }
}
