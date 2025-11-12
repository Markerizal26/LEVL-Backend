# Postman Collection Update Guide

Panduan lengkap untuk memperbarui Postman Collection dengan request body yang akurat dan lengkap.

---

## 📝 DETAILED REQUEST BODY SPECIFICATIONS

### AUTHENTICATION ENDPOINTS

#### 1. PUT /profile - Update Profile

**Current Status in Postman:** ❌ Missing or incomplete

**Expected Method:** PUT (form-data for file uploads, JSON for text-only)

**URL:** `{{API_URL}}/profile`

**Headers:**
```
Authorization: Bearer {{access_token}}
Content-Type: application/json
(or multipart/form-data if uploading file)
```

**Option A: JSON Only (No File Upload)**
```json
{
  "name": "Ahmad Wijaya",
  "username": "ahmadwijaya"
}
```

**Option B: Form-Data (With File Upload)**
```
Form-Data:
name: Ahmad Wijaya (text)
username: ahmadwijaya (text)
avatar: [FILE: user-avatar.jpg] (file)
```

**Validation Rules:**
- `name`: required, string, max 100
- `username`: required, string, max 50, unique (excluding current user)
- `avatar`: optional, must be image, formats: jpg, jpeg, png, webp, max 2MB

**Response Example (201 Created):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Ahmad Wijaya",
    "username": "ahmadwijaya",
    "email": "ahmad@example.com",
    "avatar_path": "avatars/user_1_abc123.jpg",
    "created_at": "2025-01-10T10:30:00Z",
    "updated_at": "2025-01-12T14:45:00Z"
  },
  "message": "Profil berhasil diperbarui."
}
```

---

#### 2. POST /auth/email/verify/send

**Current Status in Postman:** ✅ Present

**Method:** POST

**URL:** `{{API_URL}}/auth/email/verify/send`

**Headers:**
```
Authorization: Bearer {{access_token}}
Content-Type: application/json
```

**Body:** Empty or empty object
```json
{}
```

**Response Example:**
```json
{
  "success": true,
  "data": {
    "uuid": "550e8400-e29b-41d4-a716-446655440000"
  },
  "message": "Tautan verifikasi telah dikirim ke email Anda. Berlaku 3 menit dan hanya bisa digunakan sekali."
}
```

---

#### 3. POST /auth/email/verify

**Current Status in Postman:** ⚠️ Body needs verification

**Method:** POST

**URL:** `{{API_URL}}/auth/email/verify`

**Headers:**
```
Content-Type: application/json
```

**Body Option A: Using UUID and Code**
```json
{
  "uuid": "550e8400-e29b-41d4-a716-446655440000",
  "code": "123456"
}
```

**Body Option B: Using Token and Code**
```json
{
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiaWF0IjoxNTE2MjM5MDIyfQ.SflKxwRJSMeKKF2QT4fwpMeJf36POk6yJV_adQssw5c",
  "code": "123456"
}
```

**Validation Rules:**
- Either `uuid` or `token` is required (at least one)
- `code`: required, string, exactly 6 digits

**Response Example:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "email_verified_at": "2025-01-12T14:50:00Z",
    "status": "active"
  },
  "message": "Email berhasil diverifikasi."
}
```

---

#### 4. POST /auth/email/verify/by-token

**Current Status in Postman:** ⚠️ Body needs verification

**Method:** POST

**URL:** `{{API_URL}}/auth/email/verify/by-token`

**Headers:**
```
Content-Type: application/json
```

**Body:**
```json
{
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "email": "johndoe@example.com"
}
```

**Validation Rules:**
- `token`: required, string (email verification token)
- `email`: required, valid email address

**Note:** Token is typically sent via email link

---

#### 5. POST /profile/email/request

**Current Status in Postman:** ⚠️ Body needs verification

**Method:** POST

**URL:** `{{API_URL}}/profile/email/request`

**Headers:**
```
Authorization: Bearer {{access_token}}
Content-Type: application/json
```

**Body:**
```json
{
  "new_email": "newemail@example.com"
}
```

**Validation Rules:**
- `new_email`: required, valid email, unique, max 191, must be different from current email

**Response Example:**
```json
{
  "success": true,
  "data": {
    "uuid": "550e8400-e29b-41d4-a716-446655440000"
  },
  "message": "Verifikasi email baru telah dikirim. Silakan periksa email Anda."
}
```

---

#### 6. POST /profile/email/verify

