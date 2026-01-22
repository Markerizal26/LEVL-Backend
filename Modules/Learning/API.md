# Learning Module API Documentation

Dokumentasi lengkap untuk modul Learning, mencakup manajemen Assignments dan Submissions.

---

## Base URL

```
/api/v1
```

## Authentication

Semua endpoint membutuhkan autentikasi via Bearer token:

```
Authorization: Bearer {access_token}
```

---

## Assignments

Manajemen tugas (assignments) yang terkait dengan lesson tertentu dalam sebuah course.

### 1. List Assignments by Lesson

Mengambil daftar assignment untuk lesson tertentu. Mendukung filtering dan debugging.

**Endpoint:** `GET /courses/{course_slug}/units/{unit_slug}/lessons/{lesson_slug}/assignments`

**Authorization:** Authenticated User

**Path Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `course_slug` | string | Slug dari course |
| `unit_slug` | string | Slug dari unit |
| `lesson_slug` | string | Slug dari lesson |

**Query Parameters:**
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `page` | integer | No | 1 | Nomor halaman |
| `per_page` | integer | No | 15 | Jumlah item per halaman |
| `search` | string | No | - | Pencarian berdasarkan judul atau deskripsi |
| `sort` | string | No | - | Format sorting (e.g., `created_at`, `-created_at`) |
| `filter[status]` | string | No | - | Filter berdasarkan status (`draft`, `published`, `archived`) |
| `filter[submission_type]` | string | No | - | Filter tipe submission (`file`, `text`, `link`) |

**Response:** `200 OK`
```json
{
  "success": true,
  "message": "Data berhasil diambil",
  "data": {
    "assignments": [
      {
        "id": 1,
        "title": "Tugas Akhir Modul 1",
        "description": "Kerjakan soal berikut...",
        "submission_type": "text",
        "max_score": 100,
        "status": "published",
        "available_from": "2024-01-01 00:00:00",
        "deadline_at": "2024-01-10 23:59:59",
        "created_at": "2024-01-01 10:00:00",
        "updated_at": "2024-01-01 10:00:00"
      }
    ]
  }
}
```

### 2. Create Assignment

Membuat assignment baru untuk lesson tertentu.

**Endpoint:** `POST /courses/{course_slug}/units/{unit_slug}/lessons/{lesson_slug}/assignments`

**Authorization:** Admin, Instructor, Superadmin

**Path Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `course_slug` | string | Slug dari course |
| `unit_slug` | string | Slug dari unit |
| `lesson_slug` | string | Slug dari lesson |

**Request Body:**
| Field | Type | Required | Validation Rules | Description |
|-------|------|----------|------------------|-------------|
| `title` | string | Yes | `required|string|max:255` | Judul assignment |
| `description` | string | No | `nullable|string` | Deskripsi atau instruksi tugas |
| `submission_type` | string | Yes | `required|in:file,text,link` | Tipe pengumpulan ('file', 'text', 'link') |
| `max_score` | integer | No | `nullable|integer|min:1|max:1000` | Nilai maksimal (default: 100) |
| `available_from` | date | No | `nullable|date` | Tanggal mulai bisa dikerjakan |
| `deadline_at` | date | No | `nullable|date|after_or_equal:available_from` | Batas waktu pengumpulan |
| `status` | string | No | `nullable|in:draft,published,archived` | Status assignment |
| `allow_resubmit` | boolean | No | `nullable|boolean` | Izinkan pengumpulan ulang |
| `late_penalty_percent` | integer | No | `nullable|integer|min:0|max:100` | Persentase pengurangan nilai jika terlambat |

**Response:** `201 Created`
```json
{
  "success": true,
  "message": "Assignment berhasil dibuat",
  "data": {
    "assignment": {
      "id": 2,
      "title": "Tugas Baru",
      "status": "draft",
      "submission_type": "file",
      "created_by": 1
    }
  }
}
```

### 3. Show Assignment

Melihat detail assignment.

**Endpoint:** `GET /assignments/{assignment_id}`

**Authorization:** Authenticated User

**Response:** `200 OK`
```json
{
  "success": true,
  "message": "Success",
  "data": {
    "assignment": {
      "id": 1,
      "title": "Tugas Akhir Modul 1",
      "description": "...",
      "submission_type": "text",
      "lesson": { "id": 10, "title": "Lesson 1" },
      "creator": { "id": 1, "name": "Instructor 1" }
    }
  }
}
```

### 4. Update Assignment

Memperbarui data assignment.

**Endpoint:** `PUT /assignments/{assignment_id}`

**Authorization:** Admin, Instructor, Superadmin (Owner)

