# Enrollments API Documentation

Complete API reference for the Enrollments module.

---

## Base URL

```
/api/v1
```

## Authentication

All endpoints require authentication via Bearer token:

```
Authorization: Bearer {access_token}
```

---

## Enrollment Status Values

| Status | Description |
|--------|-------------|
| `pending` | Enrollment request awaiting approval |
| `active` | User is actively enrolled in the course |
| `completed` | User has completed all course requirements |
| `cancelled` | Enrollment was cancelled or declined |

**Status Transitions:**
- Auto-enroll: `null` → `active`
- Key-based: `null` → `active` (with valid key)
- Approval: `null` → `pending` → `active` (after approval) or `cancelled` (if declined)
- Student cancel: `pending` → `cancelled`
- Student withdraw: `active` → `cancelled`
- Manager remove: `pending|active` → `cancelled`

---

## Endpoints

### 1. List Enrollments by Course

Get all enrollments for a specific course (requires course management permission).

**Endpoint:** `GET /courses/{slug}/enrollments`

**Authorization:** Must have `update` permission on the course (Admin/Instructor/Superadmin)

**Path Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `slug` | string | Course slug |

**Query Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `page` | int | No | Page number (default: 1) |
| `per_page` | int | No | Items per page (default: 15) |
| `sort` | string | No | Sort field. Default: `priority` (Pending first). |
| `search` | string | No | Search by user name or email (via Meilisearch) |
| `filter[status]` | string | No | Exact match: `active`, `pending`, `completed`, `cancelled` |
| `filter[user_id]` | int | No | Filter by student ID |
| `filter[enrolled_from]` | date | No | Start date (`YYYY-MM-DD`) |
| `filter[enrolled_to]` | date | No | End date (`YYYY-MM-DD`) |
| `include` | string | No | Relationships: `user`, `course` |

**Allowed Sorts:**
- `priority` (default) - Pending enrollments first, then by creation date
- `enrolled_at`
- `completed_at`
- `created_at`

**Response:** `200 OK`
```json
{
  "success": true,
  "message": "Daftar enrollment course berhasil diambil.",
  "data": [
    {
      "id": 1,
      "user_id": 5,
      "course_id": 2,
      "status": "pending",
      "enrolled_at": "2026-01-20T07:00:00.000000Z",
      "completed_at": null,
      "created_at": "2026-01-20T06:30:00.000000Z",
      "updated_at": "2026-01-20T06:30:00.000000Z",
      "user": {
        "id": 5,
        "name": "John Doe",
        "email": "john@example.com"
      },
      "course": {
        "id": 2,
        "title": "Junior Web Programmer",
        "slug": "junior-web-programmer",
        "code": "CODE-01"
      }
    }
  ],
  "meta": {
    "pagination": {
      "current_page": 1,
      "per_page": 15,
      "total": 25,
      "last_page": 2,
      "from": 1,
      "to": 15,
      "has_next": true,
      "has_prev": false
    },
    "filtering": {
      "status": "pending"
    }
  },
  "errors": null
}
```

---

### 2. List Enrollments (Context-Aware)

List enrollments based on authenticated user's role.

**Endpoint:** `GET /enrollments`

**Role-Based Behavior:**

| Role | Returns |
|------|---------|
| **Superadmin** | All system enrollments with full filtering |
| **Admin/Instructor** | Enrollments from courses they manage |
| **Student** | Their own enrollments only |

**Query Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `page` | int | No | Page number (default: 1) |
| `per_page` | int | No | Items per page (default: 15) |
| `sort` | string | No | Sort field. Default: `priority`. |
| `search` | string | No | Full-text search via Meilisearch (searches across user/course data) |

**Filters for Superadmin:**
| Filter | Type | Description |
|--------|------|-------------|
| `filter[status]` | string | Exact match |
| `filter[user_id]` | int | Filter by student ID |
| `filter[course_slug]` | string | Filter by course slug |
| `filter[enrolled_from]` | date | Start date |
| `filter[enrolled_to]` | date | End date |

**Filters for Admin/Instructor:**
| Filter | Type | Description |
|--------|------|-------------|
| `filter[course_slug]` | string | Filter by specific managed course |
| `filter[status]` | string | Exact match |
| `filter[user_id]` | int | Filter by student ID |
| `filter[enrolled_from]` | date | Start date |
| `filter[enrolled_to]` | date | End date |

