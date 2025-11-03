<?php

namespace App\Services;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class UploadService
{
    public function storePublic(UploadedFile $file, string $directory, ?string $filename = null): string
    {
        $disk = Storage::disk('public');
        $name = $filename ?: $this->generateFilename($file);
        $path = trim($directory, '/').'/'.$name;
        $disk->putFileAs(trim($directory, '/'), $file, $name);
        return $path;
    }

    public function deletePublic(?string $path): void
    {
        if (!$path) {
            return;
        }
        $disk = Storage::disk('public');
        if ($disk->exists($path)) {
            $disk->delete($path);
        }
    }

    protected function generateFilename(UploadedFile $file): string
    {
        $ext = $file->getClientOriginalExtension() ?: $file->extension();
        return uniqid('file_', true).($ext ? '.'.$ext : '');
    }
}


