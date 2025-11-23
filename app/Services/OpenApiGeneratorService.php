<?php

namespace App\Services;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionMethod;

class OpenApiGeneratorService
{
  protected array $featureGroups = [
    "01-asesmen" => [
      "label" => "01 - Asesmen",
      "description" => "Manajemen jadwal, pelaksanaan, hasil, dan bank soal asesmen.",
      "features" => [
        "jadwal-pendaftaran" => [
          "label" => "Jadwal & Pendaftaran Asesmen",
          "description" => "Melihat jadwal dan mendaftar asesmen.",
          "modules" => ["Assessments", "Enrollments"],
          "keywords" => ["assessments/schedules", "assessments/register"],
        ],
        "pelaksanaan" => [
          "label" => "Pelaksanaan Asesmen",
          "description" => "Proses pengerjaan asesmen oleh asesi.",
          "modules" => ["Assessments"],
          "keywords" => ["assessments/attempts", "assessments/submit"],
        ],
        "hasil-rekomendasi" => [
          "label" => "Hasil & Rekomendasi Asesmen",
          "description" => "Melihat hasil penilaian dan rekomendasi asesor.",
          "modules" => ["Assessments", "Grading"],
          "keywords" => ["assessments/results", "assessments/recommendations"],
        ],
        "bank-soal" => [
          "label" => "Bank Soal Asesmen",
          "description" => "Manajemen bank soal khusus untuk asesmen.",
          "modules" => ["Assessments"],
          "keywords" => ["assessments/questions", "assessments/banks"],
        ],
      ],
    ],
    "02-auth" => [
      "label" => "02 - Autentikasi & Registrasi",
      "description" => "Fitur autentikasi, registrasi, dan manajemen sesi pengguna.",
      "features" => [
        "verifikasi-email" => [
          "label" => "Verifikasi Email",
          "description" => "Verifikasi alamat email pengguna baru.",
          "modules" => ["Auth"],
          "keywords" => ["email/verify", "email/resend"],
        ],
        "reset-password" => [
          "label" => "Reset & Ubah Password",
          "description" => "Proses reset password jika lupa atau ubah password.",
          "modules" => ["Auth"],
          "keywords" => ["password/reset", "password/email", "password/change"],
        ],
        "login-logout" => [
          "label" => "Login & Logout",
          "description" => "Masuk dan keluar dari aplikasi.",
          "modules" => ["Auth"],
          "keywords" => ["login", "logout"],
        ],
        "registrasi" => [
          "label" => "Registrasi Asesi",
          "description" => "Pendaftaran akun baru untuk asesi.",
          "modules" => ["Auth"],
          "keywords" => ["register"],
        ],
        "oauth" => [
          "label" => "Autentikasi Google (OAuth)",
          "description" => "Login menggunakan akun Google.",
          "modules" => ["Auth"],
          "keywords" => ["google", "oauth", "set-username"],
        ],
        "token" => [
          "label" => "Manajemen Token Akses",
          "description" => "Refresh token dan manajemen sesi.",
          "modules" => ["Auth"],
          "keywords" => ["refresh", "token"],
        ],
      ],
    ],
    "03-forum" => [
      "label" => "03 - Forum Diskusi",
      "description" => "Forum diskusi untuk tanya jawab dan interaksi antar pengguna.",
      "features" => [
        "forum-skema" => [
          "label" => "Forum Skema",
          "description" => "Forum diskusi spesifik untuk skema/kelas tertentu.",
          "modules" => ["Learning", "Schemes"],
          "keywords" => ["forum/schemes"],
        ],
        "topik" => [
          "label" => "Topik & Thread Diskusi",
          "description" => "Membuat dan melihat topik diskusi.",
          "modules" => ["Learning"],
          "keywords" => ["forum/threads", "forum/topics"],
        ],
        "komentar" => [
          "label" => "Komentar & Balasan",
          "description" => "Memberikan komentar atau balasan pada diskusi.",
          "modules" => ["Learning"],
          "keywords" => ["forum/comments", "forum/replies"],
        ],
      ],
    ],
    "04-gamifikasi" => [
      "label" => "04 - Gamifikasi & Perkembangan",
      "description" => "Fitur gamifikasi untuk meningkatkan motivasi belajar.",
      "features" => [
        "progress" => [
          "label" => "Progress Kursus",
          "description" => "Melacak kemajuan belajar pengguna.",
          "modules" => ["Learning", "Enrollments"],
          "keywords" => ["progress", "completion"],
        ],
        "poin" => [
          "label" => "Poin & Badges",
          "description" => "Sistem poin dan lencana penghargaan.",
          "modules" => ["Gamification"],
          "keywords" => ["points", "badges"],
        ],
        "level" => [
          "label" => "Level & XP",
          "description" => "Tingkatan level dan experience points pengguna.",
          "modules" => ["Gamification"],
          "keywords" => ["levels", "xp"],
        ],
        "leaderboard" => [
          "label" => "Leaderboard",
          "description" => "Papan peringkat pengguna terbaik.",
          "modules" => ["Gamification"],
          "keywords" => ["leaderboard"],
        ],
      ],
    ],
    "05-info" => [
      "label" => "05 - Informasi & Notifikasi",
      "description" => "Pusat informasi, berita, dan notifikasi sistem.",
      "features" => [
        "berita" => [
          "label" => "Berita & Pengumuman",
          "description" => "Berita terbaru dan pengumuman penting.",
          "modules" => ["Common", "Operations"],
          "keywords" => ["news", "announcements", "info"],
        ],
        "notifikasi-sistem" => [
          "label" => "Notifikasi Sistem",
          "description" => "Pengaturan dan log notifikasi sistem.",
          "modules" => ["Notifications"],
          "keywords" => ["notifications/system"],
        ],
        "notifikasi-inapp" => [
          "label" => "Notifikasi In-App",
          "description" => "Daftar notifikasi di dalam aplikasi.",
          "modules" => ["Notifications"],
          "keywords" => ["notifications"],
        ],
      ],
    ],
    "06-materi" => [
      "label" => "06 - Materi Pembelajaran",
      "description" => "Konten pembelajaran termasuk unit, lesson, dan media.",
      "features" => [
        "media" => [
          "label" => "Media Pembelajaran",
          "description" => "Manajemen file media pembelajaran.",
          "modules" => ["Learning", "Common"],
          "keywords" => ["media", "files"],
        ],
      ],
    ],
    "07-profil" => [
      "label" => "07 - Manajemen Profil",
      "description" => "Pengaturan akun dan profil pengguna.",
      "features" => [
        "profil" => [
          "label" => "Profil Pengguna (Lihat & Update)",
          "description" => "Melihat dan mengubah data profil.",
          "modules" => ["Auth"],
          "keywords" => ["profile", "me"],
        ],
        "email" => [
          "label" => "Manajemen Email",
          "description" => "Pengaturan alamat email.",
          "modules" => ["Auth"],
          "keywords" => ["email/update"],
        ],
        "password" => [
          "label" => "Manajemen Password",
          "description" => "Ubah password akun.",
          "modules" => ["Auth"],
          "keywords" => ["password/update"],
        ],
        "avatar" => [
          "label" => "Avatar & Data Pribadi",
          "description" => "Upload avatar dan update data pribadi.",
          "modules" => ["Auth"],
          "keywords" => ["avatar", "biodata"],
        ],
      ],
    ],
    "08-Skema" => [
      "label" => "08 - Skema & Materi",
      "description" => "Manajemen struktur skema dan materi pembelajaran.",
      "features" => [
        "skema" => [
          "label" => "Course",
          "description" => "Daftar skema dan detail kelas.",
          "modules" => ["Schemes"],
          "keywords" => ["schemes", "courses"],
        ],
        "unit" => [
          "label" => "Unit",
          "description" => "Unit kompetensi atau modul pembelajaran.",
          "modules" => ["Schemes", "Learning"],
          "keywords" => ["units"],
        ],
        "lesson" => [
          "label" => "Lesson",
          "description" => "Sesi pembelajaran individual.",
          "modules" => ["Schemes", "Learning"],
          "keywords" => ["lessons"],
        ],
        "blocks" => [
          "label" => "Lesson Block",
          "description" => "Blok konten dalam lesson (teks, video, dokumen).",
          "modules" => ["Schemes", "Learning"],
          "keywords" => ["blocks", "materials"],
        ],
      ],
    ],
    "09-kelas" => [
      "label" => "09 - Kelas & Pendaftaran",
      "description" => "Manajemen pendaftaran kelas dan kategori.",
      "features" => [
        "enrollments" => [
          "label" => "Pendaftaran Kelas (Enrollments)",
          "description" => "Proses pendaftaran peserta ke kelas.",
          "modules" => ["Enrollments"],
          "keywords" => ["enrollments", "join"],
        ],
        "approval" => [
          "label" => "Persetujuan Enroll (Admin & Instruktur)",
          "description" => "Persetujuan pendaftaran oleh admin/instruktur.",
          "modules" => ["Enrollments"],
          "keywords" => ["enrollments/approve", "enrollments/reject"],
        ],
        "kategori" => [
          "label" => "Kategori Skema",
          "description" => "Kategori dan pengelompokan skema.",
          "modules" => ["Schemes"],
          "keywords" => ["categories"],
        ],
        "tags" => [
          "label" => "Tag Kursus",
          "description" => "Label atau tag untuk pencarian kursus.",
          "modules" => ["Schemes"],
          "keywords" => ["tags"],
        ],
      ],
    ],
    "10-sistem" => [
      "label" => "10 - Sistem Manajemen",
      "description" => "Fitur administratif dan pengaturan sistem.",
      "features" => [
        "users" => [
          "label" => "Manajemen Pengguna",
          "description" => "CRUD pengguna dan manajemen status.",
          "modules" => ["Auth"],
          "keywords" => ["users", "profile", "updateUserStatus"],
        ],
        "master" => [
          "label" => "Master Data",
          "description" => "Data referensi sistem.",
          "modules" => ["Master"],
          "keywords" => ["master", "provinces", "cities"],
        ],
        "roles" => [
          "label" => "Role & Hak Akses",
          "description" => "Pengaturan role dan permission.",
          "modules" => ["Roles"],
          "keywords" => ["roles", "permissions"],
        ],
        "settings" => [
          "label" => "Pengaturan Sistem",
          "description" => "Konfigurasi global aplikasi.",
          "modules" => ["Settings"],
          "keywords" => ["settings"],
        ],
      ],
    ],
    "11-tugas" => [
      "label" => "11 - Tugas & Latihan",
      "description" => "Manajemen tugas, latihan soal, dan penilaian.",
      "features" => [
        "bank-soal" => [
          "label" => "Bank Soal (PG, Esai, File Upload)",
          "description" => "Bank soal umum untuk latihan dan tugas.",
          "modules" => ["Learning"],
          "keywords" => ["banks", "questions"],
        ],
        "tugas" => [
          "label" => "Tugas & Latihan Soal",
          "description" => "Daftar tugas yang harus dikerjakan.",
          "modules" => ["Learning"],
          "keywords" => ["assignments", "exercises"],
        ],
        "submission" => [
          "label" => "Pengumpulan Jawaban",
          "description" => "Submit jawaban tugas atau latihan.",
          "modules" => ["Learning"],
          "keywords" => ["submissions", "answers"],
        ],
        "penilaian" => [
          "label" => "Penilaian Tugas & Latihan",
          "description" => "Pemberian nilai dan feedback.",
          "modules" => ["Learning"],
          "keywords" => ["grades", "feedback"],
        ],
      ],
    ],
  ];

