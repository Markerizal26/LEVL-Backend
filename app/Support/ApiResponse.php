<?php

namespace App\Support;

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
}


