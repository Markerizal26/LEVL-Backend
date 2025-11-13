# Prep LSP API Postman Collection Structure

Gunakan struktur berikut ketika menyusun Postman collection. Setiap folder mewakili module di backend. Nested folder menjaga pemisahan antara endpoint publik, endpoint yang membutuhkan autentikasi, serta batasan role.

```
Prep LSP API
├── Auth
│   ├── Public Auth
│   ├── Profile & Account
│   ├── Managed Users (Admin & Super Admin)
│   ├── Email Verification
│   ├── Password Recovery
│   └── OAuth (Google)
├── Schemes
│   ├── Courses
│   ├── Course Publication
│   ├── Units
│   ├── Lessons
│   ├── Lesson Blocks
│   ├── Course Tags
│   └── Progress
└── Enrollments
    ├── Student Actions
    ├── Course Admin & Instructor
    └── Super Admin Overview
```

Setiap item endpoint berisi:

- **Authorization** (Bearer token + role jika wajib).
- **Description** singkat.
- **Query Params** (untuk GET) berisi tabel `Key | Type | Required | Description | Example`.
- **Body** (untuk POST/PUT/DELETE) menggunakan mode *form-data* atau *raw JSON* sesuai kebutuhan; tabel `Key | Type | Required | Description | Example`.
- **Tests** (opsional) untuk memeriksa status, payload, atau menyimpan token.

Kolom **Request Name** dalam tabel di bawah bisa langsung dipakai sebagai nama request di Postman agar konsisten.

---

## 1. Auth Module (`/api/v1`)

### 1.1 Public Auth

| Request Name | Method | Path | Description | Auth |
| --- | --- | --- | --- | --- |
| `Register` | POST | `/auth/register` | Registrasi user baru | Tidak |
| `Login` | POST | `/auth/login` | Login dengan email/username | Tidak |
| `Refresh Token` | POST | `/auth/refresh` | Refresh JWT (harus pakai `AllowExpiredToken`) | Opsional (expired token diperbolehkan) |

#### Body (form-data / x-www-form-urlencoded)

**Register**

| Key | Type | Required | Description | Example |
| --- | --- | --- | --- | --- |
| `name` | string | yes | Nama lengkap | `Budi Santoso` |
| `username` | string | yes | Username unik (alphanumeric, ., -, _) | `budi.santoso` |
| `email` | string | yes | Email unik | `budi@example.com` |
| `password` | string | yes | Minimal 8 karakter, mengikuti aturan password module | `Rahasia123!` |

**Login**

| Key | Type | Required | Description | Example |
| --- | --- | --- | --- | --- |
| `login` | string | yes | Email atau username | `budi@example.com` |
| `password` | string | yes | Password | `Rahasia123!` |

**Refresh**

| Key | Type | Required | Description | Example |
| --- | --- | --- | --- | --- |
| `refresh_token` | string | yes | Refresh token aktif (14d idle / 90d absolute) | `<refresh_token>` |

### 1.2 Profile & Account (Auth: Bearer, Role: `student`/`instructor`/`admin`/`superadmin`)

| Request Name | Method | Path | Description |
| --- | --- | --- | --- |
| `Get Profile` | GET | `/profile` | Detail user saat ini |
| `Update Profile` | PUT | `/profile` | Update nama, username, avatar |
| `Logout` | POST | `/auth/logout` | Logout + revoke refresh token |
| `Set Username` | POST | `/auth/set-username` | Set username pertama kali |
| `Send Email Verification` | POST | `/auth/email/verify/send` | Kirim ulang email verifikasi |
| `Request Email Change` | POST | `/profile/email/request` | Permintaan ganti email |
| `Verify Email Change` | POST | `/profile/email/verify` | Verifikasi ganti email |

#### Body (form-data)

**Update Profile**

| Key | Type | Required | Description | Example |
| --- | --- | --- | --- | --- |
| `name` | string | yes | Nama baru | `Budi Update` |
| `username` | string | yes | Username baru (unik) | `budi.update` |
| `avatar` | file | no | Gambar avatar (`jpg`,`jpeg`,`png`,`webp`, max 2MB) | `avatar.png` |