  public function generate(): array
  {
    $spec = [
      "openapi" => "3.1.0",
      "info" => [
        "title" => config("app.name", "Laravel API") . " Documentation",
        "version" => "1.0.0",
        "description" =>
          "Dokumentasi API untuk aplikasi " .
          config("app.name", "Laravel API") .
          " yang diorganisir per fitur sesuai kebutuhan Peserta, Instruktur, dan Admin.",
        "contact" => [
          "name" => "API Support",
        ],
      ],
      "servers" => [
        [
          "url" => config("app.url") . "/api",
          "description" => "API Server",
        ],
      ],
      "tags" => [],
      "x-tagGroups" => [],
      "paths" => [],
      "components" => [
        "securitySchemes" => [
          "bearerAuth" => [
            "type" => "http",
            "scheme" => "bearer",
            "bearerFormat" => "JWT",
            "description" => "JWT Authentication. Gunakan token yang didapat dari endpoint login.",
          ],
        ],
        "schemas" => [
          "SuccessResponse" => [
            "type" => "object",
            "properties" => [
              "status" => [
                "type" => "string",
                "enum" => ["success"],
                "example" => "success",
                "description" => "Status response, selalu 'success' untuk response sukses",
              ],
              "message" => [
                "type" => "string",
                "example" => "Berhasil",
                "description" => "Pesan sukses yang menjelaskan operasi yang dilakukan",
              ],
              "data" => [
                "type" => "object",
                "description" => "Data response, bisa berupa object, array, atau null",
                "additionalProperties" => true,
              ],
            ],
            "required" => ["status", "message", "data"],
          ],
          "ErrorResponse" => [
            "type" => "object",
            "properties" => [
              "status" => [
                "type" => "string",
                "example" => "error",
              ],
              "message" => [
                "type" => "string",
                "example" => "Terjadi kesalahan",
              ],
              "errors" => [
                "type" => "object",
                "description" => "Detail error (opsional, biasanya untuk validation errors)",
                "additionalProperties" => [
                  "type" => "array",
                  "items" => ["type" => "string"],
                ],
              ],
            ],
            "required" => ["status", "message"],
          ],
          "PaginationMeta" => [
            "type" => "object",
            "properties" => [
              "current_page" => [
                "type" => "integer",
                "description" => "Halaman saat ini",
              ],
              "per_page" => [
                "type" => "integer",
                "description" => "Jumlah item per halaman",
              ],
              "total" => [
                "type" => "integer",
                "description" => "Total jumlah item",
              ],
              "last_page" => [
                "type" => "integer",
                "description" => "Halaman terakhir",
              ],
              "from" => [
                "type" => "integer",
                "nullable" => true,
                "description" => "Index item pertama di halaman ini",
              ],
              "to" => [
                "type" => "integer",
                "nullable" => true,
                "description" => "Index item terakhir di halaman ini",
              ],
              "has_more" => [
                "type" => "boolean",
                "description" => "Apakah masih ada halaman berikutnya",
              ],
            ],
            "required" => ["current_page", "per_page", "total", "last_page", "has_more"],
          ],
          "PaginatedResponse" => [
            "type" => "object",
            "properties" => [
              "status" => [
                "type" => "string",
                "example" => "success",
              ],
              "message" => [
                "type" => "string",
                "example" => "Berhasil",
              ],
              "data" => [
                "type" => "object",
                "properties" => [
                  "items" => [
                    "type" => "array",
                    "description" => "Array of items",
                    "items" => ["type" => "object"],
                  ],
                  "meta" => [
                    '$ref' => "#/components/schemas/PaginationMeta",
                  ],
                ],
                "required" => ["items", "meta"],
              ],
            ],
            "required" => ["status", "message", "data"],
          ],
        ],
      ],
    ];

    // Generate tags & nested groups
    foreach ($this->featureGroups as $groupKey => $groupConfig) {
      $tagNames = [];
      foreach ($groupConfig["features"] as $featureKey => $featureConfig) {
        $tagName = $featureConfig["label"];
        $spec["tags"][] = [
          "name" => $tagName,
          "description" => $featureConfig["description"],
          "x-tagGroup" => $groupConfig["label"],
          "x-tagPath" => "{$groupKey}/{$featureKey}",
        ];
        $tagNames[] = $tagName;
      }

      $spec["x-tagGroups"][] = [
        "name" => $groupConfig["label"],
        "tags" => $tagNames,
        "description" => $groupConfig["description"],
      ];
    }

    // Process routes
    $routes = Route::getRoutes();
    foreach ($routes as $route) {
      if (!$route->getName() || !str_starts_with($route->uri(), "api/")) {
        continue;
      }

      $this->processRoute($route, $spec);
    }

    return $spec;
  }