**Current Status in Postman:** ⚠️ Body needs verification

**Method:** POST

**URL:** `{{API_URL}}/profile/email/verify`

**Headers:**
```
Authorization: Bearer {{access_token}}
Content-Type: application/json
```

**Body:**
```json
{
  "uuid": "550e8400-e29b-41d4-a716-446655440000",
  "code": "123456"
}
```

**Validation Rules:**
- `uuid`: required, string (from email request response)
- `code`: required, string, exactly 6 digits (from email)

---

### SCHEME ENDPOINTS - DETAILED

#### POST /courses - Full Example with All Fields

**Current Status in Postman:** ⚠️ Incomplete body

**Method:** POST

**URL:** `{{API_URL}}/courses`

**Headers:**
```
Authorization: Bearer {{access_token}}
Content-Type: application/json
(or multipart/form-data if uploading files)
```

**Complete JSON Body Example:**
```json
{
  "code": "DASAR-TI-2025",
  "slug": "dasar-teknologi-informasi-2025",
  "title": "Dasar Teknologi Informasi",
  "short_desc": "Kursus komprehensif tentang fondasi teknologi informasi modern",
  "level_tag": "dasar",
  "type": "okupasi",
  "visibility": "public",
  "progression_mode": "sequential",
  "category_id": 1,
  "instructor_id": 5,
  "status": "draft",
  "tags": [
    "teknologi",
    "dasar",
    "ti",
    "informatika"
  ],
  "outcomes": [
    "Memahami konsep dasar dan sejarah teknologi informasi",
    "Menguasai penggunaan komputer dan sistem operasi",
    "Mampu menggunakan aplikasi perkantoran dengan efisien",
    "Memahami networking dan internet dasar"
  ],
  "prereq": [
    "Literasi digital minimal",
    "Kemampuan berbahasa Inggris dasar"
  ],
  "course_admins": [5, 6]
}
```

**Form-Data Alternative (with file uploads):**
```
name                | value / file
--------------------|---------------------
code                | DASAR-TI-2025
slug                | dasar-teknologi-informasi-2025
title               | Dasar Teknologi Informasi
short_desc          | Kursus komprehensif tentang fondasi teknologi informasi modern
level_tag           | dasar
type                | okupasi
visibility          | public
progression_mode    | sequential
category_id         | 1
instructor_id       | 5
status              | draft
tags                | ["teknologi","dasar","ti","informatika"]
outcomes            | ["Memahami konsep dasar","Menguasai penggunaan komputer","Mampu menggunakan aplikasi perkantoran","Memahami networking"]
prereq              | ["Literasi digital minimal","Kemampuan berbahasa Inggris dasar"]
course_admins       | [5,6]
thumbnail           | [FILE: course_thumb.jpg]
banner              | [FILE: course_banner.jpg]
```

**Validation Rules:**
- `code`: required, string, max 50, must be unique across all courses
- `slug`: optional, string, max 100, will be auto-generated if not provided
- `title`: required, string, max 255
- `short_desc`: optional, string (any length)
- `level_tag`: required, must be one of: `dasar`, `menengah`, `mahir`
- `type`: required, must be one of: `okupasi`, `kluster`
- `visibility`: required, must be one of: `public`, `private`
- `progression_mode`: required, must be one of: `sequential`, `free`
- `category_id`: optional, must exist in categories table
- `instructor_id`: optional, must exist in users table
- `status`: optional, must be one of: `draft`, `published`, `archived` (default: draft)
- `tags`: optional, array of strings
- `outcomes`: optional, array of strings (learning outcomes)
- `prereq`: optional, array of strings (prerequisites)
- `course_admins`: optional, array of user IDs
- `thumbnail`: optional, image file (jpg, jpeg, png, webp), max 4MB
- `banner`: optional, image file (jpg, jpeg, png, webp), max 6MB

**Response Example:**
```json
{
  "success": true,
  "data": {
    "course": {
      "id": 1,
      "code": "DASAR-TI-2025",
      "slug": "dasar-teknologi-informasi-2025",
      "title": "Dasar Teknologi Informasi",
      "short_desc": "Kursus komprehensif tentang fondasi teknologi informasi modern",
      "level_tag": "dasar",
      "type": "okupasi",
      "visibility": "public",
      "progression_mode": "sequential",
      "category_id": 1,
      "instructor_id": 5,
      "status": "draft",
      "thumbnail_path": "courses/thumbnails/course_1_abc.jpg",
      "banner_path": "courses/banners/course_1_def.jpg",
      "tags_json": ["teknologi", "dasar", "ti", "informatika"],
      "outcomes_json": ["Memahami konsep dasar", "Menguasai penggunaan komputer"],
      "created_at": "2025-01-12T15:00:00Z",
      "updated_at": "2025-01-12T15:00:00Z"
    }
  },
  "message": "Course berhasil dibuat."
}
```

