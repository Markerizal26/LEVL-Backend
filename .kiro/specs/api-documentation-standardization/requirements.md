# Requirements Document

## Introduction

Dokumen ini mendefinisikan requirements untuk menstandarisasi dokumentasi API menggunakan Scramble auto-generate, memperbaiki inkonsistensi format response, menerapkan PHPDoc standards yang konsisten di semua controller, menambahkan realistic examples, dan mendokumentasikan rate limiting. Tujuannya adalah menghasilkan dokumentasi API yang konsisten, lengkap, dan mudah digunakan oleh API consumer.

## Glossary

- **Scramble**: Library Laravel untuk auto-generate OpenAPI specification dari kode PHP
- **OpenAPI**: Standar spesifikasi untuk mendeskripsikan RESTful APIs
- **Scalar**: UI modern untuk menampilkan dokumentasi OpenAPI
- **PHPDoc**: Dokumentasi dalam kode PHP menggunakan format docblock
- **API_Documentation_System**: Sistem Scramble yang menghasilkan dokumentasi API secara otomatis
- **Rate Limiting**: Pembatasan jumlah request API per periode waktu
- **Throttle Middleware**: Laravel middleware untuk implementasi rate limiting
- **ApiResponse Trait**: Trait Laravel yang menyediakan format response standar

## Requirements

### Requirement 1: Response Format Standardization

**User Story:** As an API consumer, I want all API responses to follow a consistent structure, so that I can easily parse and handle responses from any endpoint.

#### Acceptance Criteria

1. WHEN any endpoint returns a success response THEN the API_Documentation_System SHALL document the standard structure: `{success: boolean, message: string, data: object|array|null, meta: object|null, errors: null}`
2. WHEN any endpoint returns a validation error THEN the API_Documentation_System SHALL document the structure: `{success: false, message: string, data: null, meta: null, errors: {field: [messages]}}`
3. WHEN any endpoint returns an error response THEN the API_Documentation_System SHALL document the structure with appropriate HTTP status code (400, 401, 403, 404, 422, 500)
4. WHEN documenting response examples THEN the API_Documentation_System SHALL use `success` field (not `status`) to match ApiResponse trait implementation

### Requirement 2: PHPDoc Summary Standardization

**User Story:** As a developer, I want consistent @summary annotations across all controllers in Bahasa Indonesia, so that Scramble generates uniform documentation.

#### Acceptance Criteria

1. WHEN a controller method handles a GET list endpoint THEN the method SHALL have @summary annotation in format: "Daftar {Resource}" (e.g., "Daftar Pengumuman", "Daftar Kursus")
2. WHEN a controller method handles a GET detail endpoint THEN the method SHALL have @summary annotation in format: "Detail {Resource}" (e.g., "Detail Pengumuman", "Detail Kursus")
3. WHEN a controller method handles a POST create endpoint THEN the method SHALL have @summary annotation in format: "Buat {Resource} Baru" (e.g., "Buat Pengumuman Baru")
4. WHEN a controller method handles a PUT/PATCH update endpoint THEN the method SHALL have @summary annotation in format: "Perbarui {Resource}" (e.g., "Perbarui Pengumuman")
5. WHEN a controller method handles a DELETE endpoint THEN the method SHALL have @summary annotation in format: "Hapus {Resource}" (e.g., "Hapus Pengumuman")
6. WHEN a controller method handles a custom action endpoint THEN the method SHALL have @summary annotation describing the specific action in Bahasa Indonesia

### Requirement 3: PHPDoc Description Standardization

**User Story:** As an API consumer, I want detailed descriptions for complex endpoints, so that I understand the endpoint's purpose and behavior.

#### Acceptance Criteria

1. WHEN a controller method has authorization requirements THEN the @description SHALL mention required roles (e.g., "Hanya dapat diakses oleh Admin dan Instruktur")
2. WHEN a controller method has special business logic THEN the @description SHALL explain the behavior (e.g., "Soft delete - data tidak dihapus permanen")
3. WHEN a controller method triggers side effects THEN the @description SHALL document them (e.g., "Mengirim notifikasi email ke peserta terdaftar")
4. WHEN a controller method has prerequisites THEN the @description SHALL document them (e.g., "User harus terdaftar di kursus untuk mengakses endpoint ini")

### Requirement 4: Realistic Response Examples

**User Story:** As an API consumer, I want to see realistic response examples with actual field values, so that I understand the exact data format returned.

#### Acceptance Criteria

