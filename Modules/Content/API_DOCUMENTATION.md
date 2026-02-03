# Content Module API Documentation

## Overview

Content Module menyediakan API untuk mengelola pengumuman (announcements) dan berita (news) dalam platform LMS.

## Base URL

```
/api/v1
```

## Authentication

Semua endpoint memerlukan autentikasi menggunakan JWT token.

```
Authorization: Bearer {token}
```

---

## Announcements

### 1. Get All Announcements

Mendapatkan daftar pengumuman untuk user yang sedang login.

**Endpoint:** `GET /announcements`

**Query Parameters:**
- `filter[course_id]` (optional, integer): ID kursus
- `filter[priority]` (optional, string): `low` | `normal` | `high`
- `filter[unread]` (optional, boolean): `true` | `false`
- `sort` (optional, string): `created_at` | `published_at` (gunakan prefix `-` untuk descending)
- `page` (optional, integer): default 1
- `per_page` (optional, integer): default 15

**Response:**
```json
{
  "success": true,
  "message": "Berhasil",
  "data": [
    {
      "id": 1,
      "title": "Selamat Datang",
      "content": "Selamat datang di platform...",
      "status": "published",
      "target_type": "all",
      "priority": "high",
      "published_at": "2025-12-01T10:00:00.000000Z",
      "author": {
        "id": 1,
        "name": "Admin"
      }
    }
  ],
  "meta": {
    "pagination": {
      "current_page": 1,
      "last_page": 5,
      "per_page": 15,
      "total": 75
    }
  }
}
```

### 2. Create Announcement

Membuat pengumuman baru (role: Superadmin/Admin/Instructor).

**Endpoint:** `POST /announcements`

**JSON Body (raw):**
```json
{
  "title": "Pengumuman Penting",
  "content": "Ini adalah pengumuman penting...",
  "course_id": 1,
  "target_type": "all",
  "target_value": null,
  "priority": "normal",
  "status": "draft",
  "scheduled_at": "2026-02-10T10:00:00Z"
}
```

**Field Rules:**
- `title` (required, string, max 255)
- `content` (required, string)
- `course_id` (optional, integer, exists:courses,id)
- `target_type` (required, string): `all` | `role` | `course`
- `target_value` (optional, string, max 255)
- `priority` (optional, string): `low` | `normal` | `high`
- `status` (optional, string): `draft` | `published` | `scheduled`
- `scheduled_at` (optional, date, after now) — gunakan saat `status=scheduled`

**Response:**
```json
{
  "success": true,
  "message": "Pengumuman berhasil dibuat.",
  "data": {
    "announcement": {
      "id": 1,
      "title": "Pengumuman Penting",
      "status": "draft"
    }
  }
}
```

### 3. Get Announcement Detail

Mendapatkan detail pengumuman (otomatis mark as read dan increment views).

**Endpoint:** `GET /announcements/{id}`

**Response:**
```json
{
  "success": true,
  "data": {
    "announcement": {
      "id": 1,
      "title": "Pengumuman Penting",
      "content": "Ini adalah pengumuman...",
      "author": {
        "id": 1,
        "name": "Admin"
      },
      "revisions": []
    }
  }
}
```

### 4. Update Announcement

Memperbarui pengumuman (role: Superadmin/Admin/Instructor, author).

**Endpoint:** `PUT /announcements/{id}`

**JSON Body (raw):**
```json
{
  "title": "Pengumuman Updated",
  "content": "Konten yang diupdate...",
  "target_type": "role",
  "target_value": "Instructor",
  "priority": "high"
}
```

**Field Rules:**
- `title` (sometimes|required, string, max 255)
- `content` (sometimes|required, string)
- `target_type` (sometimes|required, string): `all` | `role` | `course`
- `target_value` (optional, string, max 255)
- `priority` (optional, string): `low` | `normal` | `high`

### 5. Delete Announcement

Menghapus pengumuman (soft delete).

**Endpoint:** `DELETE /announcements/{id}`

**Body:** tidak ada

**Response:**
```json
{
  "success": true,
  "message": "Pengumuman berhasil dihapus."
}
```

### 6. Publish Announcement

Mempublikasikan pengumuman.

**Endpoint:** `POST /announcements/{id}/publish`

**Body:** tidak ada

**Response:**
```json
{
  "success": true,
  "message": "Pengumuman berhasil dipublikasikan.",
  "data": {
    "id": 1,
    "status": "published",
    "published_at": "2025-12-02T10:00:00.000000Z"
  }
}
```

### 7. Schedule Announcement

Menjadwalkan publikasi pengumuman.

**Endpoint:** `POST /announcements/{id}/schedule`

**JSON Body (raw):**
```json
{
  "scheduled_at": "2026-02-10T10:00:00Z"
}
```

**Field Rules:**
- `scheduled_at` (required, date, after now)