---

#### POST /courses/{slug}/units - Full Example

**Current Status in Postman:** ⚠️ Incomplete body

**Method:** POST

**URL:** `{{API_URL}}/courses/dasar-teknologi-informasi-2025/units`

**Headers:**
```
Authorization: Bearer {{access_token}}
Content-Type: application/json
```

**Complete JSON Body:**
```json
{
  "code": "UNIT-001",
  "slug": "pengenalan-dasar",
  "title": "Unit 1: Pengenalan Dasar Teknologi Informasi",
  "description": "Unit ini memperkenalkan konsep fundamental, sejarah, dan perkembangan teknologi informasi hingga era modern",
  "order": 1,
  "status": "draft"
}
```

**Validation Rules:**
- `code`: required, string, max 50, must be unique within course
- `slug`: optional, string, max 100, will be auto-generated, unique per course
- `title`: required, string, max 255
- `description`: optional, string (any length)
- `order`: optional, integer, minimum 1 (default: last position)
- `status`: optional, must be one of: `draft`, `published` (default: draft)

---

#### PUT /courses/{slug}/units/reorder - Array Format

**Current Status in Postman:** ⚠️ Body needs format correction

**Method:** PUT

**URL:** `{{API_URL}}/courses/dasar-teknologi-informasi-2025/units/reorder`

**Headers:**
```
Authorization: Bearer {{access_token}}
Content-Type: application/json
```

**Body - Array of Unit IDs in Desired Order:**
```json
{
  "units": [3, 1, 2, 5, 4]
}
```

**Explanation:**
- This example reorders 5 units
- Unit ID 3 becomes 1st
- Unit ID 1 becomes 2nd
- Unit ID 2 becomes 3rd
- And so on...

**Validation Rules:**
- `units`: required, must be array
- `units.*`: required, integer, must exist in units table
- All unit IDs must belong to the same course
- Array must contain all units in the course (or at least the ones you want to reorder)

**Another Example:**
```json
{
  "units": [10, 8, 9, 7]
}
```

---

#### POST /courses/{slug}/units/{slug}/lessons - Full Example

**Current Status in Postman:** ⚠️ Incomplete body

**Method:** POST

**URL:** `{{API_URL}}/courses/dasar-teknologi-informasi-2025/units/pengenalan-dasar/lessons`

**Headers:**
```
Authorization: Bearer {{access_token}}
Content-Type: application/json
```

**Body with Markdown Content:**
```json
{
  "slug": "lesson-1-apa-itu-ti",
  "title": "Apa Itu Teknologi Informasi?",
  "description": "Pelajaran pertama yang menjelaskan definisi dan ruang lingkup teknologi informasi",
  "markdown_content": "# Apa Itu Teknologi Informasi?\n\n## Definisi\nTeknologi Informasi (TI) adalah teknologi yang digunakan untuk menyimpan, mengolah, dan menyebarkan informasi menggunakan komputer dan jaringan.\n\n## Sejarah Singkat\n- Tahun 1950-an: Komputer mainframe\n- Tahun 1980-an: Personal Computer (PC)\n- Tahun 2000-an: Era Internet\n- Tahun 2010-an: Cloud Computing dan Mobile\n\n## Komponen TI\n1. **Hardware** - Perangkat fisik komputer\n2. **Software** - Program dan aplikasi\n3. **Network** - Jaringan komputer\n4. **Data** - Informasi yang dikelola",
  "order": 1,
  "duration_minutes": 45,
  "status": "draft"
}
```

**Validation Rules:**
- `slug`: optional, string, max 100, auto-generated if not provided
- `title`: required, string, max 255
- `description`: optional, string (any length)
- `markdown_content`: optional, string (markdown syntax supported)
- `order`: optional, integer, minimum 1
- `duration_minutes`: optional, integer, minimum 0
- `status`: optional, must be one of: `draft`, `published` (default: draft)

---

#### POST /courses/{slug}/units/{slug}/lessons/{slug}/blocks - Multiple Types

