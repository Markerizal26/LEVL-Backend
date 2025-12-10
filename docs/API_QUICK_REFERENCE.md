# API Documentation Quick Reference

## 🎯 Akses Dokumentasi

### Local Development

```
http://localhost:8000/docs/api
```

### OpenAPI Specification

```
http://localhost:8000/api.json
```

## 📝 Format Dokumentasi yang Digunakan

### PHPDoc Tags yang Supported

```php
/**
 * Judul Endpoint
 *
 * Deskripsi lengkap tentang apa yang endpoint ini lakukan.
 *
 * @summary Judul Singkat (max 50 char)
 * 
 * @queryParam name type Description. Example: value
 * @queryParam name type optional Description. Example: value
 * 
 * @bodyParam name type required Description. Example: value
 * @bodyParam name type optional Description. Example: value
 * 
 * @response 200 scenario="Success" {"json": "example"}
 * @response 401 scenario="Unauthorized" {"error": "message"}
 * @response 422 scenario="Validation Error" {"errors": {}}
 * 
 * @authenticated
 * @unauthenticated
 * @role Admin|Instructor
 */
```

## 🔑 Authentication

### JWT Bearer Token

Semua protected endpoint memerlukan header:

```
Authorization: Bearer {access_token}
```

### Mendapatkan Token

```http
POST /api/v1/auth/login
Content-Type: application/json

{
  "login": "user@example.com",
  "password": "password123"
}
```

Response:

```json
{
  "success": true,
  "data": {
    "access_token": "eyJ0eXAi...",
    "refresh_token": "abc123...",
    "expires_in": 900
  }
}
```

### Refresh Token

```http
POST /api/v1/auth/refresh
X-Refresh-Token: {refresh_token}
```

## 📊 Response Format

### Success Response

```json
{
  "success": true,
  "message": "Operation successful",
  "data": { ... }
}
```

### Paginated Response

```json
{
  "success": true,
  "data": [ ... ],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 100,
    "last_page": 7
  },
  "links": {
    "first": "...",
    "last": "...",
    "prev": null,
    "next": "..."
  }
}
```

### Error Response

```json
{
  "success": false,
  "message": "Error description",
  "errors": {
    "field": ["Validation error message"]
  }
}
```

## 🔍 Query Parameters

### Filtering

```
?filter[field]=value
?filter[status]=active
?filter[category_id]=1
```

### Sorting

```
?sort=field          (ascending)
?sort=-field         (descending)
?sort=-created_at    (newest first)
```

### Pagination

```
?page=1
?per_page=15
```

### Search

```
?search=keyword
```

### Include Relations

```
?include=relation1,relation2
?include=instructor,category
```

## 🚦 Rate Limiting

| Endpoint Type  | Limit              |
| -------------- | ------------------ |
| Default API    | 60 requests/minute |
| Auth endpoints | 10 requests/minute |
| Enrollment     | 5 requests/minute  |

### Rate Limit Headers

Response includes:

```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 45
X-RateLimit-Reset: 1234567890
```

### Rate Limited Response (429)

```json
{
  "success": false,
  "message": "Terlalu banyak percobaan. Silakan coba lagi dalam 60 detik."
}
```

## 🏷️ Tag Groups

Dokumentasi diorganisir dalam groups:

1. **Autentikasi & Pengguna**
   - Autentikasi
   - Profil Pengguna
   - Manajemen Pengguna

2. **Pembelajaran**
   - Skema & Kursus
   - Unit Kompetensi
   - Materi Pembelajaran
   - Progress Belajar

3. **Tugas & Penilaian**
   - Tugas & Pengumpulan
   - Penilaian

4. **Interaksi**
   - Forum Diskusi
   - Notifikasi
   - Gamifikasi

5. **Konten & Data**
   - Konten & Berita
   - Data Master
   - Pencarian

6. **Administrasi**
   - Pendaftaran Kursus
   - Laporan & Statistik

## 🛠️ Commands

### Export OpenAPI Spec

```bash
php artisan scramble:export
```

### Validate API Documentation

```bash
php scripts/validate-api-docs.php
```

### Clear API Cache