**Request Body:**
| Field | Type | Required | Validation Rules | Description |
|-------|------|----------|------------------|-------------|
| `title` | string | No | `sometimes|string|max:255` | |
| `description` | string | No | `nullable|string` | |
| `submission_type` | string | No | `sometimes|in:file,text,link` | |
| `max_score` | integer | No | `nullable|integer|min:1|max:1000` | |
| `available_from` | date | No | `nullable|date` | |
| `deadline_at` | date | No | `nullable|date|after_or_equal:available_from` | |
| `status` | string | No | `sometimes|in:draft,published,archived` | |
| `allow_resubmit` | boolean | No | `nullable|boolean` | |
| `late_penalty_percent` | integer | No | `nullable|integer|min:0|max:100` | |

**Response:** `200 OK`
```json
{
  "success": true,
  "message": "Assignment berhasil diperbarui",
  "data": {
    "assignment": { ... }
  }
}
```

### 5. Delete Assignment

Menghapus assignment secara permanen.

**Endpoint:** `DELETE /assignments/{assignment_id}`

**Authorization:** Admin, Instructor, Superadmin (Owner)

**Response:** `200 OK`
```json
{
  "success": true,
  "message": "Assignment berhasil dihapus",
  "data": []
}
```

### 6. Publish / Unpublish Assignment

Mengubah status assignment menjadi published atau draft.

**Publish:** `PUT /assignments/{assignment_id}/publish`
**Unpublish:** `PUT /assignments/{assignment_id}/unpublish`

**Authorization:** Admin, Instructor, Superadmin

**Response:** `200 OK`
```json
{
  "success": true,
  "message": "Assignment berhasil dipublish/unpublished",
  "data": {
    "assignment": { "status": "published", ... }
  }
}
```

---

## Submissions

Manajemen pengumpulan tugas oleh siswa.

### 7. List Submissions for Assignment

Melihat daftar submission untuk assignment tertentu.

**Endpoint:** `GET /assignments/{assignment_id}/submissions`

**Authorization:** Authenticated User (Siswa meihat submission sendiri, Instructor/Admin melihat semua)

**Query Parameters:**
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `page` | integer | No | 1 | |
| `per_page` | integer | No | 15 | |
| `search` | string | No | - | Cari berdasarkan nama siswa (untuk instructor) |
| `sort` | string | No | - | |
| `filter[status]` | string | No | - | `submitted`, `graded`, `late` |

**Response:** `200 OK`
```json
{
  "success": true,
  "message": "Success",
  "data": {
    "submissions": [
      {
        "id": 101,
        "user_id": 5,
        "status": "submitted",
        "submitted_at": "2024-01-05 14:00:00",
        "is_late": false,
        "user": { "name": "Student A" }
      }
    ]
  }
}
```

### 8. Create Submission (Submit Assignment)

Siswa mengumpulkan tugas.

**Endpoint:** `POST /assignments/{assignment_id}/submissions`

**Authorization:** Student (Must be enrolled)

**Request Body:**
| Field | Type | Required | Validation Rules | Description |
|-------|------|----------|------------------|-------------|
| `answer_text` | string | No | `nullable|string` | Teks jawaban (jika submission_type=text/link) |
| `files` | file[] | No | - | (TBD) Jika support upload file via multipart |

**Response:** `201 Created`
```json
{
  "success": true,
  "message": "Tugas berhasil dikumpulkan",
  "data": {
    "submission": {
      "id": 102,
      "status": "submitted",
      "attempt_number": 1
    }
  }
}
```

### 9. Show Submission

Detail submission.

**Endpoint:** `GET /submissions/{submission_id}`

**Authorization:** Student (Own submission), Instructor, Admin

**Response:** `200 OK`

### 10. Update Submission

Update submission (hanya jika status masih `draft` atau diizinkan resubmit).

**Endpoint:** `PUT /submissions/{submission_id}`

**Authorization:** Student (Own submission)

**Request Body:**
| Field | Type | Required | Validation Rules | Description |
|-------|------|----------|------------------|-------------|
| `answer_text` | string | No | `sometimes|string` | |

**Response:** `200 OK`

### 11. Grade Submission

Memberikan nilai dan feedback pada submission.

**Endpoint:** `POST /submissions/{submission_id}/grade`

**Authorization:** Admin, Instructor, Superadmin

**Request Body:**
| Field | Type | Required | Validation Rules | Description |
|-------|------|----------|------------------|-------------|
| `score` | integer | Yes | `required|integer|min:0` | Nilai yang diberikan |
| `feedback` | string | No | `nullable|string` | Feedback untuk siswa |

**Response:** `200 OK`
```json
{
  "success": true,
  "message": "Submission berhasil dinilai",
  "data": {
    "submission": {
      "id": 102,
      "status": "graded",
      "grade": {
        "score": 85,
        "feedback": "Great job!",
        "graded_by": 1
      }
    }
  }
}
```