**Logout**

| Key | Type | Required | Description | Example |
| --- | --- | --- | --- | --- |
| `refresh_token` | string | no | Refresh token yang ingin dicabut | `<refresh_token>` |

**Set Username**

| Key | Type | Required | Description | Example |
| --- | --- | --- | --- | --- |
| `username` | string | yes | Username baru (tidak boleh dipakai) | `budi.baru` |

**Request Email Change**

| Key | Type | Required | Description | Example |
| --- | --- | --- | --- | --- |
| `new_email` | string | yes | Email pengganti | `budi+baru@example.com` |

**Verify Email Change**

| Key | Type | Required | Description | Example |
| --- | --- | --- | --- | --- |
| `uuid` | string | yes | UUID permintaan | `e3f5c...` |
| `code` | string | yes | OTP 6 digit | `123456` |

**Verify Email**

| Key | Type | Required | Description | Example |
| --- | --- | --- | --- | --- |
| `uuid` | string | yes* | UUID verifikasi | `2ff6a...` |
| `token` | string | yes* | Alternative token | `token-signed` |
| `code` | string | yes | OTP 6 digit | `654321` |

> `uuid` atau `token` salah satu wajib (`required_without`).

### 1.3 Managed Users (Role: `admin` atau `superadmin`)

| Request Name | Method | Path | Role | Description |
| --- | --- | --- | --- | --- |
| `Create Instructor` | POST | `/auth/instructor` | admin / superadmin | Buat instructor |
| `Create Admin` | POST | `/auth/admin` | superadmin | Buat admin |
| `Create Super Admin` | POST | `/auth/super-admin` | superadmin | Buat superadmin |
| `Update User Status` | PUT | `/auth/users/{user}/status` | superadmin | Update status user |
| `List Users` | GET | `/auth/users` | superadmin | Daftar user dengan filter |
| `Show User` | GET | `/auth/users/{user}` | superadmin | Detail user |
| `Resend Credentials` | POST | `/auth/credentials/resend` | superadmin | Kirim ulang kredensial |

#### Body (form-data)

**Create Managed User (instruktur/admin/superadmin)**

| Key | Type | Required | Description | Example |
| --- | --- | --- | --- | --- |
| `name` | string | yes | Nama | `Rina Putri` |
| `username` | string | yes | Username unik | `rina.putri` |
| `email` | string | yes | Email unik | `rina@example.com` |

**Update User Status**

| Key | Type | Required | Description | Example |
| --- | --- | --- | --- | --- |
| `status` | string | yes | `active`, `inactive`, `suspended`, dsb (sesuai model) | `inactive` |

**Resend Credentials**

| Key | Type | Required | Description | Example |
| --- | --- | --- | --- | --- |
| `user_id` | integer | yes | ID user target | `42` |

#### Query Params (GET `/auth/users`)

| Key | Type | Required | Description | Example |
| --- | --- | --- | --- | --- |
| `search` | string | no | Cari nama/email/username | `rina` |
| `role` | string | no | Filter role | `student` |
| `status` | string | no | Filter status | `active` |
| `per_page` | integer | no | Default 15 | `30` |
| `page` | integer | no | Page ke-n | `2` |

### 1.4 Password Recovery

| Request Name | Method | Path | Description | Auth |
| --- | --- | --- | --- | --- |
| `Forgot Password` | POST | `/auth/password/forgot` | Kirim OTP reset | Tidak |
| `Confirm Forgot Password` | POST | `/auth/password/forgot/confirm` | Konfirmasi OTP sebelum reset | Tidak |
| `Reset Password` | POST | `/auth/password/reset` | Reset password (butuh login) | Bearer |

#### Body

**Forgot Password**

| Key | Type | Required | Description | Example |
| --- | --- | --- | --- | --- |
| `login` | string | yes | Email atau username | `rina@example.com` |

