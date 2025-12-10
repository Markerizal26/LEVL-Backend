# Content Module - Info & News Management System

## Overview

Content Module menyediakan sistem manajemen pengumuman (announcements) dan berita (news) untuk platform LMS. Module ini mendukung targeting audience, scheduling, read tracking, dan statistik untuk komunikasi yang efektif.

## Features

### ✨ Core Features
- **Announcements Management**: Create, read, update, delete announcements
- **News Management**: Full CRUD untuk artikel berita
- **Target Audience**: Support untuk all users, specific roles, specific courses
- **Scheduling**: Auto-publish konten pada waktu yang ditentukan
- **Read Tracking**: Track siapa yang sudah membaca konten
- **Statistics**: View counts, read rates, trending news
- **Search & Filtering**: Full-text search dengan multiple filters
- **Revision History**: Track semua perubahan konten
- **Course-Specific**: Announcements khusus untuk kursus tertentu

### 🔐 Authorization
- **Admin**: Full access ke semua fitur
- **Instructor**: Dapat membuat news dan course announcements
- **Student**: Read-only access

## Installation

Module ini sudah ter-install sebagai bagian dari Laravel Modules.

### Run Migrations

```bash
php artisan migrate
```

### Seed Sample Data

```bash
php artisan module:seed Content
```

## API Documentation

### 📚 Scalar Documentation

Akses dokumentasi interaktif via Scalar:

```
http://your-domain.com/scalar
```

### 📄 OpenAPI Spec

OpenAPI specification di-generate otomatis oleh Scramble:

```
/docs/api          # Scramble UI
/docs/api.json     # OpenAPI JSON spec
```

> **Note:** File `openapi.yaml` di module ini sudah deprecated. Gunakan Scramble untuk dokumentasi terbaru.

### 🔗 API Endpoints

**Base URL:** `/api/v1`

#### Announcements (8 endpoints)
- `GET /announcements` - List announcements
- `POST /announcements` - Create announcement
- `GET /announcements/{id}` - Get detail
- `PUT /announcements/{id}` - Update
- `DELETE /announcements/{id}` - Delete
- `POST /announcements/{id}/publish` - Publish
- `POST /announcements/{id}/schedule` - Schedule
- `POST /announcements/{id}/read` - Mark as read

#### News (8 endpoints)
- `GET /news` - List news
- `POST /news` - Create news
- `GET /news/trending` - Get trending
- `GET /news/{slug}` - Get detail
- `PUT /news/{slug}` - Update
- `DELETE /news/{slug}` - Delete
- `POST /news/{slug}/publish` - Publish
- `POST /news/{slug}/schedule` - Schedule

#### Course Announcements (2 endpoints)
- `GET /courses/{course}/announcements` - List
- `POST /courses/{course}/announcements` - Create

#### Statistics (5 endpoints)
- `GET /content/statistics` - Overall stats
- `GET /content/statistics/announcements/{id}` - Announcement stats
- `GET /content/statistics/news/{slug}` - News stats
- `GET /content/statistics/trending` - Trending news
- `GET /content/statistics/most-viewed` - Most viewed

#### Search (1 endpoint)
- `GET /content/search` - Search content

## Usage Examples

### Create Announcement

```bash
curl -X POST http://localhost:8000/api/v1/announcements \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Important Announcement",
    "content": "This is an important announcement...",
    "target_type": "all",
    "priority": "high",
    "status": "published"
  }'
```

### Create News

```bash
curl -X POST http://localhost:8000/api/v1/news \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Latest News",
    "content": "Full news content...",
    "excerpt": "Brief summary...",
    "category_ids": [1, 2],
    "is_featured": true,
    "status": "published"
  }'
```

### Schedule Content

```bash
curl -X POST http://localhost:8000/api/v1/announcements/1/schedule \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "scheduled_at": "2025-12-10T10:00:00Z"
  }'
```

### Search Content

```bash
curl -X GET "http://localhost:8000/api/v1/content/search?q=important&type=all" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

## Testing

### Run All Tests

```bash
php artisan test --filter=Content
```

### Run Unit Tests Only

```bash
php artisan test Modules/Content/tests/Unit
```

### Run Feature Tests Only

```bash
php artisan test Modules/Content/tests/Feature
```

### Test Coverage

- **Total Tests:** 58
- **Unit Tests:** 37 (Models, relationships, scopes)
- **Feature Tests:** 21 (API endpoints, validation, authorization)
- **All Passing:** ✅

## Scheduled Jobs

### Auto-Publish Scheduled Content

Job ini berjalan setiap 5 menit untuk mempublikasikan konten yang sudah terjadwal.

```bash
# Manual run
php artisan schedule:run

