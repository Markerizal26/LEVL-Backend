<?php

namespace App\Providers;

use App\Contracts\EnrollmentKeyHasherInterface;
use App\Support\EnrollmentKeyHasher;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Dedoc\Scramble\Support\Generator\Server;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            EnrollmentKeyHasherInterface::class,
            EnrollmentKeyHasher::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();

        // Only configure Scramble when explicitly needed (not on every request)
        // This prevents memory issues during normal API requests
        if (request()->is('docs*') || request()->is('docs/api.json')) {
            $this->configureScramble();
        }

        if ($this->app->environment('local')) {
            Mail::alwaysTo(config('mail.development_to', 'dev@local.test'));
        }
    }

    /**
     * Configure the rate limiters for the application.
     */
    protected function configureRateLimiting(): void
    {
        // Default API rate limiter
        RateLimiter::for('api', function (Request $request) {
            $config = config('rate-limiting.api.default');

            return Limit::perMinutes($config['decay'], $config['max'])
                ->by($request->user()?->id ?: $request->ip());
        });

        // Auth endpoints rate limiter (more restrictive)
        RateLimiter::for('auth', function (Request $request) {
            $config = config('rate-limiting.api.auth');

            return Limit::perMinutes($config['decay'], $config['max'])
                ->by($request->ip());
        });

        // Enrollment endpoints rate limiter (most restrictive)
        RateLimiter::for('enrollment', function (Request $request) {
            $config = config('rate-limiting.api.enrollment');

            return Limit::perMinutes($config['decay'], $config['max'])
                ->by($request->user()?->id ?: $request->ip());
        });
    }

    /**
     * Configure Scramble for OpenAPI documentation.
     */
    protected function configureScramble(): void
    {
        // Register all API routes (including module routes)
        Scramble::configure()
            ->routes(function (Route $route) {
                return Str::startsWith($route->uri, 'api/');
            });

        // Configure OpenAPI info and security after generation
        Scramble::configure()
            ->afterOpenApiGenerated(function (OpenApi $openApi) {
                $openApi->info->title = config('app.name', 'TA Prep LSP').' API';
                $openApi->info->description = $this->getApiDescription();

                // Add JWT Bearer authentication
                $openApi->secure(
                    SecurityScheme::http('bearer', 'JWT')
                );

                // Add servers
                $this->configureServers($openApi);

                // Add tag groups
                $this->configureTagGroups($openApi);
            });
    }

    /**
     * Configure API servers for OpenAPI documentation.
     */
    private function configureServers(OpenApi $openApi): void
    {
        $openApi->servers = [
            new Server(config('app.url').'/api', 'Local Development'),
        ];

        if ($prodUrl = config('api.production_url')) {
            $openApi->servers[] = new Server($prodUrl.'/api', 'Production');
        }
    }

    /**
     * Configure tag groups for better navigation in API documentation.
     *
     * Note: x-tagGroups is a Redoc/Scalar extension for grouping tags in the sidebar.
     * Since Scramble's OpenApi class may not have addExtension method in all versions,
     * we set it directly on the extensions property if available.
     */
    private function configureTagGroups(OpenApi $openApi): void
    {
        // Tag groups for better organization in Scalar UI
        // This extension is supported by Scalar and Redoc
        $tagGroups = [
            [
                'name' => 'Autentikasi & Pengguna',
                'tags' => ['Autentikasi', 'Profil Pengguna', 'Manajemen Pengguna'],
            ],
            [
                'name' => 'Pembelajaran',
                'tags' => ['Skema & Kursus', 'Unit Kompetensi', 'Materi Pembelajaran', 'Progress Belajar'],
            ],
            [
                'name' => 'Tugas & Penilaian',
                'tags' => ['Tugas & Pengumpulan', 'Penilaian'],
            ],
            [
                'name' => 'Interaksi',
                'tags' => ['Forum Diskusi', 'Notifikasi', 'Gamifikasi'],
            ],
            [
                'name' => 'Konten & Data',
                'tags' => ['Konten & Berita', 'Data Master', 'Pencarian'],
            ],
            [
                'name' => 'Administrasi',
                'tags' => ['Pendaftaran Kursus', 'Laporan & Statistik'],
            ],
        ];

        // Try to set extension if the property exists
        if (property_exists($openApi, 'extensions')) {
            $openApi->extensions['x-tagGroups'] = $tagGroups;
        }
    }

    /**
     * Get API description for OpenAPI documentation.
     */
    private function getApiDescription(): string
    {
        return <<<'MD'
## TA Prep LSP API

Platform Pembelajaran dan Sertifikasi LSP.

### Authentication

API ini menggunakan JWT Bearer Token untuk autentikasi. Untuk mendapatkan token:

1. Register atau Login melalui endpoint `/v1/auth/register` atau `/v1/auth/login`
2. Gunakan `access_token` dari response sebagai Bearer Token
3. Refresh token menggunakan `/v1/auth/refresh` sebelum expired

### Rate Limiting

- Default API: 60 requests per minute
- Auth endpoints: 10 requests per minute
- Enrollment endpoints: 5 requests per minute

### Response Format

Semua response menggunakan format standar:

```json
{
  "success": true,
  "message": "Operation successful",
  "data": { ... }
}
```

Error response:

```json
{
  "success": false,
  "message": "Error description",
  "errors": { ... }
}
```

### Query Parameters

Endpoint yang mendukung filtering dan sorting menggunakan format:

- **Filter:** `?filter[field]=value`
- **Sort:** `?sort=field` (ascending) atau `?sort=-field` (descending)
- **Pagination:** `?page=1&per_page=15`
- **Search:** `?search=keyword`
- **Include:** `?include=relation1,relation2`
MD;
    }
}
