# Referensi Request Body untuk Postman Collection

Dokumen ini berisi contoh lengkap raw body (JSON) untuk setiap endpoint yang perlu diperbaharui di Postman Collection.

---

## 📋 TABLE OF CONTENTS

1. [AUTH MODULE](#auth-module)
2. [COMMON MODULE](#common-module)
3. [SCHEMES MODULE - COURSE](#schemes-module---course)
4. [SCHEMES MODULE - UNIT](#schemes-module---unit)
5. [SCHEMES MODULE - LESSON](#schemes-module---lesson)
6. [SCHEMES MODULE - LESSON BLOCK](#schemes-module---lesson-block)

---

## AUTH MODULE

### 1. POST /auth/register
**Status:** ✅ Ada, body akurat

```json
{
  "name": "John Doe",
  "username": "johndoe",
  "email": "johndoe@example.com",
  "password": "SecurePass123!",
  "password_confirmation": "SecurePass123!"
}
```

**Validasi:**
- `name`: required, string, max 255
- `username`: required, string, max 50, unique
- `email`: required, email, unique
- `password`: required, min 8, strong (uppercase, lowercase, number, symbol)

---

### 2. POST /auth/login
**Status:** ✅ Ada, body akurat

```json
{
  "login": "johndoe@example.com",
  "password": "SecurePass123!"
}
```

**Catatan:** `login` bisa email atau username

**Validasi:**
- `login`: required, string, max 255
- `password`: required, string, min 8

---

### 3. PUT /profile ⚠️ **PERLU DIPERBAHARUI**
**Status:** ❌ Body tidak lengkap - Gunakan form-data, bukan raw JSON

```
Form-Data:
- name: "John Doe Updated"
- username: "johndoe_updated"
- avatar: <file binary>
```

**Atau jika tanpa file upload, gunakan JSON:**
```json
{
  "name": "John Doe Updated",
  "username": "johndoe_updated"
}
```

**Validasi:**
- `name`: required, string, max 100
- `username`: required, string, max 50, unique
- `avatar`: optional, image file (jpg, jpeg, png, webp), max 2MB

**Headers:**
- Authorization: `Bearer {access_token}`
- Content-Type: `application/x-www-form-urlencoded` (jika form-data) atau `application/json`

---

### 4. POST /auth/email/verify/send ✅ 
**Status:** Ada, tidak perlu body

```json
{}
```

**Validasi:** Tidak ada body yang diperlukan

**Headers:**
- Authorization: `Bearer {access_token}`

---

### 5. POST /auth/email/verify ⚠️ **PERLU DIPERBAHARUI**
**Status:** ❌ Body perlu verifikasi

```json
{
  "uuid": "550e8400-e29b-41d4-a716-446655440000",
  "code": "123456"
}
```

**Atau menggunakan token:**
```json
{
  "token": "eyJhbGciOiJIUzI1NiIs...",
  "code": "123456"
}
```

**Validasi:**
- `uuid` atau `token`: salah satu harus ada
- `code`: required, string (6 digit)

---

### 6. POST /auth/email/verify/by-token ⚠️ **PERLU DIPERBAHARUI**
**Status:** ❌ Body perlu verifikasi

```json
{
  "token": "eyJhbGciOiJIUzI1NiIs...",
  "email": "johndoe@example.com"
}
```

**Validasi:**
- `token`: required, string
- `email`: required, email

---

### 7. POST /profile/email/request ⚠️ **PERLU DIPERBAHARUI**
**Status:** ❌ Body perlu verifikasi

```json
{
  "new_email": "newemail@example.com"
}
```

**Validasi:**
- `new_email`: required, email, unique, max 191

**Headers:**
- Authorization: `Bearer {access_token}`

---

### 8. POST /profile/email/verify ⚠️ **PERLU DIPERBAHARUI**
**Status:** ❌ Body perlu verifikasi

```json
{
  "uuid": "550e8400-e29b-41d4-a716-446655440000",
  "code": "123456"
}
```

**Validasi:**
- `uuid`: required, string
- `code`: required, string (6 digit)

**Headers:**
- Authorization: `Bearer {access_token}`

---

### 9. POST /auth/refresh
**Status:** ✅ Ada, body akurat

```json
{
  "refresh_token": "eyJhbGciOiJIUzI1NiIs..."
}
```

**Validasi:**
- `refresh_token`: required, string

**Headers:**
- Authorization: `Bearer {access_token}`

---

### 10. POST /auth/logout
**Status:** ✅ Ada, body akurat

```json
{
  "refresh_token": "eyJhbGciOiJIUzI1NiIs..."
}
```

**Validasi:**
- `refresh_token`: nullable, string

**Headers:**
- Authorization: `Bearer {access_token}`

---

### 11. POST /auth/instructor
**Status:** ✅ Ada, body akurat

```json
{
  "name": "Dr. Ahmad Wijaya",
  "username": "ahmadwijaya",
  "email": "ahmadwijaya@example.com"
}
```

**Validasi:**
- `name`: required, string, max 255
- `username`: required, string, max 255, unique
- `email`: required, email, unique

**Headers:**
- Authorization: `Bearer {access_token}` (admin atau super-admin)

**Note:** Password akan di-generate otomatis dan dikirim via email

---

### 12. POST /auth/admin
**Status:** ✅ Ada, body akurat

```json
{
  "name": "Admin Setia",
  "username": "adminsetia",
  "email": "adminsetia@example.com"
}
```

**Validasi:** Sama dengan instructor

**Headers:**
- Authorization: `Bearer {access_token}` (super-admin only)

---

### 13. POST /auth/super-admin
**Status:** ✅ Ada, body akurat

```json
{
  "name": "Super Admin Master",
  "username": "superadmin_master",
  "email": "superadmin@example.com"
}
```

**Validasi:** Sama dengan instructor

**Headers:**
- Authorization: `Bearer {access_token}` (super-admin only)

---

### 14. POST /auth/credentials/resend
**Status:** ✅ Ada, body akurat

```json
{
  "user_id": 42
}
```

**Validasi:**
- `user_id`: required, integer, exists in users table

**Headers:**
- Authorization: `Bearer {access_token}` (super-admin only)

---

### 15. PUT /auth/users/{user}/status
**Status:** ✅ Ada, body akurat

```json
{
  "status": "inactive"
}
```

**Status Options:** `active`, `inactive`

**Validasi:**
- `status`: required, in [active, inactive]

**Headers:**
- Authorization: `Bearer {access_token}` (super-admin only)

---

### 16. POST /auth/password/forgot
**Status:** ✅ Ada, body akurat

```json
{
  "login": "johndoe@example.com"
}
```

**Catatan:** `login` bisa email atau username

**Validasi:**
- `login`: required, string

---

### 17. POST /auth/password/forgot/confirm
**Status:** ✅ Ada, body akurat

```json
{
  "token": "123456",
  "password": "NewSecurePass123!",
  "password_confirmation": "NewSecurePass123!"
}
```

**Validasi:**
- `token`: required, 6-digit code
- `password`: required, strong (uppercase, lowercase, number, symbol), min 8

---

### 18. POST /auth/password/reset
**Status:** ✅ Ada, body akurat

```json
{
  "current_password": "OldSecurePass123!",
  "password": "NewSecurePass123!",
  "password_confirmation": "NewSecurePass123!"
}
```

**Validasi:**
- `current_password`: required
- `password`: required, strong (uppercase, lowercase, number, symbol), min 8

**Headers:**
- Authorization: `Bearer {access_token}`

---

### 19. ❌ **MISSING: GET /auth/users/{user}**

**Method:** GET  
**URL:** `/v1/auth/users/{user}`  
**Headers:**
- Authorization: `Bearer {access_token}` (super-admin only)

**Response:** User detail object

**Action:** Tambahkan endpoint ini ke Postman collection

---

## COMMON MODULE

### 1. GET /categories
**Status:** ✅ Ada

**Query Parameters:**
```
?search=<string>&filter[status]=active&sort=-created_at&per_page=15&page=1
```

---

### 2. POST /categories
**Status:** ✅ Ada, body akurat

```json
{
  "name": "Teknologi Informasi",
  "value": "teknologi-informasi",
  "description": "Kategori untuk kurikulum di bidang teknologi informasi",
  "status": "active"
}
```

**Validasi:**
- `name`: required, string, max 100
- `value`: required, string, max 100, unique
- `description`: optional, string, max 255
- `status`: required, in [active, inactive]

**Headers:**
- Authorization: `Bearer {access_token}` (super-admin only)

---

### 3. PUT /categories/{category}
**Status:** ✅ Ada, body akurat

```json
{
  "name": "Teknologi Informasi (Updated)",
  "value": "teknologi-informasi",
  "description": "Kategori kurikulum bidang teknologi informasi yang diperbarui",
  "status": "active"
}
```

**Validasi:** Sama dengan POST, tapi `value` tetap unique kecuali untuk record yang sama

---

## SCHEMES MODULE - COURSE

### 1. POST /courses ⚠️ **PERLU DIPERBAHARUI**
**Status:** ❌ Body tidak lengkap

```json
{
  "code": "DASAR-TI-001",
  "slug": "dasar-teknologi-informasi",
  "title": "Dasar Teknologi Informasi",
  "short_desc": "Pengenalan fundamental teknologi informasi dan komputasi",
  "level_tag": "dasar",
  "type": "okupasi",
  "visibility": "public",
  "progression_mode": "sequential",
  "category_id": 1,
  "instructor_id": 5,
  "status": "draft",
  "tags": ["teknologi", "dasar", "ti"],
  "outcomes": [
    "Memahami konsep dasar teknologi informasi",
    "Menguasai penggunaan komputer dasar",
    "Mampu menggunakan aplikasi perkantoran"
  ],
  "prereq": [],
  "course_admins": [5, 6]
}
```

**Jika mengunggah file:**
```
Form-Data:
- code: "DASAR-TI-001"
- slug: "dasar-teknologi-informasi"
- title: "Dasar Teknologi Informasi"
- short_desc: "Pengenalan fundamental teknologi informasi"
- level_tag: "dasar"
- type: "okupasi"
- visibility: "public"
- progression_mode: "sequential"
- category_id: "1"
- instructor_id: "5"
- status: "draft"
- tags: '["teknologi", "dasar", "ti"]'
- outcomes: '["Memahami konsep dasar", "Menguasai penggunaan"]'
- prereq: '[]'
- course_admins: '[5, 6]'
- thumbnail: <file binary>
- banner: <file binary>
```

**Validasi:**
- `code`: required, string, max 50, unique
- `slug`: optional, string, max 100, unique
- `title`: required, string, max 255
- `short_desc`: optional, string
- `level_tag`: required, in [dasar, menengah, mahir]
- `type`: required, in [okupasi, kluster]
- `visibility`: required, in [public, private]
- `progression_mode`: required, in [sequential, free]
- `category_id`: optional, integer, exists in categories
- `instructor_id`: optional, integer, exists in users
- `status`: optional, in [draft, published, archived]
- `tags`: optional, array of strings
- `outcomes`: optional, array of strings
- `prereq`: optional, array of strings
- `course_admins`: optional, array of user IDs
- `thumbnail`: optional, image (jpg, jpeg, png, webp), max 4MB
- `banner`: optional, image (jpg, jpeg, png, webp), max 6MB

**Headers:**
- Authorization: `Bearer {access_token}` (admin atau super-admin)
- Content-Type: `application/json` (jika JSON) atau `application/x-www-form-urlencoded` (jika form-data)

---

### 2. PUT /courses/{slug} ⚠️ **PERLU DIPERBAHARUI**
**Status:** ❌ Body tidak lengkap (sama seperti POST)

```json
{
  "code": "DASAR-TI-001",
  "slug": "dasar-teknologi-informasi",
  "title": "Dasar Teknologi Informasi (Updated)",
  "short_desc": "Pengenalan fundamental teknologi informasi - versi terbaru",
  "level_tag": "dasar",
  "type": "okupasi",
  "visibility": "public",
  "progression_mode": "sequential",
  "category_id": 1,
  "instructor_id": 5,
  "status": "published",
  "tags": ["teknologi", "dasar", "ti", "baru"],
  "outcomes": [
    "Memahami konsep dasar teknologi informasi",
    "Menguasai penggunaan komputer dasar",
    "Mampu menggunakan aplikasi perkantoran",
    "Familiar dengan jaringan komputer"
  ],
  "prereq": [],
  "course_admins": [5, 6, 7]
}
```

---

## SCHEMES MODULE - UNIT

### 1. POST /courses/{slug}/units ⚠️ **PERLU DIPERBAHARUI**
**Status:** ❌ Body tidak lengkap

```json
{
  "code": "UNIT-001",
  "slug": "pengenalan-dasar",
  "title": "Pengenalan Dasar Teknologi Informasi",
  "description": "Unit pertama yang menjelaskan konsep dasar dan sejarah teknologi informasi",
  "order": 1,
  "status": "draft"
}
```

**Validasi:**
- `code`: required, string, max 50, unique
- `slug`: optional, string, max 100, unique per course
- `title`: required, string, max 255
- `description`: optional, string
- `order`: optional, integer, min 1
- `status`: optional, in [draft, published]

**Headers:**
- Authorization: `Bearer {access_token}` (admin atau super-admin)

---

### 2. PUT /courses/{slug}/units/{slug} ⚠️ **PERLU DIPERBAHARUI**
**Status:** ❌ Body tidak lengkap (sama seperti POST units)

```json
{
  "code": "UNIT-001",
  "slug": "pengenalan-dasar",
  "title": "Pengenalan Dasar Teknologi Informasi (Updated)",
  "description": "Unit pertama yang menjelaskan konsep dasar, sejarah, dan perkembangan teknologi informasi",
  "order": 1,
  "status": "published"
}
```

---

### 3. PUT /courses/{slug}/units/reorder ⚠️ **PERLU DIPERBAHARUI**
**Status:** ❌ Body tidak lengkap

```json
{
  "units": [3, 1, 2, 5, 4]
}
```

**Penjelasan:** Array berisi unit IDs dalam urutan yang diinginkan (dari atas ke bawah).

**Validasi:**
- `units`: required, array
- `units.*`: required, integer, exists in units table

**Headers:**
- Authorization: `Bearer {access_token}` (admin atau super-admin)

---

## SCHEMES MODULE - LESSON

### 1. POST /courses/{slug}/units/{slug}/lessons ⚠️ **PERLU DIPERBAHARUI**
**Status:** ❌ Body tidak lengkap

```json
{
  "slug": "lesson-1-pengenalan",
  "title": "Pengenalan Teknologi Informasi",
  "description": "Pelajaran pertama yang menjelaskan apa itu teknologi informasi",
  "markdown_content": "# Pengenalan Teknologi Informasi\n\nTeknologi Informasi (TI) adalah...",
  "order": 1,
  "duration_minutes": 45,
  "status": "draft"
}
```

**Validasi:**
- `slug`: optional, string, max 100, unique per unit
- `title`: required, string, max 255
- `description`: optional, string
- `markdown_content`: optional, string (markdown format)
- `order`: optional, integer, min 1
- `duration_minutes`: optional, integer, min 0
- `status`: optional, in [draft, published]

**Headers:**
- Authorization: `Bearer {access_token}` (admin atau super-admin)

---

### 2. PUT /courses/{slug}/units/{slug}/lessons/{slug} ⚠️ **PERLU DIPERBAHARUI**
**Status:** ❌ Body tidak lengkap (sama seperti POST lessons)

```json
{
  "slug": "lesson-1-pengenalan",
  "title": "Pengenalan Teknologi Informasi (Versi 2)",
  "description": "Pelajaran pertama yang menjelaskan apa itu teknologi informasi - versi terbaru",
  "markdown_content": "# Pengenalan Teknologi Informasi\n\nTeknologi Informasi (TI) adalah penggunaan komputer dan perangkat lunak...",
  "order": 1,
  "duration_minutes": 60,
  "status": "published"
}
```

---

## SCHEMES MODULE - LESSON BLOCK

### 1. POST /courses/{slug}/units/{slug}/lessons/{slug}/blocks ⚠️ **PERLU DIPERBAHARUI**
**Status:** ❌ Body tidak lengkap

```json
{
  "slug": "block-1-video",
  "type": "video",
  "title": "Video: Pengenalan TI",
  "order": 1,
  "content": {
    "video_url": "https://youtube.com/embed/..."
  },
  "status": "draft"
}
```

**Atau untuk tipe text:**
```json
{
  "slug": "block-2-text",
  "type": "text",
  "title": "Penjelasan Teks",
  "order": 2,
  "content": {
    "text": "Ini adalah konten teks untuk blok pembelajaran..."
  },
  "status": "draft"
}
```

**Atau untuk tipe quiz:**
```json
{
  "slug": "block-3-quiz",
  "type": "quiz",
  "title": "Quiz Pemahaman",
  "order": 3,
  "content": {
    "questions": [
      {
        "question": "Apa itu teknologi informasi?",
        "options": ["A", "B", "C"],
        "correct_answer": 0
      }
    ]
  },
  "status": "draft"
}
```

**Validasi:** Tergantung pada LessonBlockRequest (perlu diverifikasi)

**Headers:**
- Authorization: `Bearer {access_token}` (admin atau super-admin)

---

### 2. PUT /courses/{slug}/units/{slug}/lessons/{slug}/blocks/{slug} ⚠️ **PERLU DIPERBAHARUI**
**Status:** ❌ Body tidak lengkap (sama seperti POST blocks)

```json
{
  "slug": "block-1-video",
  "type": "video",
  "title": "Video: Pengenalan TI (Updated)",
  "order": 1,
  "content": {
    "video_url": "https://youtube.com/embed/...updated..."
  },
  "status": "published"
}
```

---

## 📊 SUMMARY

| Endpoint | Status | Action Required |
|----------|--------|-----------------|
| Auth - Register | ✅ | - |
| Auth - Login | ✅ | - |
| Auth - PUT Profile | ⚠️ | Lengkapi body dengan semua field |
| Auth - Email Verify | ⚠️ | Verifikasi dan lengkapi body |
| Auth - Email Verify by Token | ⚠️ | Verifikasi dan lengkapi body |
| Auth - Request Email Change | ⚠️ | Verifikasi dan lengkapi body |
| Auth - Verify Email Change | ⚠️ | Verifikasi dan lengkapi body |
| Auth - GET Users by ID | ❌ | Tambahkan endpoint baru |
| Common - Categories | ✅ | - |
| Schemes - POST Course | ⚠️ | Lengkapi body dengan semua field |
| Schemes - PUT Course | ⚠️ | Lengkapi body dengan semua field |
| Schemes - POST Unit | ⚠️ | Lengkapi body dengan semua field |
| Schemes - PUT Unit | ⚠️ | Lengkapi body dengan semua field |
| Schemes - Reorder Units | ⚠️ | Lengkapi body dengan format array |
| Schemes - POST Lesson | ⚠️ | Lengkapi body dengan semua field |
| Schemes - PUT Lesson | ⚠️ | Lengkapi body dengan semua field |
| Schemes - POST Lesson Block | ⚠️ | Verifikasi dan lengkapi body |
| Schemes - PUT Lesson Block | ⚠️ | Verifikasi dan lengkapi body |

---

## 🚀 NEXT STEPS

1. **Update Postman Collection** dengan contoh raw body di atas
2. **Tambahkan endpoint baru** `GET /auth/users/{user}`
3. **Verifikasi LessonBlockRequest** untuk melihat validasi yang tepat
4. **Test semua endpoint** dengan body yang sudah diupdate
5. **Add response examples** untuk setiap endpoint

---

**Last Updated:** November 12, 2025
**Author:** Documentation Generator
