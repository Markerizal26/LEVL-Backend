# Dokumentasi API Common

Dokumentasi lengkap untuk semua endpoint di module `Common`, termasuk manajemen Log, Tags, Master Data, Badges, Level Configs, dan Challenges.

---

## 1. Activity Logs (Log Aktivitas)

### 1.1 List Activity Logs
Mendapatkan daftar log aktivitas user.

- **Method**: `GET`
- **URL**: `/api/v1/activity-logs`
- **Akses**: `Superadmin`

**Query Parameters:**
| Parameter | Tipe | Wajib | Keterangan | Contoh |
|-----------|------|-------|------------|--------|
| `per_page` | integer | Tidak | Pagination (Default: 15) | `20` |
| `page` | integer | Tidak | Halaman | `1` |
| `sort` | string | Tidak | Sorting (`-created_at`, `created_at`, `log_name`, `event`) | `-created_at` |
| `filter[log_name]` | string | Tidak | Kategori log (`auth`, `system`, `user`, `course`) | `auth` |
| `filter[description]` | string | Tidak | Search deskripsi (partial) | `login` |
| `filter[event]` | string | Tidak | Event type (`login`, `logout`, `created`, `updated`, `deleted`) | `login` |
| `filter[subject_type]` | string | Tidak | Tipe subject (Class Name) | `Modules\Auth\Models\User` |
| `filter[subject_id]` | integer | Tidak | ID Subject | `1` |
| `filter[causer_type]` | string | Tidak | Tipe aktor (Class Name) | `Modules\Auth\Models\User` |
| `filter[causer_id]` | integer | Tidak | ID Aktor | `1` |
| `filter[properties.browser]` | string | Tidak | Browser (`Chrome`, `Firefox`, etc) | `Chrome` |
| `filter[properties.platform]` | string | Tidak | Platform (`Windows`, `macOS`, etc) | `macOS` |
| `filter[created_at_between]` | string | Tidak | Date Range (comma separated `YYYY-MM-DD`) | `2024-01-01,2024-01-31` |

**Response Example:**
```json
{
    "data": [
        {
            "id": 1,
            "log_name": "auth",
            "description": "User logged in",
            "properties": {
                "browser": "Chrome",
                "platform": "macOS",
                "ip": "127.0.0.1",
                "city": "Jakarta"
            },
            "created_at": "2024-01-01T00:00:00.000000Z",
            "updated_at": "2024-01-01T00:00:00.000000Z",
            "event": "login",
            "batch_uuid": null,
            "device_info": "-",
            "location": {
                "city": "Jakarta",
                "region": "DKI Jakarta",
                "country": "Indonesia"
            },
            "causer": {
                "id": 1,
                "name": "Super Admin",
                "username": "superadmin",
                "email": "superadmin@example.com",
                "status": "active",
                "avatar_url": "..."
            },
            "subject": {
                "id": 1,
                "name": "Super Admin",
                "username": "superadmin",
                "email": "superadmin@example.com"
            }
        }
    ],
    "meta": {
        "pagination": {
            "total": 50,
            "per_page": 15,
            "current_page": 1,
            "last_page": 4,
            "from": 1,
            "to": 15,
            "has_next": true,
            "has_prev": false
        }
    }
}
```

### 1.2 Detail Activity Log
Mendapatkan detail log tertentu.

- **Method**: `GET`
- **URL**: `/api/v1/activity-logs/{id}`
- **Akses**: `Superadmin`

### 1.3 Field Definitions
Penjelasan mengenai field-field penting dalam Activity Log:

| Field | Keterangan | Contoh Value |
|-------|------------|--------------|
| `log_name` | Kategori atau channel dari log aktivitas. Digunakan untuk mengelompokkan jenis aktivitas. | `auth` (Login/Logout), `system` (Error/Job), `user` (User Profile), `course` (Manajemen Course) |
| `event` | Jenis aksi yang dilakukan. | `login`, `logout`, `created`, `updated`, `deleted`, `restored` |
| `subject_type` | Class Log Activity Subject (Model yang menerima aksi). | `Modules\Auth\Models\User`, `Modules\Course\Models\Course` |
| `subject_id` | Primary Key ID dari `subject_type`. Jika aksi adalah hapus user dengan ID 5, maka subject_id = 5. | `1`, `100` |
| `causer_type` | Class Log Activity Causer (Aktor yang melakukan aksi). Biasanya adalah User model. | `Modules\Auth\Models\User` |
| `causer_id` | ID dari `causer_type` (User ID yang login). Jika null, berarti aksi dilakukan oleh sistem/bot. | `1` (Admin), `null` (System) |
| `causer` | Object data lengkap dari actor/causer (biasanya User). | `{ "id": 1, "name": "Admin", ... }` |
| `subject` | Object data partial dari subject. | `{ "id": 10, "name": "Course PHP" }` |
| `properties` | Metadata tambahan terkait event dalam format JSON. | `{"ip": "127.0.0.1", "browser": "Chrome", "old": {...}, "attributes": {...}}` |

