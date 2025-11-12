# Quick Reference - Copy-Paste Request Bodies

File ini berisi contoh-contoh request body siap pakai yang bisa langsung di-copy-paste ke Postman.

---

## 🚀 READY-TO-USE REQUEST BODIES

### AUTH MODULE

#### 1. PUT /profile (JSON)
```json
{
  "name": "Budi Santoso",
  "username": "budisantoso"
}
```

#### 2. PUT /profile (Form-Data with Avatar)
```
Key: name | Value: Budi Santoso
Key: username | Value: budisantoso
Key: avatar | Value: [SELECT FILE]
```

#### 3. POST /auth/email/verify (With UUID)
```json
{
  "uuid": "550e8400-e29b-41d4-a716-446655440000",
  "code": "123456"
}
```

#### 4. POST /auth/email/verify (With Token)
```json
{
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiaWF0IjoxNTE2MjM5MDIyfQ.SflKxwRJSMeKKF2QT4fwpMeJf36POk6yJV_adQssw5c",
  "code": "123456"
}
```

#### 5. POST /auth/email/verify/by-token
```json
{
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiaWF0IjoxNTE2MjM5MDIyfQ.SflKxwRJSMeKKF2QT4fwpMeJf36POk6yJV_adQssw5c",
  "email": "johndoe@example.com"
}
```

#### 6. POST /profile/email/request
```json
{
  "new_email": "newemail@example.com"
}
```

#### 7. POST /profile/email/verify
```json
{
  "uuid": "550e8400-e29b-41d4-a716-446655440000",
  "code": "123456"
}
```

---

### COMMON MODULE

#### 1. POST /categories
```json
{
  "name": "Teknologi Informasi",
  "value": "teknologi-informasi",
  "description": "Kategori untuk kurikulum di bidang teknologi informasi",
  "status": "active"
}
```

#### 2. PUT /categories/{id}
```json
{
  "name": "Teknologi Informasi & Komunikasi",
  "value": "teknologi-informasi",
  "description": "Kategori untuk kurikulum bidang teknologi informasi dan komunikasi",
  "status": "active"
}
```

---

### SCHEMES MODULE - COURSES

#### 1. POST /courses (Minimal - Draft)
```json
{
  "code": "DASAR-TI-001",
  "title": "Dasar Teknologi Informasi",
  "level_tag": "dasar",
  "type": "okupasi",
  "visibility": "public",
  "progression_mode": "sequential"
}
```

#### 2. POST /courses (Complete - Ready to Publish)
```json
{
  "code": "DASAR-TI-2025",
  "slug": "dasar-teknologi-informasi-2025",
  "title": "Dasar Teknologi Informasi - Edisi 2025",
  "short_desc": "Kursus komprehensif tentang fondasi teknologi informasi modern dengan praktik langsung",
  "level_tag": "dasar",
  "type": "okupasi",
  "visibility": "public",
  "progression_mode": "sequential",
  "category_id": 1,
  "instructor_id": 5,
  "status": "draft",
  "tags": ["teknologi", "dasar", "ti", "informatika", "komputer"],
  "outcomes": [
    "Memahami konsep dasar dan sejarah teknologi informasi",
    "Menguasai penggunaan komputer dan sistem operasi Windows/Linux",
    "Mampu menggunakan aplikasi perkantoran (Word, Excel, PowerPoint) dengan efisien",
    "Memahami networking dasar dan koneksi internet",
    "Familiar dengan cloud computing dan web services"
  ],
  "prereq": [
    "Literasi digital minimal",
    "Kemampuan berbahasa Indonesia dengan baik",
    "Keinginan untuk belajar teknologi"
  ],
  "course_admins": [5, 6]
}
```

#### 3. POST /courses (Form-Data with Files)
```
name: code | value: DASAR-TI-2025
name: title | value: Dasar Teknologi Informasi - Edisi 2025
name: short_desc | value: Kursus komprehensif tentang fondasi teknologi informasi
name: level_tag | value: dasar
name: type | value: okupasi
name: visibility | value: public
name: progression_mode | value: sequential
name: category_id | value: 1
name: instructor_id | value: 5
name: status | value: draft
name: tags | value: ["teknologi","dasar","ti","informatika"]
name: outcomes | value: ["Memahami konsep dasar","Menguasai penggunaan komputer"]
name: course_admins | value: [5,6]
name: thumbnail | value: [FILE: course_thumbnail.jpg]
name: banner | value: [FILE: course_banner.jpg]
```