  protected function processRoute($route, array &$spec): void
  {
    $uri = str_replace("api/", "", $route->uri());
    $httpMethod = strtolower($route->methods()[0] ?? "get");
    $action = $route->getAction();

    if (!isset($action["controller"])) {
      return;
    }

    $controllerParts = explode("@", $action["controller"]);
    $controllerClass = $controllerParts[0];
    $controllerMethod = $controllerParts[1] ?? "__invoke";

    if (!class_exists($controllerClass)) {
      return;
    }

    $featureInfo = $this->getFeatureInfo($uri, $controllerClass);
    if (!$featureInfo) {
      return;
    }

    $pathItem = $this->buildPathItem(
      $route,
      $controllerClass,
      $controllerMethod,
      $featureInfo,
      $uri,
    );

    if (!isset($spec["paths"]["/{$uri}"])) {
      $spec["paths"]["/{$uri}"] = [];
    }

    $spec["paths"]["/{$uri}"][$httpMethod] = $pathItem;
  }

  protected function getFeatureInfo(string $uri, string $controllerClass): ?array
  {
    $module = $this->extractModuleFromController($controllerClass);
    $uriLower = strtolower($uri);

    foreach ($this->featureGroups as $groupKey => $groupConfig) {
      // Iterate in reverse to check most specific features first (e.g. Blocks before Course)
      foreach (array_reverse($groupConfig["features"], true) as $featureKey => $featureConfig) {
        if ($this->matchesFeature($module, $uriLower, $featureConfig)) {
          return [
            "groupKey" => $groupKey,
            "groupLabel" => $groupConfig["label"],
            "featureKey" => $featureKey,
            "tag" => $featureConfig["label"],
          ];
        }
      }
    }

    return $this->getFallbackFeature();
  }

  protected function matchesFeature(?string $module, string $uriLower, array $featureConfig): bool
  {
    $moduleMatch =
      empty($featureConfig["modules"]) ||
      ($module !== null && in_array($module, $featureConfig["modules"], true));

    if (!$moduleMatch) {
      return false;
    }

    $keywords = $featureConfig["keywords"] ?? [];
    if (empty($keywords)) {
      return true;
    }

    return $this->uriMatchesKeywords($uriLower, $keywords);
  }

  protected function extractModuleFromController(string $controllerClass): ?string
  {
    if (preg_match("/Modules\\\\(.*?)\\\\Http\\\\Controllers/", $controllerClass, $matches)) {
      return $matches[1];
    }

    return null;
  }

  protected function uriMatchesKeywords(string $uriLower, array $keywords): bool
  {
    foreach ($keywords as $keyword) {
      if ($keyword === "") {
        continue;
      }
      if (str_contains($uriLower, strtolower($keyword))) {
        return true;
      }
    }

    return false;
  }

  protected function getFallbackFeature(): array
  {
    $groupKey = "umum";
    $featureKey = "general";

    $group = $this->featureGroups[$groupKey] ?? [
      "label" => "Umum",
      "features" => [
        $featureKey => [
          "label" => "Endpoint Lainnya",
        ],
      ],
    ];

    $feature = $group["features"][$featureKey] ?? ["label" => "Endpoint Lainnya"];

    return [
      "groupKey" => $groupKey,
      "groupLabel" => $group["label"],
      "featureKey" => $featureKey,
      "tag" => $feature["label"],
    ];
  }