1. WHEN documenting a User response THEN the example SHALL include realistic values: `{"id": 1, "name": "Ahmad Rizki", "email": "ahmad.rizki@example.com", "username": "ahmadrizki", "status": "active", "email_verified_at": "2025-01-15T10:30:00.000000Z"}`
2. WHEN documenting a Course/Scheme response THEN the example SHALL include realistic values: `{"id": 1, "title": "Skema Kompetensi Web Developer", "slug": "skema-kompetensi-web-developer", "description": "Skema sertifikasi untuk pengembang web profesional", "status": "published"}`
3. WHEN documenting an Announcement response THEN the example SHALL include realistic values: `{"id": 1, "title": "Jadwal Ujian Sertifikasi Batch 2025", "content": "Ujian sertifikasi batch pertama akan dilaksanakan pada...", "priority": "high", "target_type": "all"}`
4. WHEN documenting a News response THEN the example SHALL include realistic values: `{"id": 1, "title": "Peluncuran Program Sertifikasi Baru", "slug": "peluncuran-program-sertifikasi-baru", "excerpt": "LSP meluncurkan program sertifikasi terbaru...", "is_featured": true}`
5. WHEN documenting pagination meta THEN the example SHALL include: `{"current_page": 1, "last_page": 5, "per_page": 15, "total": 75}`
6. WHEN documenting error responses THEN the example SHALL include specific Indonesian error messages relevant to the endpoint

### Requirement 5: Rate Limiting Documentation

**User Story:** As an API consumer, I want to know the rate limits for each endpoint group, so that I can implement proper request throttling in my application.

#### Acceptance Criteria

1. WHEN an endpoint uses throttle middleware THEN the API_Documentation_System SHALL document the rate limit in the endpoint description
2. WHEN documenting auth endpoints (login, register, password reset) THEN the documentation SHALL specify: "Rate limit: 10 requests per minute"
3. WHEN documenting enrollment endpoints (enroll, cancel, withdraw) THEN the documentation SHALL specify: "Rate limit: 5 requests per minute"
4. WHEN documenting general API endpoints THEN the documentation SHALL specify: "Rate limit: 60 requests per minute"
5. WHEN documenting rate limit THEN the description SHALL include the throttle group name if applicable

### Requirement 6: Rate Limit Headers Documentation

**User Story:** As an API consumer, I want to know what rate limit headers are returned, so that I can monitor my API usage.

#### Acceptance Criteria

1. WHEN any rate-limited endpoint is documented THEN the API_Documentation_System SHALL document response header `X-RateLimit-Limit` with description "Jumlah maksimum request yang diizinkan per periode"
2. WHEN any rate-limited endpoint is documented THEN the API_Documentation_System SHALL document response header `X-RateLimit-Remaining` with description "Jumlah request tersisa dalam periode saat ini"
3. WHEN any rate-limited endpoint is documented THEN the API_Documentation_System SHALL document response header `Retry-After` for 429 responses with description "Waktu dalam detik sebelum dapat mencoba lagi"
4. WHEN documenting 429 Too Many Requests response THEN the example SHALL include: `{"success": false, "message": "Terlalu banyak permintaan. Silakan coba lagi dalam {seconds} detik.", "data": null, "meta": {"retry_after": 60}, "errors": null}`

### Requirement 7: Authentication Documentation

**User Story:** As an API consumer, I want to clearly see which endpoints require authentication, so that I can implement proper authentication flow.

#### Acceptance Criteria

1. WHEN an endpoint requires authentication THEN the controller method SHALL NOT have @unauthenticated annotation (Scramble default is authenticated)
2. WHEN an endpoint is public (no auth required) THEN the controller method SHALL have @unauthenticated annotation
3. WHEN an endpoint requires specific roles THEN the @description SHALL specify: "Memerlukan role: {roles}" (e.g., "Memerlukan role: Admin, Instruktur")
4. WHEN documenting authenticated endpoints THEN the API_Documentation_System SHALL show security requirement `bearerAuth`

### Requirement 8: Query Parameters Documentation

**User Story:** As an API consumer, I want to see all available query parameters for list endpoints, so that I can filter, sort, and paginate data efficiently.

#### Acceptance Criteria

1. WHEN a list endpoint supports pagination THEN the documentation SHALL include `page` (integer, default: 1) and `per_page` (integer, default: 15) parameters
2. WHEN a list endpoint supports sorting THEN the documentation SHALL include `sort` parameter with allowed fields listed
3. WHEN a list endpoint supports filtering THEN the documentation SHALL include filter parameters with their types and allowed values
4. WHEN a list endpoint supports search THEN the documentation SHALL include `search` or `q` parameter with searchable fields listed
5. WHEN a query parameter accepts enum values THEN the documentation SHALL list all valid enum values

### Requirement 9: Enum Values Documentation

**User Story:** As an API consumer, I want to see all possible enum values with descriptions, so that I understand valid values for enum fields.

#### Acceptance Criteria

1. WHEN a field accepts status enum THEN the documentation SHALL list values with descriptions (e.g., "draft: Belum dipublikasikan", "published: Sudah dipublikasikan", "scheduled: Dijadwalkan")
2. WHEN a field accepts priority enum THEN the documentation SHALL list values: "low: Prioritas rendah", "normal: Prioritas normal", "high: Prioritas tinggi"
3. WHEN a field accepts target_type enum THEN the documentation SHALL list values: "all: Semua pengguna", "role: Berdasarkan role", "course: Berdasarkan kursus"
4. WHEN a field accepts enrollment_status enum THEN the documentation SHALL list values: "pending: Menunggu persetujuan", "active: Aktif", "completed: Selesai", "cancelled: Dibatalkan"