#### 4. PUT /courses/{slug}
```json
{
  "code": "DASAR-TI-2025",
  "slug": "dasar-teknologi-informasi-2025",
  "title": "Dasar Teknologi Informasi - Edisi 2025 (Updated)",
  "short_desc": "Kursus komprehensif tentang fondasi teknologi informasi modern dengan praktik langsung - versi terbaru",
  "level_tag": "dasar",
  "type": "okupasi",
  "visibility": "public",
  "progression_mode": "sequential",
  "category_id": 1,
  "instructor_id": 5,
  "status": "published",
  "tags": ["teknologi", "dasar", "ti", "informatika", "komputer", "edisi-2025"],
  "outcomes": [
    "Memahami konsep dasar dan sejarah teknologi informasi",
    "Menguasai penggunaan komputer dan sistem operasi Windows/Linux",
    "Mampu menggunakan aplikasi perkantoran dengan efisien",
    "Memahami networking dasar dan koneksi internet",
    "Familiar dengan cloud computing dan web services",
    "Memahami keamanan siber dasar"
  ],
  "prereq": [],
  "course_admins": [5, 6, 7]
}
```

---

### SCHEMES MODULE - UNITS

#### 1. POST /courses/{slug}/units (Minimal)
```json
{
  "code": "UNIT-001",
  "title": "Pengenalan Dasar Teknologi Informasi"
}
```

#### 2. POST /courses/{slug}/units (Complete)
```json
{
  "code": "UNIT-001",
  "slug": "pengenalan-dasar",
  "title": "Unit 1: Pengenalan Dasar Teknologi Informasi",
  "description": "Unit ini memperkenalkan konsep fundamental, sejarah perkembangan, dan komponen utama dari teknologi informasi",
  "order": 1,
  "status": "draft"
}
```

#### 3. PUT /courses/{slug}/units/{slug}
```json
{
  "code": "UNIT-001",
  "slug": "pengenalan-dasar",
  "title": "Unit 1: Pengenalan Dasar Teknologi Informasi (Updated)",
  "description": "Unit ini memperkenalkan konsep fundamental, sejarah perkembangan, komponen utama, dan tren terkini dari teknologi informasi",
  "order": 1,
  "status": "published"
}
```

#### 4. PUT /courses/{slug}/units/reorder (5 Units)
```json
{
  "units": [3, 1, 2, 5, 4]
}
```

#### 5. PUT /courses/{slug}/units/reorder (4 Units)
```json
{
  "units": [10, 8, 9, 7]
}
```

#### 6. PUT /courses/{slug}/units/reorder (3 Units)
```json
{
  "units": [15, 14, 13]
}
```

---

### SCHEMES MODULE - LESSONS

#### 1. POST /courses/{slug}/units/{slug}/lessons (Minimal)
```json
{
  "title": "Apa Itu Teknologi Informasi?"
}
```

#### 2. POST /courses/{slug}/units/{slug}/lessons (With Content)
```json
{
  "slug": "lesson-1-apa-itu-ti",
  "title": "Apa Itu Teknologi Informasi?",
  "description": "Pelajaran pertama yang menjelaskan definisi, sejarah, dan perkembangan teknologi informasi",
  "markdown_content": "# Apa Itu Teknologi Informasi?\n\nTeknologi Informasi (TI) adalah penggunaan komputer dan perangkat lunak untuk mengelola dan mendistribusikan informasi.\n\n## Komponen Utama TI\n\n### 1. Hardware\n- CPU\n- RAM\n- Storage\n- Peripheral Devices\n\n### 2. Software\n- Operating System\n- Application Software\n- Programming Language\n\n### 3. Network\n- LAN (Local Area Network)\n- WAN (Wide Area Network)\n- Internet\n\n### 4. Data\n- Database\n- Data Management\n- Data Security",
  "order": 1,
  "duration_minutes": 45,
  "status": "draft"
}
```