### 8. Mark as Read

Menandai pengumuman sudah dibaca.

**Endpoint:** `POST /announcements/{id}/read`

**Body:** tidak ada

**Response:**
```json
{
  "success": true,
  "message": "Pengumuman ditandai sudah dibaca."
}
```

---

## News

### 1. Get All News

Mendapatkan daftar berita.

**Endpoint:** `GET /news`

**Query Parameters:**
- `filter[category_id]` (optional, integer)
- `filter[tag_id]` (optional, integer)
- `filter[featured]` (optional, boolean): `true` | `false`
- `filter[date_from]` (optional, string, format Y-m-d)
- `filter[date_to]` (optional, string, format Y-m-d)
- `sort` (optional, string): `created_at` | `published_at` | `views_count` (prefix `-` untuk descending)
- `page` (optional, integer): default 1
- `per_page` (optional, integer): default 15

**Response:**
```json
{
  "success": true,
  "message": "Berhasil",
  "data": [
    {
      "id": 1,
      "title": "Berita Terbaru",
      "slug": "berita-terbaru",
      "excerpt": "Ringkasan berita...",
      "is_featured": true,
      "views_count": 150,
      "published_at": "2025-12-01T10:00:00.000000Z",
      "author": {
        "id": 1,
        "name": "Admin"
      },
      "categories": []
    }
  ],
  "meta": {
    "pagination": {
      "current_page": 1,
      "last_page": 5,
      "per_page": 15,
      "total": 75
    }
  }
}
```

### 2. Create News

Membuat berita baru (role: Superadmin/Admin/Instructor).

**Endpoint:** `POST /news`

**JSON Body (raw):**
```json
{
  "title": "Berita Baru",
  "slug": "berita-baru",
  "excerpt": "Ringkasan berita...",
  "content": "Konten lengkap berita...",
  "is_featured": false,
  "status": "draft",
  "scheduled_at": "2026-02-10T10:00:00Z",
  "category_ids": [1, 2],
  "tag_ids": [1, 2, 3]
}
```

**Multipart Form-Data (untuk upload gambar):**
- `featured_image` (file, image, max 5MB)
- field lain sama dengan JSON di atas

**Field Rules:**
- `title` (required, string, max 255)
- `slug` (optional, string, max 255, unique:news,slug)
- `excerpt` (optional, string)
- `content` (required, string)
- `featured_image` (optional, image, max 5120 KB)
- `is_featured` (optional, boolean)
- `status` (optional, string): `draft` | `published` | `scheduled`
- `scheduled_at` (optional, date, after now) — gunakan saat `status=scheduled`
- `category_ids` (optional, array)
- `category_ids.*` (exists:content_categories,id)
- `tag_ids` (optional, array)
- `tag_ids.*` (exists:tags,id)

### 3. Get News Detail

Mendapatkan detail berita.

