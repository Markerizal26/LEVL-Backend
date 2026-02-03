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
            "ip_address": "127.0.0.1",
            "browser": "Chrome",
            "location": {
                "city": "Jakarta",
                "region": "DKI Jakarta",
                "country": "Indonesia"
            }
        }
    ]
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
| `properties` | Metadata tambahan terkait event dalam format JSON. | `{"ip": "127.0.0.1", "browser": "Chrome", "old": {...}, "attributes": {...}}` |

---

## 2. Audit Logs (Log Audit)

### 2.1 List Audit Logs
Mendapatkan daftar audit logs sistem untuk keperluan validasi dan compliance.

- **Method**: `GET`
- **URL**: `/api/v1/audit-logs`
- **Akses**: `Admin`, `Superadmin`

**Query Parameters:**
| Parameter | Tipe | Wajib | Keterangan | Valid Values / Contoh |
|-----------|------|-------|------------|-----------------------|
| `per_page` | integer | Tidak | Pagination | `15` |
| `page` | integer | Tidak | Halaman | `1` |
| `action` | string | Tidak | Filter single action | `submission_created` |
| `actions[]` | array | Tidak | Filter multiple actions | `['grading', 'grade_override']` |
| `actor_id` | integer | Tidak | ID User Aktor | `1` |
| `subject_id` | integer | Tidak | ID Subject | `10` |
| `start_date` | date | Tidak | Filter range start | `2024-01-01` |
| `end_date` | date | Tidak | Filter range end | `2024-12-31` |
| `context_search`| string | Tidak | Cari text dalam JSON context | `reason` |

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
Membuat tag baru. Mendukung pembuatan **Single** atau **Bulk (Array)**.

- **Method**: `POST`
- **URL**: `/api/v1/tags`
- **Akses**: `Superadmin`, `Admin`, `Instructor`

**Request Body (Single):**
```json
{
    "name": "VueJS"
}
```

**Request Body (Bulk):**
Berguna untuk membuat banyak tag sekaligus dari input UI (e.g. creatable select).
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
Membuat badge baru.

- **Method**: `POST`
- **URL**: `/api/v1/badges`
- **Akses**: `Superadmin`

**Request Body (Multipart/Form-Data):**
| Field | Tipe | Wajib | Keterangan |
|-------|------|-------|------------|
| `code` | string | Ya | Unique Code (a-z, 0-9, _). |
| `name` | string | Ya | Nama Badge Display. |
| `description` | string | Tidak | Deskripsi singkat. |
| `type` | string | Ya | `achievement`, `milestone`, `completion`. |
| `threshold` | integer | Tidak | Nilai target (misal: 100 XP). |
| `icon` | file | Tidak | Image file (jpg, png, svg, webp) max 2MB. |
| `rules` | array | Tidak | Array of objects rule otomatisasi. |
| `rules[0][criterion]`| string | Ya | `lesson_completed`, `xp_earned`, `streak_days` |
| `rules[0][operator]` | string | Ya | `=`, `>=`, `>` |
| `rules[0][value]` | integer | Ya | Value target rule. |

### 5.3 Update & Delete Badge
- **Update**: `PUT /api/v1/badges/{badge}` (atau POST `_method=PUT`)
  - Akses: `Superadmin`
- **Delete**: `DELETE /api/v1/badges/{badge}`
  - Akses: `Superadmin`

---

## 6. Level Configs (Konfigurasi Level)

### 6.1 List & Manage
Konfigurasi kenaikan level berdasarkan XP.

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