---

## 2. Audit Logs (Log Audit)

### 2.1 List Audit Logs
Mendapatkan daftar audit logs sistem untuk keperluan validasi dan compliance.

- **Method**: `GET`
- **URL**: `/api/v1/audit-logs`
- **Akses**: `Admin`, `Superadmin`

**Query Parameters:**
| Parameter | Tipe | Keterangan | Valid Values / Contoh |
|-----------|------|------------|-----------------------|
| `page` | integer | Halaman | `1` |
| `per_page` | integer | Pagination | `15` |
| `sort` | string | Sorting (`-created_at`, `created_at`) | `-created_at` |
| `filter[action]` | string | Filter single action | `submission_created` |
| `filter[actions]` | string | Filter multiple actions (comma separated) | `grading,grade_override` |
| `filter[actor_id]` | integer | ID User Aktor | `1` |
| `filter[subject_id]` | integer | ID Subject | `10` |
| `filter[created_between]` | string | Filter date range (comma separated `YYYY-MM-DD`) | `2024-01-01,2024-12-31` |
| `filter[context_contains]`| string | Cari text dalam JSON context | `reason` |
| `filter[assignment_id]` | integer | Filter assignment ID in context | `5` |
| `filter[student_id]` | integer | Filter student ID in context | `20` |

**Valid Action Types:**
- `submission_created`: Siswa membuat submission.
- `state_transition`: Perubahan status submission (e.g., submitted -> graded).
- `grading`: Pemberian nilai oleh instruktur.
- `answer_key_change`: Perubahan kunci jawaban soal.
- `grade_override`: Pengubahan nilai secara manual (override).
- `override_grant`: Pemberian izin override khusus.

### 2.2 Detail Audit Log
- **Method**: `GET`
- **URL**: `/api/v1/audit-logs/{id}`
- **Akses**: `Admin`, `Superadmin`

### 2.3 Meta Actions
Mendapatkan list action yang tersedia untuk filter.
- **Method**: `GET`
- **URL**: `/api/v1/audit-logs/meta/actions`

---

## 3. Tags (Tagging System)

### 3.1 List Tags
Mendapatkan daftar tags.

- **Method**: `GET`
- **URL**: `/api/v1/tags`

**Query Parameters:**
| Parameter | Tipe | Wajib | Keterangan |
|-----------|------|-------|------------|
| `per_page` | integer | Tidak | Pagination |
| `search` | string | Tidak | Pencarian nama tag |
| `filter[name]` | string | Tidak | Filter nama (partial) |
| `filter[slug]` | string | Tidak | Filter slug (partial) |
| `sort` | string | Tidak | `name`, `-name`, `created_at` |

**Response Example:**
```json
{
    "data": [
        { "id": 1, "name": "PHP", "slug": "php" },
        { "id": 2, "name": "Laravel", "slug": "laravel" }
    ],
    "links": { ... },
    "meta": { ... }
}
```

### 3.2 Create Tags
[... existing tag docs ...]

---

## 4. Master Data (Data Master)

Sistem untuk mengelola data referensi (seperti Kategori, Tipe, Status) yang dinamis namun terpusat.

### 4.1 List Master Data Types
Mendapatkan daftar tipe master data yang tersedia (cth: `categories`, `tags`, `job_titles`).

- **Method**: `GET`
- **URL**: `/api/v1/master-data/types`
- **Akses**: `Authenticated User`

**Query Parameters:**
| Parameter | Tipe | Keterangan | Valid Values |
|-----------|------|------------|--------------|
| `page` | integer | Halaman | `1` |
| `per_page` | integer | Pagination (Default 15) | `10`, `50` |
| `search` | string | Pencarian nama tipe atau label | `cat` |
| `filter[is_crud]` | boolean | Filter tipe yang bisa diedit (CRUD) | `true`, `false` |
| `sort` | string | Sorting | `type`, `label`, `count`, `last_updated`, `-last_updated` |

**Response Example:**
```json
{
    "data": [
        {
            "key": "categories",
            "label": "Kategori",
            "count": 12,
            "last_updated": "2024-01-01 10:00:00",
            "is_crud": true
        }
    ],
    "meta": { ... }
}
```

