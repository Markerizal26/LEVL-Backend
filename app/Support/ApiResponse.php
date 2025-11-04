<?php

namespace App\Support;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

trait ApiResponse
{
    protected function success(array $data = [], string $message = 'Berhasil', int $status = 200)
    {
        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    protected function created(array $data = [], string $message = 'Berhasil dibuat')
    {
        return $this->success($data, $message, 201);
    }

    protected function error(string $message = 'Terjadi kesalahan', int $status = 400, ?array $errors = null)
    {
        $body = [
            'status' => 'error',
            'message' => $message,
        ];
        if ($errors !== null) {
            $body['errors'] = $errors;
        }

        return response()->json($body, $status);
    }

    protected function paginateResponse(LengthAwarePaginator $paginator, string $message = 'Berhasil')
    {
        return $this->success([
            'items' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
                'has_more' => $paginator->hasMorePages(),
            ],
        ], $message);
    }

    protected function validationError(array $errors)
    {
        return $this->error('Data yang Anda kirim tidak valid. Periksa kembali isian Anda.', 422, $errors);
    }
}