```bash
php artisan cache:clear
php artisan config:clear
```

## 📋 HTTP Status Codes

| Code | Meaning               | Usage                                |
| ---- | --------------------- | ------------------------------------ |
| 200  | OK                    | Successful GET, PUT, PATCH, DELETE   |
| 201  | Created               | Successful POST                      |
| 204  | No Content            | Successful DELETE (no response body) |
| 400  | Bad Request           | Invalid request format               |
| 401  | Unauthorized          | Missing or invalid authentication    |
| 403  | Forbidden             | Authenticated but no permission      |
| 404  | Not Found             | Resource not found                   |
| 422  | Unprocessable Entity  | Validation failed                    |
| 429  | Too Many Requests     | Rate limit exceeded                  |
| 500  | Internal Server Error | Server error                         |

## 📖 Master Data Endpoints

### Get Available Master Data Types

```http
GET /api/v1/master-data
```

### Get Enum Values

```http
GET /api/v1/master-data/user-statuses
GET /api/v1/master-data/roles
GET /api/v1/master-data/course-statuses
GET /api/v1/master-data/course-types
GET /api/v1/master-data/enrollment-types
```

Response format:

```json
{
  "success": true,
  "message": "Daftar ...",
  "data": [
    {
      "value": "active",
      "label": "Aktif"
    }
  ]
}
```

## 🔎 Search Endpoints

### Search Courses

```http
GET /api/v1/search?query=Laravel&category_id=1&level_tag=beginner
```

Parameters:

- `query` - Search keyword
- `category_id` - Filter by category
- `level_tag` - Filter by level (beginner|intermediate|advanced)
- `instructor_id` - Filter by instructor
- `status` - Filter by status
- `sort_by` - Sort field (relevance|created_at|title|rating)
- `sort_direction` - Sort direction (asc|desc)
- `page` - Page number
- `per_page` - Items per page

### Autocomplete

```http
GET /api/v1/search/autocomplete?query=Lar&limit=5
```

### Search History

```http
GET /api/v1/search/history?limit=10
```

### Clear Search History

```http
DELETE /api/v1/search/history?id=1
DELETE /api/v1/search/history  (clear all)
```

## ✅ Best Practices

### 1. Always Include Error Handling

```javascript
try {
  const response = await fetch("/api/v1/endpoint", {
    headers: {
      Authorization: `Bearer ${token}`,
      "Content-Type": "application/json",
    },
  });

  if (response.status === 401) {
    // Refresh token or redirect to login
  }

  if (response.status === 429) {
    // Handle rate limiting
  }

  const data = await response.json();
} catch (error) {
  // Handle network errors
}
```

### 2. Token Management

- Store access token securely (httpOnly cookie or secure storage)
- Implement auto-refresh before token expires
- Handle 401 responses by refreshing token
- Clear tokens on logout

### 3. Pagination

- Always specify `per_page` to control data size
- Check `meta.last_page` for total pages
- Use `links.next` for easy pagination

### 4. Filtering & Sorting

- Use multiple filters: `?filter[status]=active&filter[category_id]=1`
- Sort descending with `-`: `?sort=-created_at`
- Combine with search: `?search=keyword&filter[status]=active`

## 📚 Additional Resources

- [API Documentation Template](API_DOCUMENTATION_TEMPLATE.md)
- [Architecture Guide](ARCHITECTURE.md)
- [Improvement Plan](API_DOCS_IMPROVEMENT_PLAN.md)
- [Fixes Summary](API_DOCS_FIXES_SUMMARY.md)

## 🆘 Troubleshooting

### Documentation Not Showing

1. Clear cache: `php artisan cache:clear`
2. Clear config: `php artisan config:clear`
3. Re-export: `php artisan scramble:export`

### Authentication Issues

1. Check token format: `Bearer {token}`
2. Verify token not expired
3. Check user has required role
4. Ensure route is in `api/*` path

### Rate Limiting

1. Check `X-RateLimit-*` headers
2. Implement exponential backoff
3. Cache responses when possible
4. Use appropriate rate limiter group

---

**Documentation Version**: 1.0.0  
**Last Updated**: December 10, 2025  
**Scramble Version**: Latest