### 4.2 List Master Data Items
Mendapatkan item-item dari tipe spesifik.

- **Method**: `GET`
- **URL**: `/api/v1/master-data/{type}`
- **Akses**: `Authenticated User`

**Query Parameters:**
| Parameter | Tipe | Keterangan |
|-----------|------|------------|
| `page` | integer | Halaman |
| `per_page` | integer | Pagination |
| `search` | string | Pencarian Full-Text (Meilisearch) |
| `filter[is_active]` | boolean | Filter status aktif |
| `filter[is_system]` | boolean | Filter default system |
| `filter[value]` | string | Filter exact value |
| `filter[label]` | string | Filter partial label |
| `sort` | string | `value`, `label`, `sort_order`, `created_at`, `updated_at` |

### 4.3 Create Master Data Item
- **Method**: `POST`
- **URL**: `/api/v1/master-data/{type}`
- **Akses**: `Superadmin`

**Request Body:**
```json
{
    "value": "vuejs",          // Required: String, Max 255
    "label": "Vue.js",         // Required: String, Max 255
    "is_active": true,         // Optional: Boolean
    "sort_order": 1,           // Optional: Integer
    "metadata": {              // Optional: Array/JSON
        "color": "green"
    }
}
```

### 4.4 Update Master Data Item
- **Method**: `PUT`
- **URL**: `/api/v1/master-data/{type}/{id}`
- **Akses**: `Superadmin`

**Request Body:**
Field bersifat optional (partial update).
```json
{
    "value": "vuejs-updated",
    "label": "Vue.js Updated",
    "is_active": false
}
```

### 4.5 Delete Master Data Item
- **Method**: `DELETE`
- **URL**: `/api/v1/master-data/{type}/{id}`
```json
[
    { "name": "React" },
    { "name": "Angular" },
    { "name": "Svelte" }
]
```

### 3.3 Update & Delete Tag
- **Update**: `PUT /api/v1/tags/{slug}`
  - Body: `{ "name": "New Name" }`
  - Akses: `Superadmin`, `Admin`, `Instructor`
- **Delete**: `DELETE /api/v1/tags/{slug}`
  - Akses: `Superadmin`, `Admin`, `Instructor`

---

## 4. Master Data (Data Referensi)

### 4.1 List Types
Mendapatkan daftar *tipe* master data yang tersedia di sistem.

- **Method**: `GET`
- **URL**: `/api/v1/master-data/types`

### 4.2 List Items by Type
Mendapatkan item-item dari tipe tertentu.

- **Method**: `GET`
- **URL**: `/api/v1/master-data/types/{type}/items`
- **Contoh URL**: `/api/v1/master-data/types/difficulty-levels/items`

**Available Types (Example):**
- `difficulty-levels` (Beginner, Intermediate, Advanced)
- `categories` (IT, Business, Design, etc)
- `content-types` (Article, Video, Quiz)

### 4.3 Manage Master Data Items (CRUD)
Manajemen item master data. **Hanya Superadmin**.

- **Create**: `POST /api/v1/master-data/types/{type}/items`
- **Update**: `PUT /api/v1/master-data/types/{type}/items/{id}`
- **Delete**: `DELETE /api/v1/master-data/types/{type}/items/{id}`

**Request Body (Create/Update):**
```json
{
    "value": "expert",           // Max: 100 char. Unique per Type.
    "label": "Expert",           // Max: 255 char. Label UI.
    "is_active": true,           // Boolean
    "sort_order": 4,             // Integer
    "metadata": {                // Optional JSON
        "color": "red",
        "description": "For professionals"
    }
}
```

---

## 5. Badges (Lencana)

### 5.1 List Badges
Mendapatkan daftar badges.

- **Method**: `GET`
- **URL**: `/api/v1/badges`
- **Akses**: `Authenticated` (Read-only)

**Query Parameters:**
| Parameter | Tipe | Keterangan | Valid Values |
|-----------|------|------------|--------------|
| `filter[code]` | string | Filter code | e.g., `first_step` |
| `filter[type]` | string | Filter type | `achievement`, `milestone`, `completion` |
| `sort` | string | Sort field | `name`, `created_at`, `threshold` |

### 5.2 Create Badge
Membuat badge baru dengan semua fields yang diperlukan.

- **Method**: `POST`
- **URL**: `/api/v1/badges`
- **Akses**: `Superadmin`

**Request Body (Multipart/Form-Data):**

#### Field Details & Explanations

