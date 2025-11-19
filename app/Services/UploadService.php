<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class UploadService
{
    protected string $defaultDisk;

    public function __construct()
    {
        $this->defaultDisk = config('filesystems.default', 'do');
    }

    public function storePublic(UploadedFile $file, string $directory, ?string $filename = null, ?string $disk = null): string
    {
        $diskName = $disk ?: $this->defaultDisk;
        $diskConfig = config("filesystems.disks.{$diskName}");
        $name = $filename ?: $this->generateFilename($file);
        $path = trim($directory, '/').'/'.$name;
        $mime = $file->getMimeType();

        $isS3 = isset($diskConfig['driver']) && $diskConfig['driver'] === 's3';

        $content = null;
        $contentType = $mime ?? 'application/octet-stream';

        if (is_string($mime) && str_starts_with($mime, 'image/')) {
            if (class_exists('\\Intervention\\Image\\ImageManagerStatic')) {
                $quality = (int) env('IMAGE_QUALITY', 80);
                $image = call_user_func(['\\Intervention\\Image\\ImageManagerStatic', 'make'], $file->getRealPath());
                $targetMime = method_exists($image, 'mime') ? $image->mime() : 'jpg';
                $encoded = call_user_func([$image, 'encode'], $targetMime ?: 'jpg', $quality);
                
                $content = (string) $encoded;
                $contentType = is_string($targetMime) ? 'image/'.$targetMime : $contentType;
            } else {
                $content = file_get_contents($file->getRealPath());
            }
        } else {
            $content = file_get_contents($file->getRealPath());
        }

        if ($isS3) {
            try {
                $s3Client = new \Aws\S3\S3Client([
                    'version' => 'latest',
                    'region' => $diskConfig['region'] ?? 'sgp1',
                    'endpoint' => $diskConfig['endpoint'] ?? 'https://sgp1.digitaloceanspaces.com',
                    'credentials' => [
                        'key' => $diskConfig['key'] ?? '',
                        'secret' => $diskConfig['secret'] ?? '',
                    ],
                    'use_path_style_endpoint' => false,
                ]);

                $result = $s3Client->putObject([
                    'Bucket' => $diskConfig['bucket'] ?? 'prep-lsp',
                    'Key' => $path,
                    'Body' => $content,
                    'ACL' => 'public-read',
                    'ContentType' => $contentType,
                ]);

                Log::info('File uploaded to DO Spaces via AWS SDK', [
                    'path' => $path,
                    'size' => strlen($content),
                    'content_type' => $contentType,
                    'etag' => $result['ETag'] ?? null,
                ]);

                return $path;
            } catch (\Exception $e) {
                Log::error('DO Spaces upload failed via AWS SDK', [
                    'path' => $path,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                
                throw new \RuntimeException('Failed to upload file to DO Spaces: ' . $e->getMessage(), 0, $e);
            }
        }

        $storage = Storage::disk($diskName);
        $options = ['visibility' => 'public'];
        
        if ($content !== null) {
            $storage->put($path, $content, $options);
        } else {
            $storage->putFileAs(trim($directory, '/'), $file, $name, $options);
        }

        return $path;
    }

    public function deletePublic(?string $path, ?string $disk = null): void
    {
        if (! $path) {
            return;
        }

        $diskName = $disk ?: $this->defaultDisk;
        $diskConfig = config("filesystems.disks.{$diskName}");
        $isS3 = isset($diskConfig['driver']) && $diskConfig['driver'] === 's3';

        // For S3-compatible storage (DO Spaces), use AWS SDK directly
        if ($isS3) {
            try {
                $s3Client = new \Aws\S3\S3Client([
                    'version' => 'latest',
                    'region' => $diskConfig['region'] ?? 'sgp1',
                    'endpoint' => $diskConfig['endpoint'] ?? 'https://sgp1.digitaloceanspaces.com',
                    'credentials' => [
                        'key' => $diskConfig['key'] ?? '',
                        'secret' => $diskConfig['secret'] ?? '',
                    ],
                    'use_path_style_endpoint' => false,
                ]);

                $s3Client->deleteObject([
                    'Bucket' => $diskConfig['bucket'] ?? 'prep-lsp',
                    'Key' => $path,
                ]);

                Log::info('File deleted from DO Spaces via AWS SDK', ['path' => $path]);
            } catch (\Exception $e) {
                Log::error('DO Spaces delete failed via AWS SDK', [
                    'path' => $path,
                    'error' => $e->getMessage(),
                ]);
            }
            return;
        }

        $storage = Storage::disk($diskName);
        if ($storage->exists($path)) {
            $storage->delete($path);
        }
    }

    public function getPublicUrl(?string $path, ?string $disk = null): ?string
    {
        if (! $path) {
            return null;
        }

        $diskName = $disk ?: $this->defaultDisk;
        if ($diskName === 'public') {
            return asset('storage/'.$path);
        }

        $config = config("filesystems.disks.{$diskName}");

        $useCdn = filter_var(env('DO_USE_CDN', true), FILTER_VALIDATE_BOOL);
        if ($useCdn && isset($config['url']) && is_string($config['url']) && $config['url'] !== '') {
            return rtrim($config['url'], '/').'/'.ltrim($path, '/');
        }

        if (isset($config['driver']) && $config['driver'] === 's3') {
            $bucket = $config['bucket'] ?? null;
            $endpoint = $config['endpoint'] ?? null;
            if (is_string($bucket) && $bucket !== '' && is_string($endpoint) && $endpoint !== '') {
                $host = parse_url($endpoint, PHP_URL_HOST);
                if (is_string($host) && $host !== '') {
                    return 'https://'.rtrim($bucket.'.'.$host, '/').'/'.ltrim($path, '/');
                }
            }
        }

        try {
            return \Illuminate\Support\Facades\Storage::disk($diskName)->url($path);
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function exists(string $path, ?string $disk = null): bool
    {
        $diskName = $disk ?: $this->defaultDisk;
        $diskConfig = config("filesystems.disks.{$diskName}");
        $isS3 = isset($diskConfig['driver']) && $diskConfig['driver'] === 's3';

        // For S3-compatible storage (DO Spaces), use AWS SDK directly
        if ($isS3) {
            try {
                $s3Client = new \Aws\S3\S3Client([
                    'version' => 'latest',
                    'region' => $diskConfig['region'] ?? 'sgp1',
                    'endpoint' => $diskConfig['endpoint'] ?? 'https://sgp1.digitaloceanspaces.com',
                    'credentials' => [
                        'key' => $diskConfig['key'] ?? '',
                        'secret' => $diskConfig['secret'] ?? '',
                    ],
                    'use_path_style_endpoint' => false,
                ]);

                $s3Client->headObject([
                    'Bucket' => $diskConfig['bucket'] ?? 'prep-lsp',
                    'Key' => $path,
                ]);

                return true;
            } catch (\Aws\S3\Exception\S3Exception $e) {
                if ($e->getAwsErrorCode() === 'NotFound' || $e->getStatusCode() === 404) {
                    return false;
                }
                Log::error('DO Spaces exists check failed via AWS SDK', [
                    'path' => $path,
                    'error' => $e->getMessage(),
                ]);
                return false;
            } catch (\Exception $e) {
                Log::error('DO Spaces exists check error', [
                    'path' => $path,
                    'error' => $e->getMessage(),
                ]);
                return false;
            }
        }

        return Storage::disk($diskName)->exists($path);
    }

    protected function generateFilename(UploadedFile $file): string
    {
        $ext = $file->getClientOriginalExtension() ?: $file->extension();

        return uniqid('file_', true).($ext ? '.'.$ext : '');
    }
}