**Endpoint:** `GET /news/{slug}`

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "title": "Berita Terbaru",
    "content": "Konten lengkap...",
    "views_count": 151,
    "author": {
      "id": 1,
      "name": "Admin"
    },
    "categories": [],
    "tags": []
  }
}
```

### 4. Update News

Mengupdate berita.

**Endpoint:** `PUT /news/{slug}`

**JSON Body (raw):**
```json
{
  "title": "Berita Updated",
  "content": "Konten yang diupdate...",
  "excerpt": "Ringkasan update",
  "is_featured": true,
  "category_ids": [1, 3],
  "tag_ids": [2, 4]
}
```

**Multipart Form-Data (opsional):**
- `featured_image` (file, image, max 5MB)
- field lain sama dengan JSON di atas

**Field Rules:**
- `title` (sometimes|required, string, max 255)
- `content` (sometimes|required, string)
- `excerpt` (optional, string)
- `featured_image` (optional, image, max 5120 KB)
- `is_featured` (optional, boolean)
- `category_ids` (optional, array)
- `category_ids.*` (exists:content_categories,id)
- `tag_ids` (optional, array)
- `tag_ids.*` (exists:tags,id)

### 5. Delete News

Menghapus berita (soft delete).

**Endpoint:** `DELETE /news/{slug}`

**Body:** tidak ada

### 6. Publish News

Mempublikasikan berita.

**Endpoint:** `POST /news/{slug}/publish`

**Body:** tidak ada

### 7. Schedule News

Menjadwalkan publikasi berita.

**Endpoint:** `POST /news/{slug}/schedule`

**JSON Body (raw):**
```json
{
  "scheduled_at": "2026-02-10T10:00:00Z"
}
```

**Field Rules:**
- `scheduled_at` (required, date, after now)

### 8. Get Trending News

Mendapatkan berita trending.

**Endpoint:** `GET /news/trending`

**Query Parameters:**
- `limit` (optional, integer): default 10

---

## Course Announcements

### 1. Get Course Announcements

Mendapatkan pengumuman untuk kursus tertentu.

**Endpoint:** `GET /courses/{course}/announcements`

**Query Parameters:**
- `page` (optional, integer): default 1
- `per_page` (optional, integer): default 15

### 2. Create Course Announcement

Membuat pengumuman untuk kursus (role: Superadmin/Admin/Instructor).

**Endpoint:** `POST /courses/{course}/announcements`

**JSON Body (raw):**
```json
{
  "title": "Pengumuman Kursus",
  "content": "Konten pengumuman...",
  "target_type": "course",
  "target_value": null,
  "priority": "normal",
  "status": "published",
  "scheduled_at": "2026-02-10T10:00:00Z"
}
```

**Field Rules:**
- `title` (required, string, max 255)
- `content` (required, string)
- `target_type` (required, string): `course`
- `target_value` (optional, string, max 255)
- `priority` (optional, string): `low` | `normal` | `high`
- `status` (optional, string): `draft` | `published` | `scheduled`
- `scheduled_at` (optional, date, after now)

---

## Statistics

### 1. Get Overall Statistics

Mendapatkan statistik keseluruhan.

**Endpoint:** `GET /content/statistics`

**Query Parameters:**
- `filter[type]` (optional, string): `all` | `announcements` | `news`
- `filter[course_id]` (optional, integer)
- `filter[category_id]` (optional, integer)
- `filter[date_from]` (optional, string, format Y-m-d)
- `filter[date_to]` (optional, string, format Y-m-d)

### 2. Get Announcement Statistics

Mendapatkan statistik pengumuman tertentu.

**Endpoint:** `GET /content/statistics/announcements/{id}`

### 3. Get News Statistics

Mendapatkan statistik berita tertentu.

**Endpoint:** `GET /content/statistics/news/{slug}`

### 4. Get Trending Statistics

Mendapatkan berita trending.

**Endpoint:** `GET /content/statistics/trending`

**Query Parameters:**
- `limit` (optional, integer): default 10

### 5. Get Most Viewed

Mendapatkan berita paling banyak dilihat.

**Endpoint:** `GET /content/statistics/most-viewed`

**Query Parameters:**
- `days` (optional, integer): default 30
- `limit` (optional, integer): default 10

---

## Search

### Search Content

Mencari konten (berita dan pengumuman).

**Endpoint:** `GET /content/search`

**Query Parameters:**
- `search` (required, string, min 2)
- `filter[type]` (optional, string): `all` | `news` | `announcements`
- `filter[category_id]` (optional, integer)
- `filter[date_from]` (optional, string, format Y-m-d)
- `filter[date_to]` (optional, string, format Y-m-d)
- `per_page` (optional, integer): default 15

---

## Content Approval Workflow

### 1. Submit Content for Review

**Endpoint:** `POST /content/{type}/{id}/submit`

**Path Params:**
- `type` (required, string): `news` | `announcement`
- `id` (required, integer)

**Body:** tidak ada

### 2. Approve Content

**Endpoint:** `POST /content/{type}/{id}/approve`

**Path Params:**
- `type` (required, string): `news` | `announcement`
- `id` (required, integer)

**JSON Body (raw):**
```json
{
  "note": "Disetujui untuk dipublikasikan"
}
```

**Field Rules:**
- `note` (optional, string, max 1000)

### 3. Reject Content

**Endpoint:** `POST /content/{type}/{id}/reject`

**Path Params:**
- `type` (required, string): `news` | `announcement`
- `id` (required, integer)

**JSON Body (raw):**
```json
{
  "reason": "Konten belum lengkap"
}
```

**Field Rules:**
- `reason` (required, string, max 1000)

### 4. Get Pending Review

**Endpoint:** `GET /content/pending-review`

**Query Parameters:**
- `type` (optional, string): `all` | `news` | `announcement` (default: all)

---

## Error Responses

### 401 Unauthorized
```json
{
  "status": "error",
  "message": "Anda belum login atau sesi Anda telah berakhir."
}
```

### 403 Forbidden
```json
{
  "status": "error",
  "message": "Akses ditolak. Anda tidak memiliki izin untuk melakukan aksi ini."
}
```

### 404 Not Found
```json
{
  "status": "error",
  "message": "Data tidak ditemukan."
}
```

### 422 Validation Error
```json
{
  "status": "error",
  "message": "Data yang Anda kirim tidak valid.",
  "errors": {
    "title": ["Judul wajib diisi."],
    "content": ["Konten wajib diisi."]
  }
}
```

---

## Notes

- Semua timestamp menggunakan format ISO 8601 (UTC)
- Pagination menggunakan format Laravel standard
- Soft delete digunakan untuk semua penghapusan data
- Full-text search tersedia untuk pencarian konten
