# Design Document: API Documentation Standardization

## Overview

Dokumen ini menjelaskan desain teknis untuk menstandarisasi dokumentasi API menggunakan Scramble auto-generate. Implementasi mencakup konfigurasi Scramble, PHPDoc standards, realistic examples, rate limiting documentation, dan penghapusan dokumentasi redundan.

## Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                    Documentation Flow                            │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  Controller ──► PHPDoc Annotations ──► Scramble ──► OpenAPI Spec│
│      │                                      │                    │
│      ▼                                      ▼                    │
│  FormRequest                           Scalar UI                 │
│  (validation rules)                    (interactive docs)        │
│                                                                  │
│  Components:                                                     │
│  - PHPDoc: @summary, @description, @response, @unauthenticated  │
│  - Scramble Config: servers, tag groups, extensions             │
│  - ApiResponse Trait: standard response format                  │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

## Components and Interfaces

### 1. Scramble Configuration (`config/scramble.php`)

Konfigurasi utama untuk Scramble dengan penambahan:
- Multiple servers (Local, Production)
- Tag groups untuk navigasi
- Custom extensions untuk rate limiting headers

### 2. PHPDoc Annotation Standards

Format standar untuk semua controller methods:

```php
/**
 * @summary Daftar Pengumuman
 * @description Mendapatkan daftar pengumuman untuk user yang sedang login.
 * Mendukung filter berdasarkan course_id, priority, dan status baca.
 * Rate limit: 60 requests per minute.
 * 
 * @queryParam page integer Nomor halaman. Default: 1. Example: 1
 * @queryParam per_page integer Jumlah item per halaman. Default: 15. Example: 15
 * @queryParam course_id integer Filter berdasarkan ID kursus. Example: 1
 * @queryParam priority string Filter berdasarkan prioritas. Enum: low, normal, high. Example: high
 * @queryParam unread boolean Filter pengumuman yang belum dibaca. Example: true
 * 
 * @response 200 scenario="Success" {"success":true,"message":"Berhasil","data":[{"id":1,"title":"Jadwal Ujian Sertifikasi Batch 2025","content":"Ujian sertifikasi batch pertama akan dilaksanakan pada...","priority":"high","target_type":"all","published_at":"2025-01-15T10:00:00.000000Z","author":{"id":1,"name":"Admin LSP"}}],"meta":{"pagination":{"current_page":1,"per_page":15,"total":25,"last_page":2}},"errors":null}
 * @response 401 scenario="Unauthorized" {"success":false,"message":"Token tidak valid atau tidak ada","data":null,"meta":null,"errors":null}
 */
```

### 3. Rate Limiting Middleware Groups

| Group | Limit | Endpoints |
|-------|-------|-----------|
| `auth` | 10/minute | login, register, password reset, verify email |
| `enrollment` | 5/minute | enroll, unenroll, cancel enrollment |
| `api` | 60/minute | All other authenticated endpoints |

### 4. Response Schema Components

Standard response schemas untuk reuse:

```yaml
components:
  schemas:
    SuccessResponse:
      type: object
      properties:
        success:
          type: boolean
          example: true
        message:
          type: string
          example: "Berhasil"
        data:
          type: object
          nullable: true
        meta:
          type: object
          nullable: true
        errors:
          type: object
          nullable: true
          
    ErrorResponse:
      type: object
      properties:
        success:
          type: boolean
          example: false
        message:
          type: string
        data:
          type: object
          nullable: true
        meta:
          type: object
          nullable: true
        errors:
          type: object
          nullable: true
          
    PaginationMeta:
      type: object
      properties:
        pagination:
          type: object
          properties:
            current_page:
              type: integer
              example: 1
            per_page:
              type: integer
              example: 15
            total:
              type: integer
              example: 75
            last_page:
              type: integer
              example: 5
            from:
              type: integer
              example: 1
            to:
              type: integer
              example: 15
            has_next:
              type: boolean
              example: true
            has_prev:
              type: boolean
              example: false
```

## Data Models

### PHPDoc Annotation Template per Endpoint Type

#### List Endpoint
```php
/**
 * @summary Daftar {Resource}
 * @description Mendapatkan daftar {resource} dengan pagination.
 * {Authorization info}. Rate limit: {limit} requests per minute.
 * 
 * @queryParam page integer Nomor halaman. Default: 1. Example: 1
 * @queryParam per_page integer Jumlah item per halaman. Default: 15. Example: 15
 * @queryParam sort string Field untuk sorting. Example: -created_at
 * @queryParam search string Kata kunci pencarian. Example: sertifikasi
 * 
 * @response 200 scenario="Success" {realistic example}
 * @response 401 scenario="Unauthorized" {error example}
 */
```