**Confirm Forgot**

| Key | Type | Required | Description | Example |
| --- | --- | --- | --- | --- |
| `login` | string | yes | Email / username | `rina@example.com` |
| `token` | string | yes | OTP 6 digit | `123456` |

**Reset Password**

| Key | Type | Required | Description | Example |
| --- | --- | --- | --- | --- |
| `token` | string | yes | OTP 6 digit | `123456` |
| `password` | string | yes | Password baru (strong rules) | `Baru123!` |

### 1.5 OAuth (Google)

| Request Name | Method | Path | Description | Notes |
| --- | --- | --- | --- | --- |
| `Google OAuth Redirect` | GET | `/auth/google/redirect` | Redirect ke Google OAuth | Tidak ada body |
| `Google OAuth Callback` | GET | `/auth/google/callback` | Callback (redirect ke FE) | Tidak dipanggil via Postman |

---

## 2. Schemes Module (`/api/v1`)

### 2.1 Courses

| Request Name | Method | Path | Description | Auth | Role |
| --- | --- | --- | --- | --- | --- |
| `List Courses` | GET | `/courses` | List course dengan filter | Opsional | - |
| `Get Course Detail` | GET | `/courses/{slug}` | Detail course publik | Opsional | - |
| `Create Course` | POST | `/courses` | Buat course baru | Bearer | admin / superadmin |
| `Update Course` | PUT | `/courses/{slug}` | Update course | Bearer | admin / superadmin |
| `Delete Course` | DELETE | `/courses/{slug}` | Hapus permanen | Bearer | admin / superadmin |

#### Query Params (GET `/courses`)

| Key | Type | Description | Example |
| --- | --- | --- | --- |
| `search` | string | Cari `title` atau `short_desc` | `backend` |
| `status` | string | Gunakan `published` agar memakai listPublic | `published` |
| `filter[status]` | string | `draft`, `published`, `archived` | `draft` |
| `filter[level]` | string | `dasar`, `menengah`, `mahir` | `menengah` |
| `filter[type]` | string | `okupasi`, `kluster` | `okupasi` |
| `filter[category]` | array/string | ID kategori (array atau JSON encoded) | `[1,2]` |
| `category_id` | array/string | Legacy filter kategori | `3` |
| `filter[tag]` | array/string | Filter berdasarkan slug atau nama tag | `["design"]` |
| `tags[]` | array | Legacy filter tags | `backend` |
| `sort` | string | `title`, `code`, `created_at`, prefix `-` untuk desc | `-created_at` |
| `per_page` | integer | Default 15 | `20` |
| `page` | integer | Halaman | `2` |

#### Body (form-data) untuk POST / PUT

| Key | Type | Required | Description | Example |
| --- | --- | --- | --- | --- |
| `code` | string | yes | Kode unik course | `CS-101` |
| `slug` | string | no | Slug custom (otomatis unique jika kosong) | `intro-to-cs` |
| `title` | string | yes | Judul course | `Intro to Computer Science` |
| `short_desc` | string | no | Deskripsi singkat | `Belajar dasar komputer` |
| `level_tag` | string | yes | `dasar` \| `menengah` \| `mahir` | `dasar` |
| `type` | string | yes | `okupasi` \| `kluster` | `kluster` |
| `enrollment_type` | string | yes | `auto_accept` \| `key_based` \| `approval` | `key_based` |
| `enrollment_key` | string | conditional | Wajib jika `enrollment_type = key_based` saat create | `JOIN-2025` |
| `progression_mode` | string | yes | `sequential` atau `free` | `sequential` |
| `category_id` | integer | no | ID kategori | `5` |
| `tags[]` | array / json string | no | Daftar tag (nama / slug) | `["backend","php"]` |
| `outcomes[]` | array / json string | no | Outcome belajar | `["Lulus","Beriman"]` |
| `prereq[]` | array / json string | no | Prasyarat | `["Dasar PHP"]` |
| `status` | string | no | `draft` (default) / `published` / `archived` | `draft` |
| `instructor_id` | integer | no | ID instruktur utama | `12` |
| `course_admins[]` | array | no | ID admin course tambahan | `[2,3]` |
| `thumbnail` | file | no | Gambar thumbnail (`jpg`,`jpeg`,`png`,`webp`, max 4MB) | `thumb.png` |
| `banner` | file | no | Gambar banner (`jpg`,`jpeg`,`png`,`webp`, max 6MB) | `banner.jpg` |