  protected function buildPathItem(
    $route,
    string $controllerClass,
    string $method,
    array $featureInfo,
    string $uri,
  ): array {
    $reflection = new ReflectionClass($controllerClass);

    try {
      $methodReflection = $reflection->getMethod($method);
    } catch (\ReflectionException $e) {
      // If method doesn't exist, create a dummy reflection
      $methodReflection = null;
    }

    $httpMethod = strtolower($route->methods()[0] ?? "get");
    $summary = $this->getSummary($methodReflection, $uri, $httpMethod);
    $description = $this->getDescription($methodReflection, $uri, $httpMethod);

    $pathItem = [
      "tags" => [$featureInfo["tag"]],
      "summary" => $summary,
      "description" => $description,
      "operationId" =>
        $route->getName() ?:
        Str::camel($httpMethod . "_" . str_replace(["/", "{", "}"], ["_", "", ""], $uri)),
    ];

    // Security
    $middleware = $route->gatherMiddleware();
    if (
      in_array("auth:api", $middleware) ||
      in_array("auth:sanctum", $middleware) ||
      in_array("auth", $middleware)
    ) {
      $pathItem["security"] = [["bearerAuth" => []]];
    }

    // Parameters
    $parameters = $this->extractParameters($uri);

    // Add standard query params for GET list endpoints
    // List endpoints are GET requests that don't have required path parameters (like {id})
    // or are explicitly named 'index', 'list', etc.
    $isListEndpoint =
      $httpMethod === "get" &&
      ($method === "index" ||
        $method === "list" ||
        str_starts_with($method, "list") ||
        (empty($parameters) && !str_contains($uri, "{"))); // No path params = likely a list

    if ($isListEndpoint) {
      $parameters[] = [
        "name" => "page",
        "in" => "query",
        "description" => "Nomor halaman (pagination)",
        "schema" => ["type" => "integer", "default" => 1],
      ];
      $parameters[] = [
        "name" => "per_page",
        "in" => "query",
        "description" => "Jumlah item per halaman",
        "schema" => ["type" => "integer", "default" => 15],
      ];
      $parameters[] = [
        "name" => "search",
        "in" => "query",
        "description" => "Kata kunci pencarian",
        "schema" => ["type" => "string"],
      ];

      $allowedSorts = $this->getAllowedSorts($reflection, $methodReflection);
      if (!empty($allowedSorts)) {
        $sortDesc = "Allowed: " . implode(", ", $allowedSorts);
      } else {
        $sortDesc = "Pengurutan data (contoh: -created_at)";
      }

      $parameters[] = [
        "name" => "sort",
        "in" => "query",
        "description" => $sortDesc,
        "schema" => ["type" => "string"],
      ];

      $allowedFilters = $this->getAllowedFilters($reflection, $methodReflection);

      if (!empty($allowedFilters)) {
        // Explode filters into individual parameters
        foreach ($allowedFilters as $filterKey) {
          $enumValues = $this->getFilterEnumValues($filterKey, $reflection, $methodReflection);
          $description = "Filter by {$filterKey}";
          if ($enumValues) {
            $description .= " ({$enumValues})";
          }

          $parameters[] = [
            "name" => "filter[{$filterKey}]",
            "in" => "query",
            "description" => $description,
            "schema" => ["type" => "string"],
          ];
        }
      } else {
        // Fallback to generic filter object
        $parameters[] = [
          "name" => "filter",
          "in" => "query",
          "description" => "Filter data (contoh: filter[status]=active)",
          "style" => "deepObject",
          "explode" => true,
          "schema" => [
            "type" => "object",
            "additionalProperties" => ["type" => "string"],
          ],
        ];
      }
    }

    if (!empty($parameters)) {
      $pathItem["parameters"] = $parameters;
    }

    // Request Body
    $requestBody = $this->buildRequestBody($methodReflection, $httpMethod);
    if ($requestBody) {
      $pathItem["requestBody"] = $requestBody;
    }

    // Special handling for refresh token endpoint
    if (str_contains($uri, "/auth/refresh")) {
      $pathItem = $this->enhanceRefreshTokenEndpoint($pathItem, $uri);
    }

    // Responses
    $pathItem["responses"] = $this->buildResponses($httpMethod, $methodReflection, $uri);

    return $pathItem;
  }

  protected function getAllowedSorts(
    ReflectionClass $controllerReflection,
    ?ReflectionMethod $methodReflection,
  ): array {
    // 1. Check docblock
    if ($methodReflection) {
      $docBlock = $methodReflection->getDocComment();
      if ($docBlock && preg_match("/@allowedSorts\s+(.+)/", $docBlock, $matches)) {
        return array_map("trim", explode(",", $matches[1]));
      }
    }

    // 2. Check Repository
    $repoClass = $this->detectRepositoryClass($controllerReflection);
    if ($repoClass) {
      try {
        $repoReflection = new ReflectionClass($repoClass);
        if ($repoReflection->hasProperty("allowedSorts")) {
          $prop = $repoReflection->getProperty("allowedSorts");
          $prop->setAccessible(true);

          return $prop->getDefaultValue();
        }
      } catch (\Exception $e) {
        // ignore
      }
    }

    return [];
  }

  protected function getAllowedFilters(
    ReflectionClass $controllerReflection,
    ?ReflectionMethod $methodReflection,
  ): array {
    // 1. Check docblock
    if ($methodReflection) {
      $docBlock = $methodReflection->getDocComment();
      if ($docBlock && preg_match("/@allowedFilters\s+(.+)/", $docBlock, $matches)) {
        return array_map("trim", explode(",", $matches[1]));
      }
    }

    // 2. Check Repository
    $repoClass = $this->detectRepositoryClass($controllerReflection);
    if ($repoClass) {
      try {
        $repoReflection = new ReflectionClass($repoClass);
        if ($repoReflection->hasProperty("allowedFilters")) {
          $prop = $repoReflection->getProperty("allowedFilters");
          $prop->setAccessible(true);

          return $prop->getDefaultValue();
        }
      } catch (\Exception $e) {
        // ignore
      }
    }

    return [];
  }