| Field | Tipe | Wajib | Max | Keterangan |
|-------|------|-------|-----|------------|
| `code` | string | **Ya** | 50 | **Unique identifier untuk badge** (lowercase, snake_case format). Contoh: `first_submission`, `level_5_master`, `course_complete`. Digunakan untuk internal tracking. |
| `name` | string | **Ya** | 255 | **Nama badge yang ditampilkan kepada user**. Harus user-friendly dan deskriptif. Contoh: `First Submission`, `Level 5 Master`, `Course Graduate`. |
| `description` | string | Tidak | 1000 | **Penjelasan detail tentang badge**. Jelaskan cara mendapatkan badge dan apa yang diperlukan. Contoh: `Earned by submitting your first assignment`, `Reached level 5 with 5000 XP`. |
| `type` | string | **Ya** | - | **Kategori/Tipe badge**: `achievement` (aksi tertentu), `milestone` (tingkat/level), `completion` (menyelesaikan sesuatu). |
| `threshold` | integer | Tidak | - | **Nilai ambang batas untuk mendapat badge** (min: 1). Contoh: threshold 5 = user harus mencapai nilai 5. Biasanya untuk achievement atau milestone. |
| `icon` | file | **Ya** | 2MB | **WAJIB: Image/Icon badge**. Supported: JPEG, PNG, SVG, WebP. Recommended size: 256x256 px dengan transparent background. Akan disimpan dalam 3 versi: original, thumb (64x64), large (128x128). |
| `rules` | array | Tidak | - | **Array of rule objects untuk pemberian badge otomatis**. Diproses saat user mencapai kondisi tertentu. |
| `rules[0][criterion]` | string | Ya (jika rules ada) | 50 | **Kriteria yang dicek** (contoh: `xp_earned`, `lesson_completed`, `assignment_submitted`, `level_reached`). |
| `rules[0][operator]` | string | Ya (jika rules ada) | - | **Operator perbandingan**: `=` (sama dengan), `>=` (lebih besar/sama), `>` (lebih besar). |
| `rules[0][value]` | integer | Ya (jika rules ada) | - | **Nilai numerik untuk dibandingkan dengan criterion** (min: 1). |

#### Tipe Badge Penjelasan Lengkap

**1. Achievement** (`achievement`)
- Badge untuk pencapaian atau aksi spesifik
- Contoh: First Submission, 10 Lessons Completed, 100 Posts
- Biasanya punya threshold yang terukur
- Rules: `assignments_submitted >= 1`, `posts_made >= 10`

**2. Milestone** (`milestone`)
- Badge untuk mencapai level/tingkat tertentu
- Contoh: Level 5 Reached, 10,000 XP Earned, 30-Day Streak
- Biasanya milestone gamification
- Rules: `level_reached >= 5`, `xp_earned >= 10000`

**3. Completion** (`completion`)
- Badge untuk menyelesaikan sesuatu
- Contoh: Course Graduated, Unit Completed, Chapter Done
- Biasanya punya nilai kualitatif
- Rules: `course_completed = 1`, `all_lessons_done = 1`

#### Icon File Requirement

**PENTING**: Badge HARUS memiliki icon/image. File diperlukan sebagai form-data.

- **Format**: JPEG, PNG, SVG, WebP
- **Max Size**: 2 MB
- **Recommended Dimension**: 256x256 px atau lebih
- **Background**: Transparent recommended untuk fleksibilitas
- **Storage**: Disimpan di Spatie Media Library dengan 3 versi:
  - `original`: File asli yang di-upload
  - `thumb`: 64x64 px (untuk list view)
  - `large`: 128x128 px (untuk detail view)

#### Rules Object Structure

Rules adalah array of objects yang mendefinisikan kondisi otomatis pemberian badge:

```json
{
  "rules": [
    {
      "criterion": "xp_earned",
      "operator": ">=",
      "value": 5000
    },
    {
      "criterion": "level_reached",
      "operator": ">=",
      "value": 5
    }
  ]
}
```

- **Multiple rules**: Diproses dengan AND logic (semua harus terpenuhi)
- **Empty rules**: Badge tidak diberikan otomatis, hanya manual assign
- **Criteria Examples**: `xp_earned`, `lesson_completed`, `assignment_submitted`, `level_reached`, `streak_days`, `challenges_completed`

**Example Form Data:**
```bash
code: first_step
name: Langkah Awal
description: Menyelesaikan pelajaran pertama dengan sempurna
type: achievement
icon: (binary file - file.png)
threshold: 1
rules[0][criterion]: lesson_completed
rules[0][operator]: >=
rules[0][value]: 1
```