> Gunakan tab `Bulk Edit` di Postman jika ingin mengirim array dalam bentuk JSON string (`["tag"]`). Backend sudah melakukan decoding otomatis untuk field `tags`, `outcomes`, `prereq`, `course_admins`.

### 2.2 Course Publication (Role: admin / superadmin)

| Request Name | Method | Path | Description |
| --- | --- | --- | --- |
| `Publish Course` | PUT | `/courses/{slug}/publish` | Set status `published` (auto set `published_at`) |
| `Unpublish Course` | PUT | `/courses/{slug}/unpublish` | Kembali ke `draft` |

Tidak ada body; gunakan Bearer token.

### 2.3 Units

| Request Name | Method | Path | Description | Role |
| --- | --- | --- | --- | --- |
| `List Units` | GET | `/courses/{course}/units` | Daftar unit | Publik |
| `Get Unit Detail` | GET | `/courses/{course}/units/{unit}` | Detail unit | Publik |
| `Create Unit` | POST | `/courses/{course}/units` | Tambah unit | admin / superadmin |
| `Update Unit` | PUT | `/courses/{course}/units/{unit}` | Update unit | admin / superadmin |
| `Delete Unit` | DELETE | `/courses/{course}/units/{unit}` | Hapus unit | admin / superadmin |
| `Reorder Units` | PUT | `/courses/{course}/units/reorder` | Reorder unit | admin / superadmin |
| `Publish Unit` | PUT | `/courses/{course}/units/{unit}/publish` | Publish unit | admin / superadmin |
| `Unpublish Unit` | PUT | `/courses/{course}/units/{unit}/unpublish` | Unpublish unit | admin / superadmin |

#### Body (form-data)

**Create / Update Unit**

| Key | Type | Required | Description | Example |
| --- | --- | --- | --- | --- |
| `code` | string | yes | Kode unik dalam course | `UNIT-1` |
| `slug` | string | no | Slug custom | `basic-concepts` |
| `title` | string | yes | Judul unit | `Konsep Dasar` |
| `description` | string | no | Deskripsi | `Materi pengantar` |
| `order` | integer | no | Urutan (>=1) | `1` |
| `status` | string | no | `draft` / `published` | `draft` |

**Reorder Units**

| Key | Type | Required | Description | Example |
| --- | --- | --- | --- | --- |
| `units[]` | array | yes | Array id unit sesuai urutan baru | `[12,15,8]` |

### 2.4 Lessons

| Request Name | Method | Path | Description | Role |
| --- | --- | --- | --- | --- |
| `List Lessons` | GET | `/courses/{course}/units/{unit}/lessons` | Daftar lesson | Authenticated |
| `Get Lesson Detail` | GET | `/courses/{course}/units/{unit}/lessons/{lesson}` | Detail lesson | Authenticated |
| `Create Lesson` | POST | `/courses/{course}/units/{unit}/lessons` | Tambah lesson | admin / superadmin |
| `Update Lesson` | PUT | `/courses/{course}/units/{unit}/lessons/{lesson}` | Update lesson | admin / superadmin |
| `Delete Lesson` | DELETE | `/courses/{course}/units/{unit}/lessons/{lesson}` | Hapus lesson | admin / superadmin |
| `Publish Lesson` | PUT | `/courses/{course}/units/{unit}/lessons/{lesson}/publish` | Publish lesson | admin / superadmin |
| `Unpublish Lesson` | PUT | `/courses/{course}/units/{unit}/lessons/{lesson}/unpublish` | Unpublish lesson | admin / superadmin |
| `Complete Lesson` | POST | `/courses/{course}/units/{unit}/lessons/{lesson}/complete` | Tandai lesson selesai (student) | Authenticated |

