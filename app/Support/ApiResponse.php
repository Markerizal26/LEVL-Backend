<?php

namespace App\Support;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    private static function translate(string $message, array $params = []): string
    {
        if (str_contains($message, '.') && trans()->has($message)) {
            return __($message, $params);
        }

        return $message;
    }

    protected function success(
        mixed $data = null,
        string $message = 'messages.success',
        array $params = [],
        int $status = 200,
        ?array $meta = null
    ): JsonResponse {
        return response()->json(
            [
                'success' => true,
                'message' => self::translate($message, $params),
                'data' => $data,
                'meta' => $meta,
                'errors' => null,
            ],
            $status
        );
    }

    protected function created(
        mixed $data = null,
        string $message = 'messages.created',
        array $params = [],
        ?array $meta = null
    ): JsonResponse {
        return $this->success($data, $message, $params, 201, $meta);
    }

    protected function error(
        string $message = 'messages.error',
        array $params = [],
        int $status = 400,
        ?array $errors = null,
        mixed $data = null,
        ?array $meta = null
    ): JsonResponse {
        return response()->json(
            [
                'success' => false,
                'message' => self::translate($message, $params),
                'data' => $data,
                'meta' => $meta,
                'errors' => $errors,
            ],
            $status
        );
    }

    protected function paginateResponse(
        LengthAwarePaginator $paginator,
        string $message = 'messages.success',
        int $status = 200,
        ?array $additionalMeta = null,
        array $params = []
    ): JsonResponse {
        $request = request();
        $collection = $paginator->getCollection();

        $meta = [
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
                'has_next' => $paginator->hasMorePages(),
                'has_prev' => $paginator->currentPage() > 1,
            ],
        ];

        if ($request->has('sort')) {
            $meta['sorting'] = [
                'sort_by' => $request->get('sort'),
                'sort_order' => $request->get('sort_order', 'asc'),
            ];
        }

        foreach (['filter', 'filters'] as $key) {
            if ($request->has($key)) {
                $meta['filtering'] = $request->get($key);
                break;
            }
        }

        if ($request->filled('search')) {
            $meta['search'] = [
                'query' => $request->get('search'),
            ];
        }

        if ($additionalMeta) {
            $meta = array_replace_recursive($meta, $additionalMeta);
        }

        return $this->success(
            data: $collection,
            message: $message,
            params: $params,
            status: $status,
            meta: $meta
        );
    }

    protected function validationError(
        array $errors,
        string $message = 'messages.validation_failed',
        array $params = []
    ): JsonResponse {
        return $this->error(
            message: $message,
            params: $params,
            status: 422,
            errors: $errors
        );
    }

    protected function notFound(
        string $message = 'messages.not_found',
        array $params = []
    ): JsonResponse {
        return $this->error($message, $params, 404);
    }

    protected function unauthorized(
        string $message = 'messages.unauthorized',
        array $params = []
    ): JsonResponse {
        return $this->error($message, $params, 401);
    }

    protected function forbidden(
        string $message = 'messages.forbidden',
        array $params = []
    ): JsonResponse {
        return $this->error($message, $params, 403);
    }

    protected function noContent(): JsonResponse
    {
        return response()->noContent();
    }

    public static function successStatic(
        mixed $data = null,
        string $message = 'messages.success',
        array $params = [],
        int $status = 200,
        ?array $meta = null
    ): JsonResponse {
        return response()->json(
            [
                'success' => true,
                'message' => self::translate($message, $params),
                'data' => $data,
                'meta' => $meta,
                'errors' => null,
            ],
            $status
        );
    }

    public static function errorStatic(
        string $message = 'messages.error',
        array $params = [],
        int $status = 400,
        ?array $errors = null,
        mixed $data = null,
        ?array $meta = null
    ): JsonResponse {
        return response()->json(
            [
                'success' => false,
                'message' => self::translate($message, $params),
                'data' => $data,
                'meta' => $meta,
                'errors' => $errors,
            ],
            $status
        );
    }
}
