# Setup Scalar API Documentation

## ✅ Yang Sudah Dibuat

1. **OpenApiGeneratorService** (`app/Services/OpenApiGeneratorService.php`)
   - Service untuk generate OpenAPI spec dari routes Laravel
   - Organisasi per fitur bisnis dengan struktur folder → subfolder
   - Auto-detect dari controller dan routes

2. **GenerateOpenApiSpec Command** (`app/Console/Commands/GenerateOpenApiSpec.php`)
   - Command artisan untuk generate spec: `php artisan openapi:generate`
   - Output default: `storage/api-docs/openapi.json`

3. **OpenApiController** (`app/Http/Controllers/OpenApiController.php`)
   - Controller untuk serve OpenAPI spec via HTTP
   - Route: `/api-docs/openapi.json`

4. **Route Configuration**
   - Route untuk OpenAPI spec sudah ditambahkan di `routes/web.php`
   - Scalar config sudah diupdate untuk menggunakan generated spec

5. **Documentation Examples**
   - Contoh dokumentasi sudah ditambahkan di `SubmissionController`
   - Format: `@summary` dan `@description` di docblock

## 🚀 Cara Menggunakan

### 1. Generate OpenAPI Spec

```bash
php artisan openapi:generate
```

Spec akan tersimpan di `storage/api-docs/openapi.json`

### 2. Akses Dokumentasi Scalar

Setelah generate, akses dokumentasi melalui:

**URL Dokumentasi:** `http://your-domain.com/scalar`

**URL OpenAPI Spec:** `http://your-domain.com/api-docs/openapi.json`

### 3. Menambahkan Deskripsi ke Endpoint

Tambahkan docblock singkat di controller method:

```php
/**
 * @summary Judul singkat endpoint
 * @description Endpoint ini digunakan untuk ...
 * 
 * @queryParam filter[status] string Filter berdasarkan status. Nilai: draft, pending, published. Example: published
 * @queryParam filter[course_id] integer Filter berdasarkan ID kursus. Example: 1
 * @queryParam sort string Field untuk sorting. Prefix - untuk descending. Example: -created_at
 * @queryParam page integer Nomor halaman. Default: 1. Example: 1
 * @queryParam per_page integer Jumlah item per halaman. Default: 15. Example: 15
 */
public function index(Request $request)
{
    // ...
}
```

### 4. Format Filter Query Parameters

Semua filter menggunakan format `filter[nama_filter]` dengan nilai yang valid:

| Filter | Parameter | Nilai Valid | Contoh |
|--------|-----------|-------------|--------|
| Status Content | `filter[status]` | draft, pending, published, archived | `?filter[status]=published` |
| Status User | `filter[status]` | active, inactive, suspended | `?filter[status]=active` |
| Status Enrollment | `filter[status]` | pending, active, completed, cancelled | `?filter[status]=active` |
| Priority | `filter[priority]` | low, normal, high, urgent | `?filter[priority]=high` |
| Difficulty | `filter[difficulty_level]` | beginner, intermediate, advanced | `?filter[difficulty_level]=beginner` |
| Course ID | `filter[course_id]` | integer | `?filter[course_id]=1` |
| Category ID | `filter[category_id]` | integer | `?filter[category_id]=5` |
| Date Range | `filter[date_from]`, `filter[date_to]` | Y-m-d | `?filter[date_from]=2025-01-01` |
| Boolean | `filter[featured]`, `filter[unread]` | true, false | `?filter[featured]=true` |
| User ID | `filter[user_id]` | integer | `?filter[user_id]=5` |
| Type | `filter[type]` | varies by endpoint | `?filter[type]=daily` |

## 📁 Struktur Organisasi

Struktur Scalar mengikuti folder → subfolder yang diminta bisnis:

1. **Peserta**
   - Registrasi & Login
   - Pendaftaran Kelas/Skema
   - Pencarian Skema
   - Akses Materi Skema
   - Pengerjaan Tugas & Latihan Soal
   - Poin, Badges, Levels, Leaderboard
   - Akses & Edit Profil
   - Info & News
   - Notifikasi
   - Forum Skema
   - Pendaftaran Assessment

2. **Instruktur**
   - Manajemen Materi
   - Edit Profil Instruktur
   - Manajemen Bank Soal
   - Penilaian Tugas & Latihan

3. **Admin**
   - Login Admin
   - Manajemen Pengguna
   - Manajemen Pendaftaran Kelas & Assessment
   - Manajemen Skema
   - Manajemen Unit Kompetensi
   - Manajemen Materi Pembelajaran
   - Manajemen Bank Soal
   - Manajemen Tugas & Jawaban
   - Manajemen Poin & Badges
   - Manajemen Info & News
   - Penilaian Tugas & Latihan
   - Edit Profil Admin

4. **Umum**
   - Endpoint Lainnya

## ⚙️ Konfigurasi

### Scalar Config (`config/scalar.php`)

- **Path**: `/scalar` (default)
- **OpenAPI URL**: Auto dari route `/api-docs/openapi.json`
- **Theme**: `laravel` (dapat diubah)
- **Layout**: `modern`

### Customize Folder Organization

Edit `app/Services/OpenApiGeneratorService.php` pada properti `$featureGroups` untuk mengganti nama folder, deskripsi, daftar module terkait, atau keyword pencarian endpoint.

## 🔄 Auto-Generate

OpenAPI spec secara otomatis di-generate dari:

- ✅ Routes di setiap module (`Modules/*/routes/api.php`)
- ✅ Controller methods dan docblocks
- ✅ Form Request validation rules
- ✅ Route middleware (untuk security)

## 📝 Tips

1. Gunakan struktur folder untuk menemukan endpoint sesuai konteks bisnis.
2. Tambahkan `@summary` dan `@description` singkat setiap membuat method baru.
3. Jalankan `php artisan openapi:generate` setelah mengubah routes atau struktur dokumentasi.

## 🐛 Troubleshooting

### Spec tidak ter-generate

- Pastikan semua routes sudah ter-register
- Check error di `storage/logs/laravel.log`

### Dokumentasi tidak muncul di Scalar

- Pastikan route `/api-docs/openapi.json` accessible
- Check config `config/scalar.php` URL setting
- Clear cache: `php artisan config:clear`

### Endpoint tidak muncul di dokumentasi

- Pastikan controller ada di module yang terdaftar
- Check apakah route memiliki nama (name)
- Pastikan route menggunakan prefix `api/`
