# Dokumentasi API Modul Pembelajaran & Penilaian

**Base URL**: `/api/v1`

---

## 🔐 Matriks Otorisasi

| Peran       | Lihat | Buat/Edit | Hapus | Publikasi | Kirim | Nilai | Banding |
|------------|------|-------------|--------|---------|--------|-------|---------|
| **Siswa**| ✅   | ❌          | ❌     | ❌      | ✅     | ❌    | ✅      |
| **Instruktur**| ✅| ✅ (Milik Sendiri)    | ✅ (Milik Sendiri)| ✅ (Milik Sendiri)| ❌     | ✅ (Milik Sendiri)| ✅ (Review) |
| **Admin**  | ✅   | ✅          | ✅     | ✅      | ❌     | ✅    | ✅      |

---

## 📝 Tugas (Assignments)

### 1. Daftar Tugas (Berdasarkan Kursus)
Mengambil daftar tugas yang dipaginasi untuk kursus tertentu.

**Endpoint:** `GET /courses/{course_slug}/assignments`

**Parameter Query:**

| Parameter | Tipe | Deskripsi | Nilai yang Tersedia |
|-----------|------|-------------|-----------------|
| `filter[status]` | string | Filter berdasarkan status | `draft` (Draf)<br>`published` (Dipublikasi)<br>`archived` (Diarsipkan) |
| `filter[submission_type]` | string | Filter berdasarkan tipe pengumpulan | `text` (Teks saja)<br>`file` (File saja)<br>`mixed` (Teks & File) |
| `sort` | string | Urutkan hasil | `-created_at` (Terbaru)<br>`title` (Judul A-Z)<br>`deadline_at` (Tenggat waktu) |
| `include` | csv | Sertakan relasi | `questions` (Soal-soal)<br>`lesson` (Pelajaran)<br>`creator` (Pembuat) |
| `page` | int | Nomor halaman | Default: 1 |
| `per_page` | int | Item per halaman | Default: 15 |

---

### 2. Daftar Tugas Belum Dikerjakan (Berdasarkan Kursus)
Mengambil daftar tugas yang belum dikerjakan/diserahkan oleh siswa untuk kursus tertentu.

**Endpoint:** `GET /courses/{course_slug}/assignments/incomplete`

> [!INFO]
> **HANYA UNTUK SISWA:** Endpoint ini menampilkan assignment yang:
> - Status: **Published** (Dipublikasi)
> - Belum ada submission dengan status `submitted` atau `graded` dari siswa yang sedang login

---

### 3. Buat Tugas

**Endpoint:** `POST /assignments`

**Body (JSON):**

| Field | Tipe | Wajib | Deskripsi | Nilai yang Tersedia |
|-------|------|----------|-------------|---------------------|
| `title` | string | Ya | Judul tugas | - |
| `description` | string | Tidak | Instruksi/deskripsi tugas | - |
| `assignable_type` | string | Ya | Jenis cakupan tugas | `Course` (Kursus)<br>`Unit` (Unit)<br>`Lesson` (Pelajaran) |
| `assignable_slug` | string | Ya | Slug dari cakupan (course/unit/lesson) | - |
| `submission_type` | enum | Ya | Tipe pengumpulan | `text` (Siswa kirim teks)<br>`file` (Siswa upload file)<br>`mixed` (Teks & file) |
| `max_score` | int | Tidak | Skor maksimal | Default: 100 |
| `available_from` | datetime | Tidak | Waktu mulai tersedia | Format: ISO 8601 |
| `deadline_at` | datetime | Tidak | Batas waktu pengumpulan | Format: ISO 8601 |
| `tolerance_minutes` | int | Tidak | Toleransi keterlambatan (menit) | - |
| `time_limit_minutes` | int | Tidak | Batas waktu pengerjaan (menit) | - |
| `late_penalty_percent` | int | Tidak | Penalti terlambat (persen) | 0-100 |
| `max_attempts` | int | Tidak | Maksimal percobaan | `null` = tidak terbatas |
| `cooldown_minutes` | int | Tidak | Jeda waktu antar percobaan (menit) | - |
| `retake_enabled` | bool | Tidak | Izinkan mengulang | `true`/`false` |
| `review_mode` | enum | Tidak | Mode peninjauan hasil | `immediate`, `deferred`, `hidden` |
| `randomization_type` | enum | Tidak | Tipe pengacakan soal | `static`, `random_order`, `bank` |
| `question_bank_count` | int | **Wajib jika `bank`** | Jumlah soal dari bank | - |
| `status` | enum | Tidak | Status publikasi | `draft`, `published` |
| `attachments` | array | Tidak | File lampiran | Maksimal 5 file |