#### 3. PUT /courses/{slug}/units/{slug}/lessons/{slug}
```json
{
  "slug": "lesson-1-apa-itu-ti",
  "title": "Apa Itu Teknologi Informasi? (Updated)",
  "description": "Pelajaran pertama yang menjelaskan definisi, sejarah, dan perkembangan teknologi informasi - versi terbaru",
  "markdown_content": "# Apa Itu Teknologi Informasi? (v2.0)\n\nTeknologi Informasi (TI) adalah penggunaan komputer dan perangkat lunak untuk mengelola dan mendistribusikan informasi secara efisien...",
  "order": 1,
  "duration_minutes": 60,
  "status": "published"
}
```

---

### SCHEMES MODULE - LESSON BLOCKS

#### 1. POST /courses/{slug}/units/{slug}/lessons/{slug}/blocks (Text Type)
```json
{
  "type": "text",
  "content": "Ini adalah konten teks pembelajaran. Teknologi Informasi telah mengubah cara kita bekerja dan berkomunikasi dalam era digital ini.",
  "order": 1
}
```

#### 2. POST /courses/{slug}/units/{slug}/lessons/{slug}/blocks (Video Type)
```
Form-Data:
name: type | value: video
name: content | value: Video penjelasan tentang sejarah teknologi informasi
name: order | value: 2
name: media | value: [FILE: video_sejarah_ti.mp4]
```

#### 3. POST /courses/{slug}/units/{slug}/lessons/{slug}/blocks (Image Type)
```
Form-Data:
name: type | value: image
name: content | value: Diagram infrastruktur teknologi informasi modern
name: order | value: 3
name: media | value: [FILE: infra_ti_diagram.jpg]
```

#### 4. POST /courses/{slug}/units/{slug}/lessons/{slug}/blocks (File Type - PDF)
```
Form-Data:
name: type | value: file
name: content | value: Download slide presentasi lengkap tentang pengenalan teknologi informasi
name: order | value: 4
name: media | value: [FILE: slide_presentasi_ti.pdf]
```

#### 5. PUT /courses/{slug}/units/{slug}/lessons/{slug}/blocks/{slug} (Text Update)
```json
{
  "type": "text",
  "content": "Konten teks yang telah diperbarui dengan informasi lebih lengkap dan contoh kasus nyata.",
  "order": 1
}
```

#### 6. PUT /courses/{slug}/units/{slug}/lessons/{slug}/blocks/{slug} (Video Update)
```
Form-Data:
name: type | value: video
name: content | value: Video penjelasan terbaru tentang sejarah dan perkembangan teknologi informasi
name: order | value: 2
name: media | value: [FILE: video_sejarah_ti_updated.mp4]
```

---

## 💡 USAGE TIPS