#### Body (form-data) Create / Update Lesson

| Key | Type | Required | Description | Example |
| --- | --- | --- | --- | --- |
| `slug` | string | no | Slug custom | `pengantar-komputer` |
| `title` | string | yes | Judul lesson | `Pengantar Komputer` |
| `description` | string | no | Deskripsi | `Tujuan dan ringkasan` |
| `markdown_content` | string | no | Konten markdown | `# Materi` |
| `order` | integer | no | Urutan | `1` |
| `duration_minutes` | integer | no | Durasi estimasi | `45` |
| `status` | string | no | `draft` / `published` | `draft` |

### 2.5 Lesson Blocks

| Request Name | Method | Path | Description | Role |
| --- | --- | --- | --- | --- |
| `List Lesson Blocks` | GET | `/courses/{course}/units/{unit}/lessons/{lesson}/blocks` | Daftar block | Authenticated |
| `Get Lesson Block Detail` | GET | `/courses/{course}/units/{unit}/lessons/{lesson}/blocks/{block}` | Detail block | Authenticated |
| `Create Lesson Block` | POST | `/courses/{course}/units/{unit}/lessons/{lesson}/blocks` | Tambah block | admin / superadmin |
| `Update Lesson Block` | PUT | `/courses/{course}/units/{unit}/lessons/{lesson}/blocks/{block}` | Update block | admin / superadmin |
| `Delete Lesson Block` | DELETE | `/courses/{course}/units/{unit}/lessons/{lesson}/blocks/{block}` | Hapus block | admin / superadmin |

> Sesuaikan field body berdasarkan tipe block (cek request class khusus jika tersedia).

### 2.6 Course Tags

| Request Name | Method | Path | Description | Auth | Role |
| --- | --- | --- | --- | --- | --- |
| `List Course Tags` | GET | `/course-tags` | List tag (publik) | Tidak | - |
| `Get Course Tag Detail` | GET | `/course-tags/{slug}` | Detail tag | Tidak | - |
| `Create Course Tag` | POST | `/course-tags` | Buat tag | Bearer | admin / superadmin |
| `Update Course Tag` | PUT | `/course-tags/{slug}` | Update tag | Bearer | admin / superadmin |
| `Delete Course Tag` | DELETE | `/course-tags/{slug}` | Hapus tag | Bearer | admin / superadmin |

#### Query Params (GET `/course-tags`)

| Key | Type | Description | Example |
| --- | --- | --- | --- |
| `search` | string | Filter nama / slug | `design` |
| `per_page` | integer | aktifkan pagination (default semua) | `20` |
| `page` | integer | Halaman (jika pakai `per_page`) | `2` |

#### Body (form-data)

**Create Tag (single)**

| Key | Type | Required | Description | Example |
| --- | --- | --- | --- | --- |
| `name` | string | yes* | Nama tag | `Backend` |

**Create Tag (multiple)**

| Key | Type | Required | Description | Example |
| --- | --- | --- | --- | --- |
| `names[]` | array | yes* | Array nama tag | `["Backend","PHP"]` |

> Salah satu dari `name` atau `names[]` wajib ada.

**Update Tag**

| Key | Type | Required | Description | Example |
| --- | --- | --- | --- | --- |
| `name` | string | yes | Nama baru | `Data Science` |

### 2.7 Progress

| Request Name | Method | Path | Description | Auth | Role |
| --- | --- | --- | --- | --- | --- |
| `Get Course Progress` | GET | `/courses/{course}/progress` | Ambil progres course | Bearer | student / instructor / admin / superadmin |
| `Complete Lesson (Progress)` | POST | `/courses/{course}/units/{unit}/lessons/{lesson}/complete` | Tandai lesson selesai | Bearer | student |

Tidak ada query param tambahan. Body untuk `complete` kosong.

---

## 3. Enrollments Module (`/api/v1`)