---

### 4. Dapatkan Detail Tugas

**Endpoint:** `GET /assignments/{id}`

---

### 5. Perbarui Tugas

**Endpoint:** `PUT /assignments/{id}`

---

### 6. Duplikasi Tugas

**Endpoint:** `POST /assignments/{id}/duplicate`

---

### 7. Publikasi / Batalkan Publikasi

**Endpoint:** `PUT /assignments/{id}/publish`
**Endpoint:** `PUT /assignments/{id}/unpublish`

---

### 8. Arsipkan Tugas

**Endpoint:** `PUT /assignments/{id}/archived`

---

### 9. Hapus Tugas

**Endpoint:** `DELETE /assignments/{id}`

---

## ❓ Manajemen Soal (Questions)

### 1. Daftar Soal (Admin/Instruktur)

### 1. Daftar Soal (Admin/Instruktur)
 
 **Endpoint:** `GET /assignments/{id}/questions`
 
 **Parameter Query:**
 
 | Parameter | Tipe | Deskripsi |
 |-----------|------|-------------|
 | `filter[type]` | string | Filter berdasarkan tipe soal |
 | `sort` | string | Urutkan hasil (`order`, `weight`, `created_at`) |
 | `filter[search]` | string | **Pencarian Full-Text (Meilisearch)** |
 | `page` | int | Default: 1 |
 | `per_page` | int | Default: 15 |

---

### 2. Tambah Soal

**Endpoint:** `POST /assignments/{id}/questions`

**Body (JSON):**

| Field | Tipe | Wajib | Deskripsi |
|-------|------|-------|-------------|
| `type` | enum | Ya |  `multiple_choice`, `checkbox`, `essay`, `file_upload` |
| `content` | string | Ya | Teks soal |
| `options` | array | **Kondisional** | Opsi jawaban (untuk MC/Checkbox) |
| `answer_key` | array | **Otomatis** | Kunci jawaban |
| `weight` | float | Ya | Bobot poin |

---

### 3. Perbarui Soal

**Endpoint:** `PUT /assignments/{id}/questions/{question_id}`

---

### 4. Hapus Soal

**Endpoint:** `DELETE /assignments/{id}/questions/{question_id}`

---

### 5. Urutkan Ulang Soal

**Endpoint:** `POST /assignments/{id}/questions/reorder`

---

## ⚡ Override Akses (Admin/Instruktur)

### 1. Daftar Override

**Endpoint:** `GET /assignments/{id}/overrides`

### 2. Berikan Override

**Endpoint:** `POST /assignments/{id}/overrides`

---

## 📤 Pengumpulan (Submissions - Untuk Siswa)

### 1. Mulai Pengerjaan (Start Attempt)

**Endpoint:** `POST /assignments/{id}/submissions/start`

Memulai sesi pengerjaan tugas. Wajib dipanggil sebelum mengirim jawaban untuk tugas dengan timer atau batasan attempt.

**Respons:**
```json
{
  "data": {
    "id": 123,
    "attempt_number": 1,
    "status": "draft",
    "started_at": "2026-01-25 10:00:00",
    "deadline_at": "2026-01-25 11:00:00" // Jika ada time limit
  }
}
```