#### Create Endpoint
```php
/**
 * @summary Buat {Resource} Baru
 * @description Membuat {resource} baru. {Authorization info}.
 * Rate limit: {limit} requests per minute.
 * 
 * @response 201 scenario="Created" {realistic example}
 * @response 401 scenario="Unauthorized" {error example}
 * @response 403 scenario="Forbidden" {error example}
 * @response 422 scenario="Validation Error" {validation error example}
 */
```

#### Detail Endpoint
```php
/**
 * @summary Detail {Resource}
 * @description Mendapatkan detail {resource} berdasarkan ID.
 * {Authorization info}. Rate limit: {limit} requests per minute.
 * 
 * @response 200 scenario="Success" {realistic example}
 * @response 401 scenario="Unauthorized" {error example}
 * @response 404 scenario="Not Found" {error example}
 */
```

#### Update Endpoint
```php
/**
 * @summary Perbarui {Resource}
 * @description Memperbarui data {resource}. {Authorization info}.
 * Rate limit: {limit} requests per minute.
 * 
 * @response 200 scenario="Success" {realistic example}
 * @response 401 scenario="Unauthorized" {error example}
 * @response 403 scenario="Forbidden" {error example}
 * @response 404 scenario="Not Found" {error example}
 * @response 422 scenario="Validation Error" {validation error example}
 */
```

#### Delete Endpoint
```php
/**
 * @summary Hapus {Resource}
 * @description Menghapus {resource} (soft delete). {Authorization info}.
 * Rate limit: {limit} requests per minute.
 * 
 * @response 200 scenario="Success" {success example}
 * @response 401 scenario="Unauthorized" {error example}
 * @response 403 scenario="Forbidden" {error example}
 * @response 404 scenario="Not Found" {error example}
 */
```

### Realistic Example Data

#### User Examples
```json
{
  "id": 1,
  "name": "Ahmad Rizki Pratama",
  "email": "ahmad.rizki@example.com",
  "username": "ahmadrizki",
  "status": "active",
  "email_verified_at": "2025-01-15T10:30:00.000000Z",
  "created_at": "2025-01-01T08:00:00.000000Z",
  "roles": ["Student"]
}
```

#### Course/Scheme Examples
```json
{
  "id": 1,
  "title": "Skema Kompetensi Junior Web Developer",
  "slug": "skema-kompetensi-junior-web-developer",
  "description": "Skema sertifikasi untuk pengembang web tingkat junior yang mencakup HTML, CSS, JavaScript, dan dasar-dasar backend.",
  "status": "published",
  "enrollment_type": "open",
  "difficulty_level": "beginner",
  "instructor": {
    "id": 2,
    "name": "Dr. Budi Santoso"
  },
  "category": {
    "id": 1,
    "name": "Teknologi Informasi"
  },
  "created_at": "2025-01-01T08:00:00.000000Z"
}
```

#### Announcement Examples
```json
{
  "id": 1,
  "title": "Jadwal Ujian Sertifikasi Batch Januari 2025",
  "content": "Kepada seluruh peserta sertifikasi, dengan ini kami informasikan bahwa ujian sertifikasi batch Januari 2025 akan dilaksanakan pada tanggal 25-27 Januari 2025. Pastikan Anda telah menyelesaikan seluruh materi pembelajaran sebelum mengikuti ujian.",
  "priority": "high",
  "target_type": "all",
  "status": "published",
  "published_at": "2025-01-10T09:00:00.000000Z",
  "author": {
    "id": 1,
    "name": "Admin LSP"
  }
}
```

#### News Examples
```json
{
  "id": 1,
  "title": "LSP Meluncurkan Program Sertifikasi Cloud Computing",
  "slug": "lsp-meluncurkan-program-sertifikasi-cloud-computing",
  "excerpt": "Dalam rangka memenuhi kebutuhan industri akan tenaga kerja terampil di bidang cloud computing, LSP resmi meluncurkan program sertifikasi baru.",
  "content": "...",
  "is_featured": true,
  "views_count": 1250,
  "published_at": "2025-01-08T14:00:00.000000Z",
  "author": {
    "id": 1,
    "name": "Tim Redaksi LSP"
  },
  "categories": [
    {"id": 1, "name": "Berita Terbaru"}
  ]
}
```