**Current Status in Postman:** ⚠️ Body structure needs verification

**Method:** POST

**URL:** `{{API_URL}}/courses/dasar-teknologi-informasi-2025/units/pengenalan-dasar/lessons/lesson-1-apa-itu-ti/blocks`

**Headers:**
```
Authorization: Bearer {{access_token}}
Content-Type: multipart/form-data (for file) or application/json
```

**Type 1: Text Content**
```json
{
  "type": "text",
  "content": "Ini adalah konten teks untuk blok pembelajaran. Dapat berisi penjelasan detail tentang topik yang dibahas dalam lesson ini.",
  "order": 1
}
```

**Type 2: Video**
```
Form-Data:
type: video (text)
content: Deskripsi video tentang pengenalan TI (text)
order: 2 (text)
media: [FILE: video_pengenalan_ti.mp4] (file)
```

**Type 3: Image**
```
Form-Data:
type: image (text)
content: Deskripsi gambar infrastruktur TI (text)
order: 3 (text)
media: [FILE: infrastruktur_ti.jpg] (file)
```

**Type 4: File (Downloadable)**
```
Form-Data:
type: file (text)
content: Download slide presentasi pengenalan TI (text)
order: 4 (text)
media: [FILE: slide_presentasi.pdf] (file)
```

**Validation Rules (from LessonBlockRequest):**
- `type`: required, must be one of: `text`, `video`, `image`, `file`
- `content`: optional, string (can be markdown or plain text)
- `order`: optional, integer, minimum 1
- `media`: 
  - Required if type is `video`, `image`, or `file`
  - Optional if type is `text`
  - Must be a valid file
  - Max file size: 50MB (configurable via ENV)
  - Must match MIME type:
    - `image`: image/* (jpg, jpeg, png, gif, webp, etc)
    - `video`: video/* (mp4, mpeg, webm, etc)
    - `file`: any file type

---

## 🔍 POSTMAN COLLECTION UPDATE CHECKLIST

### Auth Module
- [ ] Update `PUT /profile` - Add all fields and form-data option
- [ ] Update `POST /auth/email/verify` - Add both UUID and token options
- [ ] Update `POST /auth/email/verify/by-token` - Add complete body
- [ ] Update `POST /profile/email/request` - Add new_email field
- [ ] Update `POST /profile/email/verify` - Add uuid and code fields
- [ ] **ADD** `GET /auth/users/{user}` - New endpoint

### Common Module
- [ ] Verify `POST /categories` body is complete ✅
- [ ] Verify `PUT /categories/{id}` body is complete ✅

### Schemes Module
- [ ] Update `POST /courses` - Add all fields (level_tag, type, visibility, etc)
- [ ] Update `PUT /courses/{slug}` - Add all fields
- [ ] Update `POST /courses/{slug}/units` - Add all fields
- [ ] Update `PUT /courses/{slug}/units/{slug}` - Add all fields
- [ ] Update `PUT /courses/{slug}/units/reorder` - Add correct array format
- [ ] Update `POST /courses/{slug}/units/{slug}/lessons` - Add all fields
- [ ] Update `PUT /courses/{slug}/units/{slug}/lessons/{slug}` - Add all fields
- [ ] Update `POST /courses/{slug}/units/{slug}/lessons/{slug}/blocks` - Add all types
- [ ] Update `PUT /courses/{slug}/units/{slug}/lessons/{slug}/blocks/{slug}` - Add all types

### General Improvements
- [ ] Add response examples for all endpoints
- [ ] Add error response examples (400, 401, 403, 404, 422, 500)
- [ ] Add query parameters documentation for GET endpoints
- [ ] Add file upload examples in pre-request scripts if needed
- [ ] Test all updated endpoints

---

## 📌 NOTES

1. **Form-Data vs JSON**: Use form-data when uploading files, JSON for data-only requests
2. **File Size Limits**:
   - Avatar: 2MB
   - Course Thumbnail: 4MB
   - Course Banner: 6MB
   - Lesson Block Media: 50MB (configurable)
3. **Auto-Generated Fields**: 
   - `slug` is auto-generated if not provided based on title/name
   - IDs, timestamps are generated by server
4. **Required vs Optional**: Make sure to test with minimal required fields first
5. **Unique Fields**: Always test with unique values (code, slug, email, username, value)

---

**Version:** 1.0  
**Last Updated:** November 12, 2025