### Tip 1: Using Variables in Postman
Replace hardcoded values with Postman variables:
- `{{API_URL}}` - Base URL of API (e.g., http://localhost:8000/api)
- `{{access_token}}` - JWT Access Token (set after login)
- `{{refresh_token}}` - JWT Refresh Token (set after login)
- `{{course_slug}}` - Current course slug
- `{{unit_slug}}` - Current unit slug
- `{{lesson_slug}}` - Current lesson slug
- `{{user_id}}` - Current user ID

### Tip 2: Pre-request Script for File Uploads
If you need to simulate file uploads in pre-request scripts:
```javascript
// This is JavaScript code in Postman pre-request tab
// Usually not needed - just use the UI file picker
```

### Tip 3: Testing with Different Status Values
When testing Course/Unit/Lesson creation, use these status values:
- `draft` - Work in progress, not visible to students
- `published` - Active course, visible and available for enrollment
- `archived` - Old course, no longer accepting new enrollments

### Tip 4: Array Fields Format
When sending arrays in JSON:
```json
{
  "tags": ["tag1", "tag2", "tag3"],
  "outcomes": ["outcome1", "outcome2"],
  "course_admins": [1, 2, 3]
}
```

When sending arrays in Form-Data (JSON string):
```
name: tags | value: ["tag1","tag2","tag3"]
name: outcomes | value: ["outcome1","outcome2"]
name: course_admins | value: [1,2,3]
```

### Tip 5: Common Errors and Solutions

| Error | Cause | Solution |
|-------|-------|----------|
| 401 Unauthorized | Missing/invalid token | Run Login endpoint first, check access_token |
| 422 Validation Error | Invalid field values | Check field requirements in validation rules |
| 404 Not Found | Resource doesn't exist | Verify ID/slug exists in database |
| 403 Forbidden | Insufficient permissions | Check user role (admin/super-admin required) |
| 500 Server Error | Server issue | Check server logs, may be database connection error |

---

## ✅ TESTING CHECKLIST

Use this checklist to systematically test all endpoints:

### Phase 1: Authentication
- [ ] POST /auth/register - Create new user
- [ ] POST /auth/login - Login and save tokens
- [ ] GET /profile - Get current user profile
- [ ] PUT /profile - Update profile
- [ ] POST /auth/refresh - Refresh access token
- [ ] POST /auth/logout - Logout

### Phase 2: Email Verification
- [ ] POST /auth/email/verify/send - Request email verification
- [ ] POST /auth/email/verify - Verify email with code
- [ ] POST /profile/email/request - Request email change
- [ ] POST /profile/email/verify - Verify new email

### Phase 3: Categories (Common)
- [ ] GET /categories - List all categories
- [ ] POST /categories - Create new category
- [ ] GET /categories/{id} - Get category detail
- [ ] PUT /categories/{id} - Update category
- [ ] DELETE /categories/{id} - Delete category

### Phase 4: Courses (Schemes)
- [ ] GET /courses - List courses
- [ ] POST /courses - Create course (draft)
- [ ] GET /courses/{slug} - Get course detail
- [ ] PUT /courses/{slug} - Update course
- [ ] PUT /courses/{slug}/publish - Publish course
- [ ] PUT /courses/{slug}/unpublish - Unpublish course
- [ ] DELETE /courses/{slug} - Delete course

### Phase 5: Units (Schemes)
- [ ] GET /courses/{slug}/units - List units
- [ ] POST /courses/{slug}/units - Create unit
- [ ] GET /courses/{slug}/units/{slug} - Get unit detail
- [ ] PUT /courses/{slug}/units/{slug} - Update unit
- [ ] PUT /courses/{slug}/units/reorder - Reorder units
- [ ] PUT /courses/{slug}/units/{slug}/publish - Publish unit
- [ ] PUT /courses/{slug}/units/{slug}/unpublish - Unpublish unit
- [ ] DELETE /courses/{slug}/units/{slug} - Delete unit

### Phase 6: Lessons (Schemes)
- [ ] GET /courses/{slug}/units/{slug}/lessons - List lessons
- [ ] POST /courses/{slug}/units/{slug}/lessons - Create lesson
- [ ] GET /courses/{slug}/units/{slug}/lessons/{slug} - Get lesson detail
- [ ] PUT /courses/{slug}/units/{slug}/lessons/{slug} - Update lesson
- [ ] PUT /courses/{slug}/units/{slug}/lessons/{slug}/publish - Publish lesson
- [ ] PUT /courses/{slug}/units/{slug}/lessons/{slug}/unpublish - Unpublish lesson
- [ ] DELETE /courses/{slug}/units/{slug}/lessons/{slug} - Delete lesson

### Phase 7: Lesson Blocks (Schemes)
- [ ] GET /courses/{slug}/units/{slug}/lessons/{slug}/blocks - List blocks
- [ ] POST /courses/{slug}/units/{slug}/lessons/{slug}/blocks - Create block (text)
- [ ] POST /courses/{slug}/units/{slug}/lessons/{slug}/blocks - Create block (video)
- [ ] POST /courses/{slug}/units/{slug}/lessons/{slug}/blocks - Create block (image)
- [ ] POST /courses/{slug}/units/{slug}/lessons/{slug}/blocks - Create block (file)
- [ ] GET /courses/{slug}/units/{slug}/lessons/{slug}/blocks/{slug} - Get block detail
- [ ] PUT /courses/{slug}/units/{slug}/lessons/{slug}/blocks/{slug} - Update block
- [ ] DELETE /courses/{slug}/units/{slug}/lessons/{slug}/blocks/{slug} - Delete block

---

**Last Updated:** November 12, 2025  
**Version:** 1.0