# Check scheduled jobs
php artisan schedule:list
```

Job terdaftar di `routes/console.php`:

```php
Schedule::job(new \Modules\Content\Jobs\PublishScheduledContent)
    ->everyFiveMinutes();
```

## Database Schema

### Tables

1. **announcements** - Pengumuman
2. **news** - Berita
3. **content_reads** - Read tracking (polymorphic)
4. **content_revisions** - Revision history
5. **content_categories** - Kategori konten
6. **news_category** - Pivot table

### Indexes

- Full-text search pada `title` dan `content`
- Index pada `status`, `published_at`, `course_id`
- Unique constraint pada `user_id + readable_type + readable_id`

## Architecture

### Design Pattern

- **Repository Pattern**: Data access layer
- **Service Layer**: Business logic
- **Policy-based Authorization**: Laravel policies
- **Event-Driven**: Events & Listeners untuk notifications
- **Job Queue**: Scheduled publishing

### File Structure

```
Modules/Content/
├── app/
│   ├── Http/
│   │   ├── Controllers/     # API Controllers
│   │   └── Requests/        # Form Requests
│   ├── Models/              # Eloquent Models
│   ├── Repositories/        # Data Access
│   ├── Services/            # Business Logic
│   ├── Policies/            # Authorization
│   ├── Events/              # Domain Events
│   ├── Listeners/           # Event Handlers
│   └── Jobs/                # Background Jobs
├── database/
│   ├── migrations/          # Database Schema
│   ├── factories/           # Model Factories
│   └── seeders/             # Sample Data
├── tests/
│   ├── Unit/                # Unit Tests
│   └── Feature/             # Integration Tests
├── routes/
│   └── api.php              # API Routes
├── openapi.yaml             # OpenAPI Spec
└── README.md                # This file
```

## Integration

### With Other Modules

- **Auth Module**: User authentication & roles
- **Schemes Module**: Course association
- **Enrollments Module**: Target audience determination
- **Notifications Module**: Notification delivery (ready)

## Performance

### Optimizations

- ✅ Database indexes on frequently queried fields
- ✅ Eager loading to prevent N+1 queries
- ✅ Full-text search indexes
- ✅ Pagination on all list endpoints
- ✅ Soft deletes for data retention

### Caching (Future)

Recommended caching strategies:
- News feed: 5 minutes
- Statistics: 1 hour
- Trending news: 15 minutes

## Security

### Implemented

- ✅ JWT Authentication required
- ✅ Role-based authorization
- ✅ Policy-based access control
- ✅ Input validation via Form Requests
- ✅ SQL injection prevention (Eloquent ORM)
- ✅ Soft deletes with audit trail

### Recommendations

- Sanitize HTML content to prevent XSS
- Rate limiting on content creation
- Content moderation workflow

## Troubleshooting

### Tests Failing

```bash
# Clear cache
php artisan config:clear
php artisan cache:clear

# Refresh database
php artisan migrate:fresh --seed

# Run tests again
php artisan test --filter=Content
```

### Scheduled Job Not Running

```bash
# Check if scheduler is running
php artisan schedule:list

# Run manually
php artisan schedule:run

# Check logs
tail -f storage/logs/laravel.log
```

### API Returns 401

- Pastikan JWT token valid
- Check token expiration
- Verify Authorization header format: `Bearer {token}`

### API Returns 403

- Check user roles
- Verify policy permissions
- Ensure user has access to resource

## Contributing

### Adding New Features

1. Create migration if needed
2. Update models
3. Add repository methods
4. Implement service logic
5. Create controller endpoints
6. Add routes
7. Write tests
8. Update OpenAPI spec
9. Update documentation

### Code Style

Follow Laravel best practices:
- PSR-12 coding standard
- Type hints for parameters and return types
- Docblocks for public methods
- Meaningful variable names

## License

This module is part of the LMS platform.

## Support

For issues or questions:
- View interactive API docs: `/docs/api` atau `/scalar`
- Run tests: `php artisan test --filter=Content`

> **Note:** File `API_DOCUMENTATION.md` dan `openapi.yaml` sudah deprecated. Gunakan Scramble untuk dokumentasi terbaru.

---

**Version:** 1.0.0  
**Status:** Production Ready ✅  
**Tests:** 58 passing ✅  
**Documentation:** Complete ✅