### 3.1 Student Actions (Role: `student`, kecuali `user_id` override oleh `superadmin`)

| Request Name | Method | Path | Description |
| --- | --- | --- | --- |
| `Enroll Course` | POST | `/courses/{course}/enrollments` | Enrol ke course |
| `Cancel Enrollment Request` | POST | `/courses/{course}/cancel` | Batalkan permintaan pending |
| `Withdraw Enrollment` | POST | `/courses/{course}/withdraw` | Mundur dari course aktif |
| `Get Enrollment Status` | GET | `/courses/{course}/enrollment-status` | Lihat status enrolment |

#### Body (form-data)

**Enroll Course**

| Key | Type | Required | Description | Example |
| --- | --- | --- | --- | --- |
| `enrollment_key` | string | conditional | Wajib jika course `key_based` | `JOIN-1234` |

**Cancel / Withdraw**

| Key | Type | Required | Description | Example |
| --- | --- | --- | --- | --- |
| `user_id` | integer | no | Hanya untuk superadmin membatalkan/mengundurkan user lain | `21` |

#### Query Params (GET `/enrollment-status`)

| Key | Type | Description | Example |
| --- | --- | --- | --- |
| `user_id` | integer | Opsional, hanya untuk superadmin melihat status user lain | `21` |

### 3.2 Course Admin & Instructor

| Request Name | Method | Path | Description | Role |
| --- | --- | --- | --- | --- |
| `List Managed Enrollments` | GET | `/courses/enrollments` | Daftar enrolment dari semua course yang dikelola | admin / instructor / superadmin |
| `List Course Enrollments` | GET | `/courses/{course}/enrollments` | Daftar enrolment course tertentu | admin / instructor / superadmin |
| `Approve Enrollment` | POST | `/enrollments/{enrollment}/approve` | Setujui pending | admin / instructor / superadmin |
| `Decline Enrollment` | POST | `/enrollments/{enrollment}/decline` | Tolak pending | admin / instructor / superadmin |
| `Remove Enrollment` | POST | `/enrollments/{enrollment}/remove` | Keluarkan peserta | admin / instructor / superadmin |

#### Query Params (GET `/courses/{course}/enrollments`)

| Key | Type | Description | Example |
| --- | --- | --- | --- |
| `status` | string | Filter `pending`, `active`, `cancelled`, `completed` | `pending` |
| `course_slug` | string | Filter enrolment pada course tertentu (harus course yang dikelola) | `intro-to-computer-science` |
| `per_page` | integer | Default 15 | `20` |
| `page` | integer | Halaman | `2` |

Tidak ada body tambahan untuk approve/decline/remove.

### 3.3 Super Admin Overview

| Request Name | Method | Path | Description |
| --- | --- | --- | --- |
| `List All Enrollments` | GET | `/enrollments` | Daftar seluruh enrolment (superadmin saja) |

#### Query Params

| Key | Type | Description | Example |
| --- | --- | --- | --- |
| `status` | string | Filter status | `pending` |
| `course_id` | integer | Filter berdasarkan course ID | `12` |
| `user_id` | integer | Filter berdasarkan user ID | `34` |
| `per_page` | integer | Default 15 | `50` |
| `page` | integer | Halaman | `3` |

---

## 4. Catatan Tambahan

- Pastikan environment Postman menyimpan variabel:
  - `{{base_url}}` → misal `https://app.test/api/v1`.
  - `{{access_token}}` → token JWT aktif.
  - `{{refresh_token}}` → token refresh terbaru.
- Untuk upload file gunakan tab `Body -> form-data` dengan tipe `File`.
- Simpan script Postman Test untuk otomatis memperbarui `access_token` & `refresh_token` saat login/refresh.
- Endpoint lain dari module lain (Assessments, Gamification, dll.) dapat ditambahkan mengikuti pola yang sama begitu siap digunakan.

Dengan struktur ini, Postman collection mudah dibagikan dan setiap module terkelola rapi sesuai domainnya.

