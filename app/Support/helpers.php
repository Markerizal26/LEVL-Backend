<?php

use App\Services\UploadService;
use Illuminate\Http\UploadedFile;

if (!function_exists('upload')) {
    function upload(): UploadService
    {
        return app(UploadService::class);
    }
}

if (!function_exists('upload_file')) {
    function upload_file(UploadedFile $file, string $directory, ?string $filename = null, ?string $disk = null): string
    {
        return app(UploadService::class)->storePublic($file, $directory, $filename, $disk);
    }
}

if (!function_exists('delete_file')) {
    function delete_file(?string $path, ?string $disk = null): void
    {
        if ($path) {
            app(UploadService::class)->deletePublic($path, $disk);
        }
    }
}

if (!function_exists('file_url')) {
    function file_url(?string $path, ?string $disk = null): ?string
    {
        return app(UploadService::class)->getPublicUrl($path, $disk);
    }
}

if (!function_exists('file_exists_in_storage')) {
    function file_exists_in_storage(string $path, ?string $disk = null): bool
    {
        return app(UploadService::class)->exists($path, $disk);
    }
}
