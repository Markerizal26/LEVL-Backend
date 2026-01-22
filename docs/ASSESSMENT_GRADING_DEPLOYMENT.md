# Assessment & Grading System - Deployment Guide

## Overview

This guide covers the deployment of the Assessment & Grading System for the Laravel LMS. The system provides comprehensive assignment management, submission handling, auto-grading, manual grading workflows, and audit logging.

---

## Prerequisites

- PHP 8.2+
- Laravel 11.x
- PostgreSQL 14+ (recommended) or MySQL 8+
- Redis 6+ (for caching and queues)
- Meilisearch (for submission search)
- Composer 2.x
- Node.js 18+ (for frontend assets)

---

## Installation Steps

### 1. Database Migrations

Run all migrations to create the required tables:

```bash
php artisan migrate
```

**Key tables created:**
- `assignments` - Assignment configuration and settings
- `questions` - Question definitions with answer keys
- `submissions` - Student submission records
- `answers` - Student answers for each question
- `grades` - Grading records and feedback
- `appeals` - Late submission appeals
- `overrides` - Instructor overrides for students
- `audit_logs` - Immutable audit trail

### 2. Queue Configuration

The system uses Laravel queues for background processing. Configure your queue driver in `.env`:

```env
QUEUE_CONNECTION=redis
```

**Required queue workers:**

```bash
# Start queue workers for grading operations
php artisan queue:work --queue=grading,notifications,default
```

**Supervisor configuration (recommended for production):**

```ini
[program:lms-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/artisan queue:work --queue=grading,notifications,default --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
numprocs=4
redirect_stderr=true
stdout_logfile=/path/to/logs/worker.log
```

### 3. Cache Configuration

Configure Redis for caching:

```env
CACHE_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```

The system caches:
- Assignment configurations (TTL: 1 hour)
- Question data (TTL: 1 hour)
- Student rosters (TTL: 30 minutes)

### 4. Search Configuration (Meilisearch)

Configure Meilisearch for submission search:

```env
SCOUT_DRIVER=meilisearch
MEILISEARCH_HOST=http://127.0.0.1:7700
MEILISEARCH_KEY=your-master-key
```

**Index submissions:**

```bash
php artisan scout:import "Modules\Learning\Models\Submission"
```

### 5. File Storage

Configure file storage for submission uploads:

```env
FILESYSTEM_DISK=local
```

For production, consider using S3 or similar:

```env
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=your-key
AWS_SECRET_ACCESS_KEY=your-secret
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your-bucket
```

---

## Environment Variables

Add these to your `.env` file:

```env
# Assessment & Grading Configuration
GRADING_DEFAULT_TOLERANCE_MINUTES=0
GRADING_DEFAULT_MAX_FILE_SIZE=10485760
GRADING_FILE_RETENTION_DAYS=365

# Queue Configuration
QUEUE_CONNECTION=redis

# Cache Configuration
CACHE_DRIVER=redis

# Search Configuration
SCOUT_DRIVER=meilisearch
MEILISEARCH_HOST=http://127.0.0.1:7700
```

---

## Security Considerations

### 1. Authorization

The system implements role-based access control:

| Role | Permissions |
|------|-------------|
| Student | Submit assignments, view own submissions, submit appeals |
| Instructor | Grade submissions, manage assignments, approve/deny appeals |
| Admin | Full access including audit logs |
| Superadmin | Full access including audit logs |

### 2. File Upload Security

- File types are validated against allowed types per question
- File sizes are validated against configured maximum
- Files are stored with randomized paths
- Access requires authentication and authorization

### 3. Audit Logging

All critical operations are logged to an immutable audit trail:
- Submission creation and state transitions
- Grading actions
- Answer key changes
- Grade overrides
- Appeal decisions
- Override grants

### 4. Input Validation

All API endpoints use Laravel Form Requests for validation:
- SQL injection prevention via Eloquent ORM
- XSS prevention via input sanitization
- CSRF protection on web routes

---

## Performance Optimization

### Database Indexes

The system creates indexes on frequently queried fields:

```sql
-- Submissions
CREATE INDEX idx_submissions_student_assignment ON submissions(student_id, assignment_id);
CREATE INDEX idx_submissions_state ON submissions(state);
CREATE INDEX idx_submissions_submitted_at ON submissions(submitted_at);

-- Answers
CREATE INDEX idx_answers_submission ON answers(submission_id);
CREATE INDEX idx_answers_question ON answers(question_id);

-- Grades
CREATE INDEX idx_grades_submission ON grades(submission_id);

-- Audit Logs
CREATE INDEX idx_audit_logs_action ON audit_logs(action);
CREATE INDEX idx_audit_logs_created_at ON audit_logs(created_at);
```

### Eager Loading

The system uses eager loading to prevent N+1 queries. Ensure relationships are loaded when needed.

### Background Jobs

Heavy operations are processed in background:
- Grade recalculation after answer key changes
- Bulk grade release
- Bulk feedback application
- Notification sending

---

## Scheduled Tasks

Add to your scheduler (`app/Console/Kernel.php`):

```php
// Clean up expired files
$schedule->command('files:cleanup-expired')->daily();

// Reindex search
$schedule->command('scout:import "Modules\Learning\Models\Submission"')->weekly();
```

---

## Monitoring

### Health Checks

Monitor these components:
- Queue depth and job failures
- Redis connectivity
- Meilisearch availability
- Database connection pool

### Logging

The system logs to Laravel's default log channels:
- `INFO` - Normal operations
- `WARNING` - Authorization failures, invalid state transitions
- `ERROR` - System errors, exceptions

### Metrics to Track

- Auto-grading completion time (target: < 2 seconds for 50 questions)
- Grading queue load time (target: < 1 second for 1000 submissions)
- Course grade calculation time (target: < 5 seconds for 500 students)

---

## Troubleshooting

### Common Issues

**1. Queue jobs not processing**
```bash
# Check queue status
php artisan queue:monitor grading,notifications,default

# Restart workers
php artisan queue:restart
```

**2. Search not returning results**
```bash
# Reindex submissions
php artisan scout:flush "Modules\Learning\Models\Submission"
php artisan scout:import "Modules\Learning\Models\Submission"
```

**3. Cache issues**
```bash
# Clear cache
php artisan cache:clear
php artisan config:clear
```

**4. File upload failures**
- Check storage permissions
- Verify file size limits in PHP and nginx/Apache
- Check disk space

---

## Rollback Procedure

If issues occur after deployment:

```bash
# Rollback migrations
php artisan migrate:rollback --step=X

# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# Restart queue workers
php artisan queue:restart
```

---

## Support

For issues or questions:
1. Check the API documentation: `Modules/Grading/API_DOCUMENTATION.md`
2. Review audit logs for operation history
3. Check Laravel logs in `storage/logs/`
