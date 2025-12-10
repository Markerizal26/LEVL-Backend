# TA PREP LSP - Backend API

Platform LMS (Learning Management System) untuk persiapan LSP (Lembaga Sertifikasi Profesi) dengan fitur gamifikasi.

## Tech Stack

- **Framework:** Laravel 12.x dengan modular architecture (nwidart/laravel-modules)
- **Database:** PostgreSQL
- **Authentication:** JWT (tymon/jwt-auth)
- **API Docs:** Scramble + Scalar
- **Storage:** Spatie Media Library

## Quick Start

```bash
# Clone dan setup
git clone <repository-url>
cd ta-prep-lsp-be
cp .env.example .env
composer install

# Generate keys
php artisan key:generate
php artisan jwt:secret

# Database
php artisan migrate --seed

# Jalankan server
php artisan serve
```

## API Documentation

| Resource             | URL                                        |
| -------------------- | ------------------------------------------ |
| Scalar (Interactive) | `/scalar`                                  |
| OpenAPI Spec         | `/api-docs/openapi.json`                   |
| Postman Collection   | `storage/api-docs/postman-collection.json` |

Generate ulang dokumentasi:

```bash
php artisan openapi:generate
```

## Modules

| Module        | Deskripsi                                 |
| ------------- | ----------------------------------------- |
| Auth          | Autentikasi, profil, password reset       |
| Schemes       | Kursus, unit kompetensi, lesson, progress |
| Enrollments   | Pendaftaran dan enrollment kursus         |
| Learning      | Assignment, submission, materi            |
| Gamification  | XP, badges, challenges, leaderboard       |
| Forums        | Diskusi per-scheme                        |
| Content       | Berita, pengumuman                        |
| Notifications | Push notification, preferences            |
| Grading       | Penilaian tugas                           |
| Search        | Pencarian global                          |
| Operations    | Admin operations                          |
| Common        | Master data, kategori                     |

## Struktur Proyek

```
├── Modules/           # Modular features
│   ├── Auth/
│   ├── Schemes/
│   └── ...
├── app/               # Core Laravel
├── config/            # Konfigurasi
├── database/          # Migrations & seeders
├── docs/             # Dokumentasi
└── storage/api-docs/ # Generated API docs
```

## Development

```bash
# Jalankan tests
php artisan test

# Validasi API docs coverage
php scripts/validate-api-docs.php

# Code formatting
composer format
```

## Architecture

Lihat [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md) untuk detail arsitektur:

- Repository Pattern
- Service Layer
- DTO dengan Spatie Laravel Data
- ApiResponse Trait

## License

MIT