### 2. Simpan Jawaban (Per Soal)

**Endpoint:** `POST /submissions/{submission_id}/answers`

Menyimpan jawaban untuk satu soal tertetu. Disarankan dipanggil setiap kali siswa menjawab soal (autosave).

**Body (JSON):**
```json
{
  "question_id": 101,
  "answer": "Jawaban siswa" // String, Array ID opsi, atau File
}
```

### 3. Kirim Jawaban (Submit Akhir)

**Endpoint:** `POST /submissions/{submission_id}/submit`

Menandai tugas sebagai selesai dan siap dinilai.

---

### 4. Lihat Riwayat Pengumpulan Saya

**Endpoint:** `GET /assignments/{id}/submissions/me`

Melihat semua percobaan pengumpulan untuk tugas tertentu.

### 5. Lihat Detail Pengumpulan

**Endpoint:** `GET /assignments/{assignment_id}/submissions/{submission_id}`

Melihat detail jawaban, nilai (jika sudah ada), dan feedback.

### 6. Lihat Daftar Soal Pengumpulan (Siswa)

**Endpoint:** `GET /submissions/{submission_id}/questions`

Mengambil daftar soal untuk sesi ujian yang sedang berlangsung. Ini adalah endpoint yang digunakan siswa saat mengerjakan ujian.

---

## 🎓 Penilaian (Grading - Modul Grading)

### 1. Antrean Penilaian (Grading Queue)

**Endpoint:** `GET /api/v1/grading`

Melihat daftar submission. Secara default menampilkan submission yang menunggu penilaian manual (essay/file upload), namun dapat difilter untuk melihat semua submission berdasarkan state.

**Parameter Query:**

| Parameter | Tipe | Deskripsi | Nilai yang Tersedia |
|-----------|------|-------------|---------------------|
| `filter[assignment_id]` | int | Filter berdasarkan ID tugas | - |
| `filter[user_id]` | int | Filter berdasarkan ID siswa | - |
| `filter[state]` | string | Filter berdasarkan state submission | `in_progress` (Sedang dikerjakan)<br>`submitted` (Sudah dikumpulkan)<br>`auto_graded` (Dinilai otomatis)<br>`pending_manual_grading` (Menunggu penilaian manual)<br>`graded` (Sudah dinilai)<br>`released` (Nilai dirilis) |
| `filter[date_from]` | date | Filter tanggal mulai (YYYY-MM-DD) | - |
| `filter[date_to]` | date | Filter tanggal akhir (YYYY-MM-DD) | - |
| `sort` | string | Urutkan hasil | `submitted_at` (Terlama)<br>`-submitted_at` (Terbaru)<br>`created_at` (Terlama)<br>`-created_at` (Terbaru) |
| `page` | int | Nomor halaman | Default: 1 |
| `per_page` | int | Item per halaman | Default: 15 (Max: 100) |

> [!INFO]
> **Default Behavior:** Jika tidak ada `filter[state]` yang diberikan, endpoint akan otomatis menampilkan hanya submission dengan state `pending_manual_grading`. Untuk melihat semua submission, berikan `filter[state]` dengan nilai yang diinginkan.

### 2. Nilai Manual

**Endpoint:** `POST /api/v1/submissions/{submission_id}/grades`

Memberikan nilai manual untuk submission (seluruhnya atau per soal).

**Body (JSON):**

| Field | Tipe | Wajib | Deskripsi |
|-------|------|-------|-------------|
| `grades` | array | Ya | Array obyek nilai per soal |
| `grades[].question_id` | integer | Ya | ID soal yang dinilai |
| `grades[].score` | numeric | Ya | Skor untuk soal tersebut |
| `grades[].feedback` | string | Tidak | Feedback spesifik untuk soal |
| `feedback` | string | Tidak | Feedback umum untuk keseluruhan submission |

**Contoh Request:**