**Filters for Student:**
| Filter | Type | Description |
|--------|------|-------------|
| `filter[status]` | string | Exact match |
| `filter[course_slug]` | string | Filter by course slug |
| `filter[enrolled_from]` | date | Start date |
| `filter[enrolled_to]` | date | End date |

**Allowed Sorts:**
- `priority` (default)
- `enrolled_at`
- `completed_at`
- `created_at`

**Response:** `200 OK` (same format as endpoint #1)

**Error Responses:**
- `404` - No managed courses found (Admin/Instructor only)

---

### 3. Check Enrollment Status

Check if user is enrolled in a specific course.

**Endpoint:** `GET /courses/{slug}/enrollment-status`

**Path Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `slug` | string | Course slug |

**Query Parameters (Superadmin only):**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `user_id` | int | No | Check status for specific user |

**Response - Enrolled:** `200 OK`
```json
{
  "success": true,
  "message": "Status enrollment berhasil diambil.",
  "data": {
    "status": "active",
    "enrollment": {
      "id": 1,
      "user_id": 5,
      "course_id": 2,
      "status": "active",
      "enrolled_at": "2026-01-20T07:00:00.000000Z",
      "completed_at": null,
      "created_at": "2026-01-20T06:30:00.000000Z",
      "updated_at": "2026-01-20T06:30:00.000000Z",
      "user": {
        "id": 5,
        "name": "John Doe",
        "email": "john@example.com"
      },
      "course": {
        "id": 2,
        "title": "Junior Web Programmer",
        "slug": "junior-web-programmer",
        "code": "CODE-01"
      }
    }
  },
  "errors": null
}
```

**Response - Not Enrolled:** `200 OK`
```json
{
  "success": true,
  "message": "Belum terdaftar di course ini.",
  "data": {
    "status": "not_enrolled",
    "enrollment": null
  },
  "errors": null
}
```

---

### 4. Enroll in Course

Enroll the authenticated student in a course.

**Endpoint:** `POST /api/v1/courses/{slug}/enroll`

**Authorization:** Requires `Student` role (enforced by middleware)

**Path Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `slug` | string | Course slug |

**Request Body:**
| Field | Type | Required | Rules | Description |
|-------|------|----------|-------|-------------|
| `enrollment_key` | string | Conditional | nullable, string, max:100 | Required if course has `enrollment_type: key_based` |

**Course Enrollment Types:**

| Type | Behavior | Key Required? |
|------|----------|---------------|
| `auto_accept` | Immediate enrollment | No |
| `key_based` | Immediate enrollment with valid key | Yes |
| `approval` | Creates pending enrollment | No |

**Response - Auto Accept:** `201 Created`
```json
{
  "success": true,
  "message": "Enrol berhasil. Anda sekarang terdaftar pada course ini.",
  "data": {
    "id": 1,
    "user_id": 5,
    "course_id": 2,
    "status": "active",
    "enrolled_at": "2026-01-20T07:00:00.000000Z",
    "completed_at": null,
    "created_at": "2026-01-20T07:00:00.000000Z",
    "updated_at": "2026-01-20T07:00:00.000000Z"
  },
  "errors": null
}
```

**Response - Approval Required:** `201 Created`
```json
{
  "success": true,
  "message": "Permintaan enrollment berhasil dikirim. Menunggu persetujuan.",
  "data": {
    "id": 1,
    "user_id": 5,
    "course_id": 2,
    "status": "pending",
    "enrolled_at": "2026-01-20T07:00:00.000000Z",
    "completed_at": null,
    "created_at": "2026-01-20T07:00:00.000000Z",
    "updated_at": "2026-01-20T07:00:00.000000Z"
  },
  "errors": null
}
```

**Error Responses:**
- `422` - Validation error (missing key, invalid key)
- `400` - Already enrolled

---

### 5. Cancel Enrollment Request

Cancel a pending enrollment request.

**Endpoint:** `POST /courses/{slug}/cancel`

**Authorization:** Student can cancel own pending enrollments. Superadmin can cancel for any user.

**Path Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `slug` | string | Course slug |

**Request Body (Superadmin only):**
| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `user_id` | int | No | Cancel enrollment for specific user |

**Business Rules:**
- Only `pending` status enrollments can be cancelled
- Students can only cancel their own enrollments
- Superadmin can cancel any user's enrollment

**Response:** `200 OK`
```json
{
  "success": true,
  "message": "Permintaan enrollment berhasil dibatalkan.",
  "data": {
    "id": 1,
    "user_id": 5,
    "course_id": 2,
    "status": "cancelled",
    "enrolled_at": "2026-01-20T07:00:00.000000Z",
    "completed_at": null,
    "created_at": "2026-01-20T06:30:00.000000Z",
    "updated_at": "2026-01-20T07:10:00.000000Z"
  },
  "errors": null
}
```

**Error Responses:**
- `404` - Enrollment not found
- `403` - Unauthorized
- `400` - Can only cancel pending enrollments

---

### 6. Withdraw from Course

Withdraw from an active course enrollment.

**Endpoint:** `POST /courses/{slug}/withdraw`

**Authorization:** Student can withdraw from own active enrollments. Superadmin can withdraw any user.

**Path Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `slug` | string | Course slug |

**Request Body (Superadmin only):**
| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `user_id` | int | No | Withdraw enrollment for specific user |

**Business Rules:**
- Only `active` status enrollments can be withdrawn
- Students can only withdraw their own enrollments
- Superadmin can withdraw any user's enrollment

**Response:** `200 OK`
```json
{
  "success": true,
  "message": "Anda berhasil mengundurkan diri dari course.",
  "data": {
    "id": 1,
    "user_id": 5,
    "course_id": 2,
    "status": "cancelled",
    "enrolled_at": "2026-01-20T07:00:00.000000Z",
    "completed_at": null,
    "created_at": "2026-01-20T06:30:00.000000Z",
    "updated_at": "2026-01-20T08:15:00.000000Z"
  },
  "errors": null
}
```

**Error Responses:**
- `404` - Enrollment not found
- `403` - Unauthorized
- `400` - Can only withdraw from active enrollments

---

### 7. Approve Enrollment (Manager)

Approve a pending enrollment request.

**Endpoint:** `POST /enrollments/{enrollment_id}/approve`

**Authorization:** Course Admin or Instructor only

**Path Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `enrollment_id` | int | Enrollment ID |

**Business Rules:**
- Only `pending` status enrollments can be approved
- User must be course admin or instructor
- Sends approval email to student

**Response:** `200 OK`
```json
{
  "success": true,
  "message": "Permintaan enrollment disetujui.",
  "data": {
    "id": 1,
    "user_id": 5,
    "course_id": 2,
    "status": "active",
    "enrolled_at": "2026-01-20T08:00:00.000000Z",
    "completed_at": null,
    "created_at": "2026-01-20T06:30:00.000000Z",
    "updated_at": "2026-01-20T08:00:00.000000Z"
  },
  "errors": null
}
```

**Error Responses:**
- `404` - Enrollment not found
- `403` - Not authorized to manage this course
- `400` - Can only approve pending enrollments

---

### 8. Decline Enrollment (Manager)

Decline a pending enrollment request.

**Endpoint:** `POST /enrollments/{enrollment_id}/decline`

**Authorization:** Course Admin or Instructor only

**Path Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `enrollment_id` | int | Enrollment ID |

**Business Rules:**
- Only `pending` status enrollments can be declined
- User must be course admin or instructor
- Sends decline email to student

**Response:** `200 OK`
```json
{
  "success": true,
  "message": "Permintaan enrollment ditolak.",
  "data": {
    "id": 1,
    "user_id": 5,
    "course_id": 2,
    "status": "cancelled",
    "enrolled_at": "2026-01-20T07:00:00.000000Z",
    "completed_at": null,
    "created_at": "2026-01-20T06:30:00.000000Z",
    "updated_at": "2026-01-20T08:05:00.000000Z"
  },
  "errors": null
}
```

**Error Responses:**
- `404` - Enrollment not found
- `403` - Not authorized to manage this course
- `400` - Can only decline pending enrollments

---

### 9. Remove User from Course (Manager)

Force remove a user from a course.

**Endpoint:** `POST /enrollments/{enrollment_id}/remove`

**Authorization:** Course Admin or Instructor only

**Path Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `enrollment_id` | int | Enrollment ID |

**Business Rules:**
- Can remove `active` or `pending` enrollments
- User must be course admin or instructor
- Changes status to `cancelled`

**Response:** `200 OK`
```json
{
  "success": true,
  "message": "Peserta berhasil dikeluarkan dari course.",
  "data": {
    "id": 1,
    "user_id": 5,
    "course_id": 2,
    "status": "cancelled",
    "enrolled_at": "2026-01-20T07:00:00.000000Z",
    "completed_at": null,
    "created_at": "2026-01-20T06:30:00.000000Z",
    "updated_at": "2026-01-20T09:00:00.000000Z"
  },
  "errors": null
}
```

**Error Responses:**
- `404` - Enrollment not found
- `403` - Not authorized to manage this course
- `400` - Can only remove active or pending enrollments

---

### 10. Bulk Approve Enrollments (Manager)

Approve multiple pending enrollment requests in one request.

**Endpoint:** `POST /enrollments/approve/bulk`

**Authorization:** Course Admin or Instructor for each enrollment (only enrollments the user can approve are processed)

**Request Body:**
| Field | Type | Required | Rules | Description |
|-------|------|----------|-------|-------------|
| `enrollment_ids` | array | Yes | array, min:1, max:100; each item integer, exists:enrollments,id | List of enrollment IDs to approve |

**Request body (raw JSON):**
```json
{
  "enrollment_ids": [1, 2, 3]
}
```

**Business Rules:**
- Only `pending` enrollments can be approved; others are returned in `failed` with reason
- Only enrollments the user is authorized to approve (per course) are processed; unauthorized IDs are skipped
- Sends approval email to each student whose enrollment is approved

**Response:** `200 OK`
```json
{
  "success": true,
  "message": "Bulk action completed.",
  "data": {
    "processed": [
      {
        "id": 1,
        "user_id": 5,
        "course_id": 2,
        "status": "active",
        "enrolled_at": "2026-01-20T08:00:00.000000Z",
        "completed_at": null,
        "created_at": "2026-01-20T06:30:00.000000Z",
        "updated_at": "2026-01-20T08:00:00.000000Z"
      }
    ],
    "failed": [
      { "id": 3, "reason": "Only pending enrollment requests can be approved." }
    ]
  },
  "errors": null
}
```

**Error Responses:**
- `422` - Validation error (e.g. empty list, invalid IDs)

---

### 11. Bulk Decline Enrollments (Manager)

Decline (reject) multiple pending enrollment requests in one request.

**Endpoint:** `POST /enrollments/decline/bulk`

**Authorization:** Course Admin or Instructor for each enrollment (only enrollments the user can decline are processed)

**Request Body:**
| Field | Type | Required | Rules | Description |
|-------|------|----------|-------|-------------|
| `enrollment_ids` | array | Yes | array, min:1, max:100; each item integer, exists:enrollments,id | List of enrollment IDs to decline |

**Request body (raw JSON):**
```json
{
  "enrollment_ids": [4, 5, 6]
}
```

**Business Rules:**
- Only `pending` enrollments can be declined; others are returned in `failed` with reason
- Only enrollments the user is authorized to decline are processed
- Sends decline email to each student whose enrollment is declined

**Response:** `200 OK` (same structure as Bulk Approve: `data.processed`, `data.failed`)

**Error Responses:**
- `422` - Validation error

---

### 12. Bulk Remove Enrollments (Manager)

Remove multiple users from courses in one request.

**Endpoint:** `POST /enrollments/remove/bulk`

**Authorization:** Course Admin or Instructor for each enrollment (only enrollments the user can remove are processed)

**Request Body:**
| Field | Type | Required | Rules | Description |
|-------|------|----------|-------|-------------|
| `enrollment_ids` | array | Yes | array, min:1, max:100; each item integer, exists:enrollments,id | List of enrollment IDs to remove |

**Request body (raw JSON):**
```json
{
  "enrollment_ids": [7, 8, 9]
}
```

**Business Rules:**
- Can remove `active` or `pending` enrollments; others are returned in `failed` with reason
- Only enrollments the user is authorized to remove are processed
- Status is set to `cancelled` for each processed enrollment

**Response:** `200 OK` (same structure as Bulk Approve: `data.processed`, `data.failed`)

**Error Responses:**
- `422` - Validation error

---

## Rate Limiting

| Endpoint Group | Limit |
|----------------|-------|
| State-changing endpoints (enroll, cancel, withdraw, approve, decline, remove, bulk approve/decline/remove) | 5 requests/minute |
| Read-only endpoints (list, status) | 60 requests/minute |

**Rate Limit Headers:**
```
X-RateLimit-Limit: 5
X-RateLimit-Remaining: 4
X-RateLimit-Reset: 1737364800
```

---

## Error Responses

### 400 Bad Request
```json
{
  "success": false,
  "message": "Hanya enrollment dengan status pending yang dapat dibatalkan.",
  "errors": {
    "enrollment": ["Hanya enrollment dengan status pending yang dapat dibatalkan."]
  }
}
```

### 403 Forbidden
```json
{
  "success": false,
  "message": "Anda tidak memiliki akses untuk melakukan aksi ini.",
  "errors": null
}
```

### 404 Not Found
```json
{
  "success": false,
  "message": "Permintaan enrollment tidak ditemukan untuk course ini.",
  "errors": null
}
```

### 422 Validation Error
```json
{
  "success": false,
  "message": "Data yang Anda kirim tidak valid.",
  "errors": {
    "enrollment_key": ["Kode enrollment wajib diisi."]
  }
}
```

### 429 Too Many Requests
```json
{
  "success": false,
  "message": "Terlalu banyak percobaan. Silakan coba lagi dalam beberapa saat.",
  "errors": null
}
```

---

## Usage Examples

### Common Filtering Patterns

**Filter by Course Slug:**
```
GET /enrollments?filter[course_slug]=junior-web-programmer
```

**Search with Multiple Filters:**
```
GET /enrollments?search=john&filter[status]=active&filter[course_slug]=data-science
```

**Date Range Query:**
```
GET /enrollments?filter[enrolled_from]=2026-01-01&filter[enrolled_to]=2026-01-31
```

**Sort by Enrollment Date (Newest First):**
```
GET /enrollments?sort=-enrolled_at
```

**Pagination with Filters:**
```
GET /enrollments?page=2&per_page=20&filter[status]=pending
```

---

## cURL Examples

### List All Enrollments (Superadmin)
```bash
curl -X GET "https://api.example.com/api/v1/enrollments?filter[status]=pending&sort=priority&search=john" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

### Search Enrollments by Course
```bash
curl -X GET "https://api.example.com/api/v1/courses/junior-web-programmer/enrollments?search=john&filter[status]=active" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

### Filter by Course Slug (Student)
```bash
curl -X GET "https://api.example.com/api/v1/enrollments?filter[course_slug]=junior-web-programmer&filter[status]=active" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

### Filter with Date Range (Superadmin)
```bash
curl -X GET "https://api.example.com/api/v1/enrollments?filter[enrolled_from]=2026-01-01&filter[enrolled_to]=2026-01-31&filter[status]=active" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

### Enroll in Course (Auto-Accept)
```bash
curl -X POST "https://api.example.com/api/v1/courses/junior-web-programmer/enroll" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json"
```

### Enroll in Course (With Key)
```bash
curl -X POST "https://api.example.com/api/v1/courses/junior-web-programmer/enroll" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"enrollment_key": "ABC123XYZ"}'
```

### Check Enrollment Status
```bash
curl -X GET "https://api.example.com/api/v1/courses/junior-web-programmer/enrollment-status" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

### Cancel Enrollment
```bash
curl -X POST "https://api.example.com/api/v1/courses/junior-web-programmer/cancel" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

### Approve Enrollment (Manager)
```bash
curl -X POST "https://api.example.com/api/v1/enrollments/123/approve" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

### Bulk Approve Enrollments
```bash
curl -X POST "https://api.example.com/api/v1/enrollments/approve/bulk" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"enrollment_ids": [1, 2, 3]}'
```

### Bulk Decline Enrollments
```bash
curl -X POST "https://api.example.com/api/v1/enrollments/decline/bulk" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"enrollment_ids": [4, 5, 6]}'
```

### Bulk Remove Enrollments
```bash
curl -X POST "https://api.example.com/api/v1/enrollments/remove/bulk" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"enrollment_ids": [7, 8, 9]}'
```

---

## Events Dispatched

| Event | Trigger | Listeners |
|-------|---------|-----------|
| `EnrollmentCreated` | Any successful enrollment | Send email notifications to student and course managers |

---

## Notes

- All dates are in ISO 8601 format with timezone
- All endpoints require authentication
- Timestamps use UTC timezone
- **Search uses Meilisearch** for fast full-text search across enrollment data
- Filter parameters use Spatie Query Builder syntax (exact match, date ranges)
- Pagination metadata follows Laravel standard format