### Requirement 10: Tag Groups Configuration

**User Story:** As an API consumer, I want endpoints grouped logically in the documentation sidebar, so that I can navigate and find related endpoints easily.

#### Acceptance Criteria

1. WHEN configuring Scramble THEN the system SHALL define tag groups in AppServiceProvider or dedicated config
2. WHEN organizing endpoints THEN the API_Documentation_System SHALL group by functional domain: Autentikasi, Profil, Kursus & Skema, Pendaftaran, Pembelajaran, Forum, Gamifikasi, Konten, Notifikasi, Sistem
3. WHEN naming tags THEN the API_Documentation_System SHALL use consistent Bahasa Indonesia naming
4. WHEN a tag group contains multiple tags THEN they SHALL be ordered logically (e.g., CRUD order: List, Create, Detail, Update, Delete)

### Requirement 11: Server Configuration

**User Story:** As an API consumer, I want to see different server environments in the documentation, so that I can test against the appropriate environment.

#### Acceptance Criteria

1. WHEN configuring Scramble THEN the system SHALL define at least two servers: Local Development and Production
2. WHEN defining Local Development server THEN the URL SHALL be configurable via APP_URL environment variable
3. WHEN defining Production server THEN the URL SHALL be configurable via API_PRODUCTION_URL environment variable
4. WHEN selecting a server in Scalar THEN the Try It feature SHALL use the selected server URL

### Requirement 12: Remove Redundant Documentation Files

**User Story:** As a developer, I want a single source of truth for API documentation, so that documentation stays synchronized with implementation.

#### Acceptance Criteria

1. WHEN Scramble auto-generates documentation THEN manual OpenAPI files (e.g., `Modules/Content/openapi.yaml`) SHALL be deprecated or removed
2. WHEN module has separate API_DOCUMENTATION.md THEN it SHALL reference Scalar URL instead of duplicating endpoint documentation
3. WHEN updating API endpoints THEN developers SHALL only need to update PHPDoc annotations, not separate documentation files

### Requirement 13: Error Response Standardization

**User Story:** As an API consumer, I want consistent error responses across all endpoints, so that I can implement unified error handling.

#### Acceptance Criteria

1. WHEN documenting 400 Bad Request THEN the example SHALL be: `{"success": false, "message": "Request tidak valid", "data": null, "meta": null, "errors": {"field": ["Pesan error spesifik"]}}`
2. WHEN documenting 401 Unauthorized THEN the example SHALL be: `{"success": false, "message": "Token tidak valid atau tidak ada", "data": null, "meta": null, "errors": null}`
3. WHEN documenting 403 Forbidden THEN the example SHALL be: `{"success": false, "message": "Anda tidak memiliki akses untuk melakukan operasi ini", "data": null, "meta": null, "errors": null}`
4. WHEN documenting 404 Not Found THEN the example SHALL be: `{"success": false, "message": "{Resource} tidak ditemukan", "data": null, "meta": null, "errors": null}`
5. WHEN documenting 422 Validation Error THEN the example SHALL include field-specific errors: `{"success": false, "message": "Data yang Anda kirim tidak valid", "data": null, "meta": null, "errors": {"title": ["Judul wajib diisi"], "content": ["Konten minimal 10 karakter"]}}`
6. WHEN documenting 500 Server Error THEN the example SHALL be: `{"success": false, "message": "Terjadi kesalahan pada server", "data": null, "meta": null, "errors": null}`

### Requirement 14: Module Coverage

**User Story:** As a developer, I want all modules to have consistent PHPDoc documentation, so that Scramble generates complete API documentation.

#### Acceptance Criteria

1. WHEN auditing Auth module controllers THEN all public methods SHALL have @summary and appropriate @response annotations
2. WHEN auditing Schemes module controllers THEN all public methods SHALL have @summary and appropriate @response annotations
3. WHEN auditing Enrollments module controllers THEN all public methods SHALL have @summary and appropriate @response annotations
4. WHEN auditing Content module controllers THEN all public methods SHALL have @summary and appropriate @response annotations
5. WHEN auditing Forums module controllers THEN all public methods SHALL have @summary and appropriate @response annotations
6. WHEN auditing Learning module controllers THEN all public methods SHALL have @summary and appropriate @response annotations
7. WHEN auditing Gamification module controllers THEN all public methods SHALL have @summary and appropriate @response annotations
8. WHEN auditing Notifications module controllers THEN all public methods SHALL have @summary and appropriate @response annotations
9. WHEN auditing Common module controllers THEN all public methods SHALL have @summary and appropriate @response annotations
10. WHEN auditing Search module controllers THEN all public methods SHALL have @summary and appropriate @response annotations

