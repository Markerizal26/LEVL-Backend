# [MODULE_NAME] Module - API Documentation

## Overview

[Deskripsi singkat module - apa yang dikelola dan untuk siapa]

## Base URL

```
/api/v1
```

## Authentication

Semua endpoint memerlukan autentikasi JWT kecuali disebutkan sebaliknya.

```
Authorization: Bearer {token}
```

---

## Endpoints

### 1. [Nama Endpoint]

[Deskripsi singkat]

**Endpoint:** `[METHOD] /resource`

**Requires:** [Roles jika ada: Admin, Instructor, Student]

**Query Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `per_page` | integer | No | Items per page (default: 15) |
| `page` | integer | No | Page number |

**Request Body:**

```json
{
  "field": "value"
}
```

**Response (200 Success):**

```json
{
  "success": true,
  "message": "Success message",
  "data": {
    "id": 1,
    "field": "value"
  }
}
```

**Error Responses:**

- `401` - Unauthorized
- `403` - Forbidden
- `404` - Not Found
- `422` - Validation Error

---

## Error Response Format

### 401 Unauthorized

```json
{
  "success": false,
  "message": "Tidak terotorisasi."
}
```

### 403 Forbidden

```json
{
  "success": false,
  "message": "Anda tidak memiliki akses."
}
```

### 404 Not Found

```json
{
  "success": false,
  "message": "Resource tidak ditemukan."
}
```

### 422 Validation Error

```json
{
  "success": false,
  "message": "Validasi gagal.",
  "errors": {
    "field": ["Field wajib diisi."]
  }
}
```

---

## Notes

- Semua timestamp menggunakan format ISO 8601 (UTC)
- Pagination menggunakan format Laravel standard
- Soft delete digunakan untuk penghapusan data