  protected function detectRepositoryClass(ReflectionClass $controllerReflection): ?string
  {
    $constructor = $controllerReflection->getConstructor();
    if (!$constructor) {
      return null;
    }

    foreach ($constructor->getParameters() as $param) {
      $type = $param->getType();
      if (!$type instanceof \ReflectionNamedType || $type->isBuiltin()) {
        continue;
      }

      $typeName = $type->getName();
      if (str_ends_with($typeName, "Repository")) {
        return $typeName;
      }
    }

    return null;
  }

  protected function getFilterEnumValues(
    string $filterKey,
    ReflectionClass $controllerReflection,
    ?ReflectionMethod $methodReflection,
  ): ?string {
    // 1. Check docblock for @filterEnum annotations
    if ($methodReflection) {
      $docBlock = $methodReflection->getDocComment();
      if (
        $docBlock &&
        preg_match(
          "/@filterEnum\s+" . preg_quote($filterKey, "/") . "\s+(.+)/",
          $docBlock,
          $matches,
        )
      ) {
        return trim($matches[1]);
      }
    }

    // 2. Check Repository for $filterEnums property
    $repoClass = $this->detectRepositoryClass($controllerReflection);
    if ($repoClass) {
      try {
        $repoReflection = new ReflectionClass($repoClass);
        if ($repoReflection->hasProperty("filterEnums")) {
          $prop = $repoReflection->getProperty("filterEnums");
          $prop->setAccessible(true);
          $filterEnums = $prop->getDefaultValue();

          return $filterEnums[$filterKey] ?? null;
        }
      } catch (\Exception $e) {
        // ignore
      }
    }

    return null;
  }

  protected array $resourceMap = [
    "auth" => "Autentikasi",
    "login" => "Login",
    "register" => "Registrasi",
    "logout" => "Logout",
    "me" => "Profil Saya",
    "users" => "Pengguna",
    "courses" => "Kursus",
    "schemes" => "Skema",
    "enrollments" => "Pendaftaran",
    "assessments" => "Asesmen",
    "submissions" => "Pengumpulan Tugas",
    "assignments" => "Tugas",
    "lessons" => "Lesson",
    "units" => "Unit",
    "blocks" => "Block",
    "media" => "Media",
    "files" => "File",
    "notifications" => "Notifikasi",
    "announcements" => "Pengumuman",
    "news" => "Berita",
    "categories" => "Kategori",
    "tags" => "Tag",
    "comments" => "Komentar",
    "replies" => "Balasan",
    "threads" => "Thread",
    "topics" => "Topik",
    "leaderboard" => "Leaderboard",
    "points" => "Poin",
    "badges" => "Badge",
    "levels" => "Level",
    "verify" => "Verifikasi",
    "reset" => "Reset",
    "change" => "Ubah",
    "update" => "Update",
    "delete" => "Hapus",
    "create" => "Buat",
    "show" => "Lihat",
    "index" => "Daftar",
    "approve" => "Setujui",
    "reject" => "Tolak",
    "publish" => "Publikasi",
    "unpublish" => "Batalkan Publikasi",
    "grade" => "Penilaian",
    "feedback" => "Umpan Balik",
    "answers" => "Jawaban",
    "questions" => "Pertanyaan",
    "options" => "Opsi",
    "exercises" => "Latihan",
    "attempts" => "Percobaan",
    "results" => "Hasil",
    "recommendations" => "Rekomendasi",
    "schedules" => "Jadwal",
    "banks" => "Bank Soal",
    "oauth" => "OAuth",
    "google" => "Google",
    "callback" => "Callback",
    "redirect" => "Redirect",
    "token" => "Token",
    "refresh" => "Refresh",
  ];

  protected function getSummary(?ReflectionMethod $method, string $uri, string $httpMethod): string
  {
    if ($method) {
      $docComment = $method->getDocComment();
      if ($docComment && preg_match("/@summary\s+(.+)/", $docComment, $matches)) {
        return trim($matches[1]);
      }
    }

    $resourceName = $this->getResourceName($uri);

    // Special cases for specific actions
    if (in_array(strtolower($resourceName), ["login", "registrasi", "logout", "verifikasi"])) {
      return "Melakukan {$resourceName}";
    }

    $actionMap = [
      "get" => "Mengambil",
      "post" => "Membuat",
      "put" => "Memperbarui",
      "patch" => "Memperbarui",
      "delete" => "Menghapus",
    ];

    $action = $actionMap[$httpMethod] ?? "Mengakses";

    if (str_contains($uri, "{")) {
      return "{$action} detail {$resourceName}";
    }

    if ($httpMethod === "get") {
      return "Mengambil daftar {$resourceName}";
    }

    return "{$action} {$resourceName}";
  }

  protected function getDescription(
    ?ReflectionMethod $method,
    string $uri,
    string $httpMethod,
  ): string {
    // Priority 1: Check for @description in docblock
    if ($method) {
      $docComment = $method->getDocComment();
      if ($docComment && preg_match("/@description\s+(.+)/s", $docComment, $matches)) {
        $description = trim($matches[1]);
        // Remove "Untuk FE/Mobile", "Untuk UI/UX", "Untuk SA" if present
        $description = preg_replace(
          '/\s*Untuk\s+(FE\/Mobile|UI\/UX|SA):[^\n]*/i',
          "",
          $description,
        );
        // Clean up markdown formatting artifacts from docblock
        $description = preg_replace('/\n\s*\*\s*\n\s*\*/', "\n", $description);
        $description = preg_replace("/\*\*([^*]+)\*\*/", '**$1**', $description);

        return trim($description);
      }
    }

    // Priority 2: Detect special actions from URI
    $uriLower = strtolower($uri);
    $resourceName = $this->getResourceName($uri);

    // Special action patterns
    $specialActions = [
      "login" => "Melakukan autentikasi pengguna dan mendapatkan access token.",
      "logout" => "Mengakhiri sesi pengguna dan menghapus token.",
      "register" => "Mendaftarkan pengguna baru ke dalam sistem.",
      "refresh" => "Memperbarui access token menggunakan refresh token.",
      "verify" => "Memverifikasi {$resourceName}.",
      "resend" => "Mengirim ulang {$resourceName}.",
      "approve" => "Menyetujui {$resourceName}.",
      "reject" => "Menolak {$resourceName}.",
      "decline" => "Menolak {$resourceName}.",
      "publish" => "Mempublikasikan {$resourceName} agar dapat diakses oleh pengguna.",
      "unpublish" => "Membatalkan publikasi {$resourceName}.",
      "archive" => "Mengarsipkan {$resourceName}.",
      "restore" => "Memulihkan {$resourceName} yang telah dihapus.",
      "remove" => "Menghapus {$resourceName} dari sistem.",
      "assign" => "Menugaskan {$resourceName}.",
      "submit" => "Mengirimkan {$resourceName}.",
      "cancel" => "Membatalkan {$resourceName}.",
      "complete" => "Menyelesaikan {$resourceName}.",
      "start" => "Memulai {$resourceName}.",
      "finish" => "Menyelesaikan {$resourceName}.",
      "join" => "Bergabung ke {$resourceName}.",
      "leave" => "Keluar dari {$resourceName}.",
    ];

    foreach ($specialActions as $action => $description) {
      if (str_contains($uriLower, "/{$action}")) {
        return $description;
      }
    }

    // Priority 3: Generate context-aware description based on HTTP method and pattern
    $hasPathParam = str_contains($uri, "{");

    switch ($httpMethod) {
      case "get":
        if ($hasPathParam) {
          return "Mengambil informasi detail dari {$resourceName} berdasarkan ID.";
        } else {
          return "Mengambil daftar {$resourceName} dengan dukungan pagination, pencarian, sorting, dan filtering.";
        }

      case "post":
        return "Membuat {$resourceName} baru dengan data yang diberikan.";

      case "put":
        return "Memperbarui seluruh data {$resourceName} yang sudah ada.";

      case "patch":
        return "Memperbarui sebagian data {$resourceName} yang sudah ada.";

      case "delete":
        return "Menghapus {$resourceName} dari sistem secara permanen.";

      default:
        return "Operasi " . strtoupper($httpMethod) . " pada {$resourceName}.";
    }
  }