```json
{
  "grades": [
    {
      "question_id": 101,
      "score": 10,
      "feedback": "Jawaban sangat tepat."
    },
    {
      "question_id": 102,
      "score": 5,
      "feedback": "Kurang lengkap."
    }
  ],
  "feedback": "Secara keseluruhan sudah baik."
}
```

**Validasi:**
- `grades` harus array (bukan object)
- Setiap item harus memiliki `question_id` yang valid
- `score` harus numeric dan >= 0
- Minimal 1 soal harus dinilai

### 3. Simpan Draf Nilai

**Endpoint:** `PUT /api/v1/submissions/{submission_id}/grades/draft`

Menyimpan nilai sementara tanpa mempublikasikan ke siswa.

**Path Parameters:**
| Parameter | Tipe | Deskripsi |
|-----------|------|-------------|
| `submission_id` | integer | ID submission yang dinilai |

**Request Body (JSON):**

| Field | Tipe | Wajib | Deskripsi |
|-------|------|-------|-------------|
| `grades` | array | Ya | Array obyek nilai per soal |
| `grades[].question_id` | integer | Ya | ID soal yang dinilai |
| `grades[].score` | numeric | Tidak | Skor untuk soal tersebut (bisa null untuk draf partial) |
| `grades[].feedback` | string | Tidak | Feedback spesifik untuk soal |

**Query Parameters:** Tidak ada

**Contoh Request:**

```json
{
  "grades": [
    {
      "question_id": 101,
      "score": 10,
      "feedback": "Draf awal"
    }
  ]
}
```

**Respons:**
```json
{
  "success": true,
  "message": "Draft nilai berhasil disimpan",
  "data": {
    "submission_id": 2
  }
}
```

### 4. Lihat Draf Nilai

**Endpoint:** `GET /api/v1/submissions/{submission_id}/grades/draft`

**Path Parameters:**
| Parameter | Tipe | Deskripsi |
|-----------|------|-------------|
| `submission_id` | integer | ID submission |

**Query Parameters:** Tidak ada

**Respons:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "submission_id": 2,
    "status": "draft",
    "grades": [
      {
        "question_id": 101,
        "score": 10,
        "feedback": "Draf awal"
      }
    ],
    "saved_at": "2026-01-25T19:34:56Z"
  }
}
```

### 5. Rilis Nilai

**Endpoint:** `PATCH /api/v1/submissions/{submission_id}/grades/release`

Mempublikasikan nilai (dari status Graded ke Released) agar dapat dilihat siswa, jika mode review adalah `deferred`.

### 6. Override Nilai

**Endpoint:** `PATCH /api/v1/submissions/{submission_id}/grades`

Mengubah nilai akhir secara paksa dengan alasan.

**Body (JSON):**
```json
{
  "score": 90,
  "reason": "Revisi manual setelah banding"
}
```

### 7. Kembalikan ke Antrean

**Endpoint:** `PATCH /api/v1/submissions/{submission_id}/grades/return-to-queue`

Mengembalikan submission yang sudah dinilai/dirilis kembali ke status `pending_manual_grading` (antrean penilaian). Berguna jika nilai ingin dianulir dan dinilai ulang.

### 8. Lihat Status Penilaian

**Endpoint:** `GET /api/v1/submissions/{submission_id}/grades/status`

Melihat status kelengkapan penilaian manual.

**Respons:**
```json
{
  "submission_id": 123,
  "is_complete": false,
  "graded_questions": 5,
  "total_questions": 10,
  "can_finalize": false,
  "can_release": false
}
```


### 9. Aksi Massal (Bulk Action)

**Endpoint (Rilis Nilai):** `POST /api/v1/grading/bulk-release`

**Body (JSON):**

```json
{
  "submission_ids": [1, 2, 3, 4],
  "async": false
}
```

**Endpoint (Beri Feedback):** `POST /api/v1/grading/bulk-feedback`

**Body (JSON):**

```json
{
  "submission_ids": [1, 2, 3, 4],
  "feedback": "Kerja bagus semua!",
  "async": true
}
```



---