#### Error Response Examples
```json
// 401 Unauthorized
{
  "success": false,
  "message": "Token tidak valid atau tidak ada",
  "data": null,
  "meta": null,
  "errors": null
}

// 403 Forbidden
{
  "success": false,
  "message": "Anda tidak memiliki akses untuk melakukan operasi ini",
  "data": null,
  "meta": null,
  "errors": null
}

// 404 Not Found
{
  "success": false,
  "message": "Pengumuman tidak ditemukan",
  "data": null,
  "meta": null,
  "errors": null
}

// 422 Validation Error
{
  "success": false,
  "message": "Data yang Anda kirim tidak valid. Periksa kembali isian Anda.",
  "data": null,
  "meta": null,
  "errors": {
    "title": ["Judul wajib diisi"],
    "content": ["Konten minimal 10 karakter"],
    "priority": ["Prioritas harus salah satu dari: low, normal, high"]
  }
}

// 429 Too Many Requests
{
  "success": false,
  "message": "Terlalu banyak permintaan. Silakan coba lagi dalam 60 detik.",
  "data": null,
  "meta": {
    "retry_after": 60
  },
  "errors": null
}
```

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system-essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property 1: Response Format Consistency
*For any* API endpoint response, the JSON structure SHALL contain exactly these top-level fields: `success` (boolean), `message` (string), `data` (object|array|null), `meta` (object|null), `errors` (object|null)
**Validates: Requirements 1.1, 1.2, 1.3, 1.4**

### Property 2: PHPDoc Summary Format
*For any* controller method handling an API endpoint, the @summary annotation SHALL follow the naming convention: "Daftar {Resource}" for GET list, "Detail {Resource}" for GET single, "Buat {Resource} Baru" for POST, "Perbarui {Resource}" for PUT/PATCH, "Hapus {Resource}" for DELETE
**Validates: Requirements 2.1, 2.2, 2.3, 2.4, 2.5**

### Property 3: Realistic Examples
*For any* response example in PHPDoc annotations, the example SHALL NOT contain generic placeholders like "Example", "string", "test", or single-word values, but SHALL contain realistic Indonesian names, titles, and contextual data
**Validates: Requirements 4.1, 4.2, 4.3, 4.4, 4.5, 4.6**

### Property 4: Rate Limit Documentation
*For any* endpoint with throttle middleware, the @description SHALL include rate limit information in format "Rate limit: {N} requests per minute"
**Validates: Requirements 5.1, 5.2, 5.3, 5.4, 5.5**

### Property 5: Authentication Annotation
*For any* public endpoint (without auth middleware), the controller method SHALL have @unauthenticated annotation; for authenticated endpoints, the annotation SHALL NOT be present
**Validates: Requirements 7.1, 7.2, 7.3, 7.4**

### Property 6: Pagination Parameters
*For any* list endpoint returning paginated data, the PHPDoc SHALL document `page` and `per_page` query parameters with types, defaults, and examples
**Validates: Requirements 8.1, 8.2, 8.3, 8.4, 8.5**

### Property 7: Error Response Coverage
*For any* authenticated endpoint, the PHPDoc SHALL document at minimum: 200/201 success, 401 unauthorized, and relevant error codes (403, 404, 422) based on endpoint behavior
**Validates: Requirements 13.1, 13.2, 13.3, 13.4, 13.5, 13.6**

### Property 8: Module PHPDoc Coverage
*For any* public controller method in any module, the method SHALL have at minimum @summary annotation in Bahasa Indonesia
**Validates: Requirements 14.1-14.10**

## Error Handling

| Scenario | PHPDoc Annotation | HTTP Code |
|----------|-------------------|-----------|
| Success | @response 200 | 200 |
| Created | @response 201 | 201 |
| Bad Request | @response 400 | 400 |
| Unauthorized | @response 401 | 401 |
| Forbidden | @response 403 | 403 |
| Not Found | @response 404 | 404 |
| Validation Error | @response 422 | 422 |
| Rate Limited | @response 429 | 429 |
| Server Error | @response 500 | 500 |

## Testing Strategy

### Dual Testing Approach

Testing akan menggunakan kombinasi unit tests dan property-based tests:

1. **Unit Tests**: Memverifikasi contoh spesifik dan edge cases
2. **Property-Based Tests**: Memverifikasi properti universal yang harus berlaku di semua input

### Property-Based Testing Library

Menggunakan **Pest PHP** dengan plugin **pest-plugin-faker** untuk property-based testing.

### Test Categories

#### 1. PHPDoc Annotation Tests
- Verify all controller methods have @summary
- Verify @summary follows naming convention
- Verify @response annotations exist for common status codes

#### 2. Response Format Tests
- Verify generated OpenAPI spec uses correct response schema
- Verify all examples use `success` field (not `status`)

#### 3. Rate Limit Documentation Tests
- Verify endpoints with throttle middleware have rate limit in description

#### 4. Configuration Tests
- Verify Scramble config has servers defined
- Verify tag groups are configured

### Test Implementation Notes

- Property tests MUST be tagged with: `**Feature: api-documentation-standardization, Property {number}: {property_text}**`
- Each correctness property MUST be implemented by a SINGLE property-based test
- Tests should run minimum 100 iterations for property-based tests