  protected function getResourceName(string $uri): string
  {
    $parts = explode("/", $uri);
    $resource = end($parts);
    $resource = str_replace(["{", "}", "-"], ["", "", " "], $resource);

    // Check map for exact match
    if (isset($this->resourceMap[$resource])) {
      return $this->resourceMap[$resource];
    }

    // Check map for individual words if it's a compound name
    $words = explode(" ", $resource);
    $translatedWords = array_map(function ($word) {
      return $this->resourceMap[$word] ?? ucfirst($word);
    }, $words);

    return implode(" ", $translatedWords);
  }

  protected function extractParameters(string $uri): array
  {
    $parameters = [];
    preg_match_all("/\{(\w+):?(\w+)?\}/", $uri, $matches, PREG_SET_ORDER);

    foreach ($matches as $match) {
      $paramName = $match[1];
      $paramType = $match[2] ?? "id";

      $parameters[] = [
        "name" => $paramName,
        "in" => "path",
        "required" => true,
        "description" => $this->getParameterDescription($paramName, $paramType),
        "schema" => [
          "type" =>
            $paramType === "slug" ? "string" : (is_numeric($paramType) ? "integer" : "string"),
        ],
      ];
    }

    return $parameters;
  }

  protected function getParameterDescription(string $name, string $type): string
  {
    $descriptions = [
      "id" => "ID unik dari {$name}",
      "slug" => "Slug dari {$name}",
      "course" => "Slug atau identifier kursus",
      "unit" => "Slug atau identifier unit",
      "lesson" => "Slug atau identifier lesson",
      "assignment" => "ID assignment",
      "submission" => "ID submission",
      "exercise" => "ID exercise",
      "question" => "ID question",
      "attempt" => "ID attempt",
    ];

    return $descriptions[$type] ?? ($descriptions[$name] ?? "Parameter {$name}");
  }

  protected function buildRequestBody(?ReflectionMethod $method, string $httpMethod): ?array
  {
    if (!in_array($httpMethod, ["post", "put", "patch"])) {
      return null;
    }

    if (!$method) {
      return [
        "required" => true,
        "content" => [
          "application/json" => [
            "schema" => [
              "type" => "object",
              "properties" => [],
            ],
          ],
        ],
      ];
    }

    $parameters = $method->getParameters();
    $requestParam = null;

    foreach ($parameters as $param) {
      $type = $param->getType();
      if (
        $type &&
        class_exists($type->getName()) &&
        is_subclass_of($type->getName(), \Illuminate\Http\Request::class)
      ) {
        $requestParam = $type->getName();
        break;
      }
    }

    if (!$requestParam) {
      return [
        "required" => true,
        "content" => [
          "application/json" => [
            "schema" => [
              "type" => "object",
              "properties" => [],
            ],
          ],
        ],
      ];
    }

    // Try to get validation rules from FormRequest
    if (method_exists($requestParam, "rules")) {
      $requestInstance = new $requestParam();
      $rules = $requestInstance->rules();
      $schemaResult = $this->rulesToSchema($rules);
      $schema = $schemaResult["schema"];
      $hasFile = $schemaResult["hasFile"];

      $contentType = $hasFile ? "multipart/form-data" : "application/json";

      return [
        "required" => true,
        "content" => [
          $contentType => [
            "schema" => $schema,
          ],
        ],
      ];
    }

    return [
      "required" => true,
      "content" => [
        "application/json" => [
          "schema" => [
            "type" => "object",
          ],
        ],
      ],
    ];
  }

  protected function rulesToSchema(array $rules): array
  {
    $schema = [
      "type" => "object",
      "properties" => [],
      "required" => [],
    ];
    $hasFile = false;

    foreach ($rules as $field => $rule) {
      $rulesArray = is_array($rule) ? $rule : explode("|", $rule);
      $fieldSchema = ["type" => "string"];

      $isRequired = in_array("required", $rulesArray);
      if ($isRequired) {
        $schema["required"][] = $field;
      }

      if (in_array("integer", $rulesArray) || in_array("numeric", $rulesArray)) {
        $fieldSchema["type"] = "integer";
      } elseif (in_array("boolean", $rulesArray)) {
        $fieldSchema["type"] = "boolean";
      } elseif (in_array("array", $rulesArray)) {
        $fieldSchema["type"] = "array";
      }

      // Check for file uploads
      if (
        in_array("file", $rulesArray) ||
        in_array("image", $rulesArray) ||
        $this->hasMimesRule($rulesArray)
      ) {
        $fieldSchema["type"] = "string";
        $fieldSchema["format"] = "binary";
        $hasFile = true;
      } else {
        // Generate example if not a file
        $example = $this->generateExample($field, $fieldSchema["type"]);
        if ($example !== null) {
          $fieldSchema["example"] = $example;
        }
      }

      if (in_array("nullable", $rulesArray)) {
        $fieldSchema["nullable"] = true;
      }

      $schema["properties"][$field] = $fieldSchema;
    }

    return ["schema" => $schema, "hasFile" => $hasFile];
  }