#### Complete Example (cURL)

```bash
curl -X POST http://localhost:8000/api/v1/badges \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "code=achievement_first_submission" \
  -F "name=First Submission" \
  -F "description=Earned by submitting your first assignment" \
  -F "type=achievement" \
  -F "threshold=1" \
  -F "icon=@/path/to/badge-icon.png" \
  -F "rules[0][criterion]=assignments_submitted" \
  -F "rules[0][operator]=>=" \
  -F "rules[0][value]=1"
```

### 5.3 Update & Delete Badge
- **Update**: `PUT /api/v1/badges/{badge}` (atau POST `_method=PUT`)
  - Akses: `Superadmin`
- **Delete**: `DELETE /api/v1/badges/{badge}`
  - Akses: `Superadmin`

---

## 6. Level Configs (Konfigurasi Level)

### 6.1 List & Manage
Konfigurasi kenaikan level berdasarkan XP. Ketika membuat/update level config dengan badge rewards, badge akan dibuat/disinkronkan otomatis.

- **List**: `GET /api/v1/level-configs`
- **Create**: `POST /api/v1/level-configs` (Superadmin)
- **Update**: `PUT /api/v1/level-configs/{id}` (Superadmin)
- **Delete**: `DELETE /api/v1/level-configs/{id}` (Superadmin)

**Request Body (Create/Update):**
```json
{
    "level": 10,
    "name": "Master",
    "xp_required": 5000,
    "rewards": [
        { "type": "badge", "value": "master_badge" },
        { "type": "points", "value": 100 }
    ]
}
```

#### Badge Synchronization Feature

Ketika Anda membuat atau update level config dengan badge rewards, sistem akan:

1. **Otomatis membuat badge** jika belum ada dengan code dari `value` field
   ```json
   {
     "type": "badge",
     "value": "level_10_master"
   }
   ```
   Akan membuat badge dengan code: `level_10_master`

2. **Badge yang dibuat otomatis akan memiliki:**
   - `code`: Dari rewards value (contoh: `level_10_master`)
   - `name`: Auto-generated dari code (contoh: `Level 10 Master`)
   - `description`: `Badge earned upon reaching level milestone`
   - `type`: `milestone` (auto-set)
   - `icon`: **NONE** (harus ditambahkan manual dengan PUT request)

3. **Jika badge sudah ada**, sistem hanya akan menggunakan badge yang ada tanpa membuat duplikat

#### PENTING: Icon Requirement

⚠️ **Badge yang dibuat otomatis dari level config TIDAK memiliki icon!**

Untuk memberikan icon pada badge, lakukan UPDATE dengan endpoint:
```
PUT /api/v1/badges/{badge_id}
Content-Type: multipart/form-data

icon: (file)
```

Contoh: Setelah membuat level config dengan badge `level_10_master`, update badge tersebut untuk menambahkan icon:
```bash
curl -X PUT http://localhost:8000/api/v1/badges/123 \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "icon=@/path/to/master-badge.png"
```

---

## 7. Challenge Management (Manajemen Tantangan)

### 7.1 List Challenges
Dashboard management challenges.

- **Method**: `GET`
- **URL**: `/api/v1/management/challenges`
- **Akses**: `Authenticated` (Read-only for Admin/Instructor), `Superadmin`.

**Query Parameters:**
| Parameter | Tipe | Keterangan |
|-----------|------|------------|
| `search` | string | Global search (Meilisearch) |
| `filter[type]` | string | `daily`, `weekly`, `one_time`, `special` |
| `filter[status]` | string | `active`, `inactive` |

### 7.2 Create Challenge
Membuat challenge baru.

- **Method**: `POST`
- **URL**: `/api/v1/management/challenges`
- **Akses**: `Superadmin`

**Request Body:**
```json
{
    "title": "Weekend Warrior",
    "description": "Complete 5 lessons this weekend",
    "type": "special",          // daily, weekly, one_time, special
    "points_reward": 500,
    "target_count": 5,          // Jumlah repetisi syarat
    "criteria": {               // Flexible JSON criteria
        "type": "lessons_completed",
        "category_id": 1
    },
    "start_at": "2024-02-01",
    "end_at": "2024-02-03",
    "badge_id": 10              // Optional badge reward ID
}
```

### 7.3 Update & Delete
- **Update**: `PUT /api/v1/management/challenges/{id}` (Superadmin)
- **Delete**: `DELETE /api/v1/management/challenges/{id}` (Superadmin)