  protected function generateExample(string $field, string $type): mixed
  {
    if ($type === "boolean") {
      return true;
    }

    if ($type === "integer") {
      if (str_contains($field, "id")) {
        return 1;
      }
      if (str_contains($field, "age")) {
        return 25;
      }
      if (str_contains($field, "year")) {
        return 2024;
      }

      return 10;
    }

    if ($type === "string") {
      if (str_contains($field, "email")) {
        return "user@example.com";
      }
      if (str_contains($field, "password")) {
        return "password123";
      }
      if (str_contains($field, "name") || str_contains($field, "fullname")) {
        return "John Doe";
      }
      if (str_contains($field, "username")) {
        return "johndoe";
      }
      if (str_contains($field, "phone")) {
        return "081234567890";
      }
      if (str_contains($field, "address")) {
        return "Jl. Sudirman No. 1, Jakarta";
      }
      if (str_contains($field, "date")) {
        return "2024-01-01";
      }
      if (str_contains($field, "time")) {
        return "12:00:00";
      }
      if (str_contains($field, "description") || str_contains($field, "content")) {
        return "Lorem ipsum dolor sit amet.";
      }
      if (str_contains($field, "title")) {
        return "Judul Contoh";
      }
      if (str_contains($field, "slug")) {
        return "judul-contoh";
      }
      if (str_contains($field, "url")) {
        return "https://example.com";
      }
      if (str_contains($field, "token")) {
        return "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...";
      }
      if (str_contains($field, "code")) {
        return "CODE123";
      }
      if (str_contains($field, "type")) {
        return "type_1";
      }
      if (str_contains($field, "status")) {
        return "active";
      }
    }

    return null;
  }

  protected function hasMimesRule(array $rules): bool
  {
    foreach ($rules as $rule) {
      if (is_string($rule) && str_starts_with($rule, "mimes:")) {
        return true;
      }
    }

    return false;
  }

  protected function buildResponses(
    string $httpMethod,
    ?ReflectionMethod $method,
    string $uri = "",
  ): array {
    $responses = [];

    // Success response
    $statusCode = match ($httpMethod) {
      "post" => 201,
      "delete" => 200,
      default => 200,
    };

    // Determine if this is a list endpoint
    $isListEndpoint = $httpMethod === "get" && $method && $method->getName() === "index";

    // Build success response with example
    $successExample = $this->buildSuccessExample($httpMethod, $isListEndpoint, $uri);

    // Use PaginatedResponse schema for list endpoints
    $responseSchema = $isListEndpoint
      ? ['$ref' => "#/components/schemas/PaginatedResponse"]
      : ['$ref' => "#/components/schemas/SuccessResponse"];

    $responses[$statusCode] = [
      "description" => $isListEndpoint ? "Response sukses dengan pagination" : "Response sukses",
      "content" => [
        "application/json" => [
          "schema" => $responseSchema,
          "examples" => [
            "success" => [
              "summary" => "Contoh response sukses",
              "value" => $successExample,
            ],
          ],
        ],
      ],
    ];

    // Error responses with examples
    $responses["400"] = [
      "description" => "Bad Request - Request tidak valid",
      "content" => [
        "application/json" => [
          "schema" => [
            '$ref' => "#/components/schemas/ErrorResponse",
          ],
          "examples" => [
            "error" => [
              "summary" => "Contoh error bad request",
              "value" => [
                "status" => "error",
                "message" => "Request tidak valid",
                "errors" => [
                  "parameter" => ["Parameter yang diberikan tidak sesuai format"],
                ],
              ],
            ],
          ],
        ],
      ],
    ];

    $responses["401"] = [
      "description" => "Unauthorized - Token tidak valid atau tidak ada",
      "content" => [
        "application/json" => [
          "schema" => [
            '$ref' => "#/components/schemas/ErrorResponse",
          ],
          "examples" => [
            "error" => [
              "summary" => "Contoh error unauthorized",
              "value" => [
                "status" => "error",
                "message" => "Token tidak valid atau tidak ada",
              ],
            ],
          ],
        ],
      ],
    ];

    $responses["403"] = [
      "description" => "Forbidden - Tidak memiliki akses",
      "content" => [
        "application/json" => [
          "schema" => [
            '$ref' => "#/components/schemas/ErrorResponse",
          ],
          "examples" => [
            "error" => [
              "summary" => "Contoh error forbidden",
              "value" => [
                "status" => "error",
                "message" => "Anda tidak memiliki akses untuk melakukan operasi ini",
              ],
            ],
          ],
        ],
      ],
    ];

    $responses["422"] = [
      "description" => "Validation Error - Data yang dikirim tidak valid",
      "content" => [
        "application/json" => [
          "schema" => [
            '$ref' => "#/components/schemas/ErrorResponse",
          ],
          "examples" => [
            "error" => [
              "summary" => "Contoh validation error",
              "value" => [
                "status" => "error",
                "message" => "Data yang Anda kirim tidak valid. Periksa kembali isian Anda.",
                "errors" => [
                  "name" => ["Field name wajib diisi"],
                  "email" => ["Format email tidak valid"],
                ],
              ],
            ],
          ],
        ],
      ],
    ];

    $responses["404"] = [
      "description" => "Not Found - Resource tidak ditemukan",
      "content" => [
        "application/json" => [
          "schema" => [
            '$ref' => "#/components/schemas/ErrorResponse",
          ],
          "examples" => [
            "error" => [
              "summary" => "Contoh error not found",
              "value" => [
                "status" => "error",
                "message" => "Resource tidak ditemukan",
              ],
            ],
          ],
        ],
      ],
    ];

    $responses["500"] = [
      "description" => "Internal Server Error - Terjadi kesalahan pada server",
      "content" => [
        "application/json" => [
          "schema" => [
            '$ref' => "#/components/schemas/ErrorResponse",
          ],
          "examples" => [
            "error" => [
              "summary" => "Contoh error server",
              "value" => [
                "status" => "error",
                "message" => "Terjadi kesalahan pada server",
              ],
            ],
          ],
        ],
      ],
    ];

    return $responses;
  }

  protected function buildSuccessExample(
    string $httpMethod,
    bool $isListEndpoint,
    ?string $uri = null,
  ): array {
    $baseExample = [
      "status" => "success",
      "message" => "Berhasil",
    ];

    // Special case for refresh token endpoint
    if ($uri && str_contains($uri, "/auth/refresh")) {
      $baseExample["message"] = "Token akses berhasil diperbarui.";
      $baseExample["data"] = [
        "access_token" =>
          "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOjEsImlhdCI6MTY0MDk2ODAwMCwiZXhwIjoxNjQwOTY4OTAwfQ.example",
        "refresh_token" => "new_refresh_token_abc123def456...",
        "expires_in" => 900, // 15 menit dalam detik
      ];

      return $baseExample;
    }

    if ($httpMethod === "post") {
      $baseExample["message"] = "Data berhasil dibuat";
      $baseExample["data"] = [
        "id" => 1,
        "code" => "RESOURCE-001",
        "title" => "Resource Title",
        "slug" => "resource-title",
        "status" => "active",
        "created_at" => "2024-01-01T00:00:00.000000Z",
        "updated_at" => "2024-01-01T00:00:00.000000Z",
      ];
    } elseif ($httpMethod === "put" || $httpMethod === "patch") {
      $baseExample["message"] = "Data berhasil diperbarui";
      $baseExample["data"] = [
        "id" => 1,
        "code" => "RESOURCE-001",
        "title" => "Updated Resource Title",
        "slug" => "updated-resource-title",
        "status" => "active",
        "created_at" => "2024-01-01T00:00:00.000000Z",
        "updated_at" => "2024-01-02T00:00:00.000000Z",
      ];
    } elseif ($httpMethod === "delete") {
      $baseExample["message"] = "Data berhasil dihapus";
      $baseExample["data"] = [];
    } elseif ($isListEndpoint) {
      // List endpoint with pagination (actual structure from paginateResponse)
      $baseExample["data"] = [
        "items" => [
          [
            "id" => 1,
            "code" => "RESOURCE-001",
            "title" => "Resource 1",
            "slug" => "resource-1",
            "status" => "active",
          ],
          [
            "id" => 2,
            "code" => "RESOURCE-002",
            "title" => "Resource 2",
            "slug" => "resource-2",
            "status" => "active",
          ],
        ],
        "meta" => [
          "current_page" => 1,
          "per_page" => 15,
          "total" => 100,
          "last_page" => 7,
          "from" => 1,
          "to" => 15,
          "has_more" => true,
        ],
      ];
    } else {
      // Single resource detail
      $baseExample["data"] = [
        "id" => 1,
        "code" => "RESOURCE-001",
        "title" => "Resource Title",
        "slug" => "resource-title",
        "description" => "Resource description",
        "status" => "active",
        "created_at" => "2024-01-01T00:00:00.000000Z",
        "updated_at" => "2024-01-01T00:00:00.000000Z",
      ];
    }

    return $baseExample;
  }

  protected function enhanceRefreshTokenEndpoint(array $pathItem, string $uri): array
  {
    // Add header parameter for X-Refresh-Token (mobile app support)
    if (!isset($pathItem["parameters"])) {
      $pathItem["parameters"] = [];
    }

    $pathItem["parameters"][] = [
      "name" => "X-Refresh-Token",
      "in" => "header",
      "required" => false,
      "description" => "Refresh token untuk mobile app. Alternatif: kirim via cookie atau body.",
      "schema" => [
        "type" => "string",
        "example" => "abc123def456ghi789jkl012mno345pqr678stu901vwx234yz",
      ],
    ];

    // Add cookie parameter
    $pathItem["parameters"][] = [
      "name" => "refresh_token",
      "in" => "cookie",
      "required" => false,
      "description" =>
        "Refresh token untuk web app (httpOnly cookie). Alternatif: kirim via header X-Refresh-Token atau body.",
      "schema" => [
        "type" => "string",
        "example" => "abc123def456ghi789jkl012mno345pqr678stu901vwx234yz",
      ],
    ];

    // Update request body to show refresh_token is optional
    if (
      isset(
        $pathItem["requestBody"]["content"]["application/json"]["schema"]["properties"][
          "refresh_token"
        ],
      )
    ) {
      $pathItem["requestBody"]["content"]["application/json"]["schema"]["properties"][
        "refresh_token"
      ]["description"] =
        "Refresh token (opsional jika dikirim via cookie atau header X-Refresh-Token)";
      // Remove from required if present
      if (isset($pathItem["requestBody"]["content"]["application/json"]["schema"]["required"])) {
        $pathItem["requestBody"]["content"]["application/json"]["schema"][
          "required"
        ] = array_filter(
          $pathItem["requestBody"]["content"]["application/json"]["schema"]["required"],
          fn($field) => $field !== "refresh_token",
        );
      }
    }

    // Clean up description - remove markdown formatting artifacts from docblock
    $description = $pathItem["description"] ?? "";
    // Remove duplicate info if already present from docblock
    $description = preg_replace(
      "/\*\*Durasi Token:\*\*.*?\*\*Response:\*\*.*?\*\//s",
      "",
      $description,
    );
    $description = preg_replace('/\n\s*\*\s*\n\s*\*\s*\*\*Durasi Token:\*\*/s', "", $description);
    $description = trim($description);

    // Add comprehensive description if not already present
    if (!str_contains($description, "Durasi Token")) {
      $pathItem["description"] =
        $description .
        "\n\n" .
        "**Durasi Token:**\n" .
        "- Access Token: 15 menit (dapat dikonfigurasi via JWT_TTL)\n" .
        "- Refresh Token Idle Expiry: 14 hari (expired jika tidak digunakan selama 14 hari)\n" .
        "- Refresh Token Absolute Expiry: 90 hari (expired setelah 90 hari terlepas dari penggunaan)\n\n" .
        "**Cara Penggunaan:**\n\n" .
        "**Frontend (Web):**\n" .
        "- Kirim refresh token via cookie `refresh_token` (httpOnly, secure, sameSite)\n" .
        "- Atau via header `X-Refresh-Token`\n" .
        "- Atau via body `refresh_token`\n\n" .
        "**Mobile App:**\n" .
        "- Kirim refresh token via header `X-Refresh-Token` (disarankan)\n" .
        "- Atau via body `refresh_token`\n" .
        "- Simpan refresh token di secure storage (Keychain/Keystore)\n\n" .
        "**Token Rotation:**\n" .
        "- Setiap kali refresh, refresh token lama akan di-revoke dan diganti dengan yang baru\n" .
        "- Response akan mengembalikan access token baru dan refresh token baru";
    } else {
      $pathItem["description"] = $description;
    }

    return $pathItem;
  }
}
