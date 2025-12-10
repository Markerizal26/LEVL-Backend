# Forums Module - API Documentation

## Overview

The Forums module provides a complete discussion system for scheme-based learning. It supports threaded discussions, nested replies, reactions, moderation, and statistics tracking.

## Features

- ✅ Thread creation and management
- ✅ Nested replies (up to 5 levels deep)
- ✅ Reactions (like, helpful, solved)
- ✅ Moderation (pin, close threads, mark accepted answers)
- ✅ Full-text search
- ✅ Statistics tracking
- ✅ Real-time notifications
- ✅ Authorization based on enrollment

## API Endpoints

### Thread Endpoints

#### List Threads
```
GET /api/v1/schemes/{scheme_id}/forum/threads
```

Query Parameters:
- `pinned` (boolean): Filter pinned threads
- `resolved` (boolean): Filter resolved threads
- `closed` (boolean): Filter closed/open threads
- `per_page` (integer): Items per page (default: 20)

Response:
```json
{
  "success": true,
  "message": "Threads retrieved successfully",
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "scheme_id": 1,
        "author_id": 1,
        "title": "Thread Title",
        "content": "Thread content...",
        "is_pinned": false,
        "is_closed": false,
        "is_resolved": false,
        "views_count": 10,
        "replies_count": 5,
        "last_activity_at": "2025-12-02T10:00:00.000000Z",
        "created_at": "2025-12-01T10:00:00.000000Z",
        "author": {
          "id": 1,
          "name": "John Doe"
        }
      }
    ],
    "total": 50
  }
}
```

#### Create Thread
```
POST /api/v1/schemes/{scheme_id}/forum/threads
```

Request Body:
```json
{
  "title": "Thread Title",
  "content": "Thread content..."
}
```

#### Get Thread Detail
```
GET /api/v1/schemes/{scheme_id}/forum/threads/{thread_id}
```

#### Update Thread
```
PUT /api/v1/schemes/{scheme_id}/forum/threads/{thread_id}
```

Request Body:
```json
{
  "title": "Updated Title",
  "content": "Updated content..."
}
```

#### Delete Thread
```
DELETE /api/v1/schemes/{scheme_id}/forum/threads/{thread_id}
```

#### Search Threads
```
GET /api/v1/schemes/{scheme_id}/forum/threads/search?q=search+query
```

#### Pin Thread (Moderator Only)
```
POST /api/v1/schemes/{scheme_id}/forum/threads/{thread_id}/pin
```

#### Close Thread (Moderator Only)
```
POST /api/v1/schemes/{scheme_id}/forum/threads/{thread_id}/close
```

### Reply Endpoints

#### Create Reply
```
POST /api/v1/forum/threads/{thread_id}/replies
```

Request Body:
```json
{
  "content": "Reply content...",
  "parent_id": null  // Optional: ID of parent reply for nested replies
}
```

#### Update Reply
```
PUT /api/v1/forum/replies/{reply_id}
```

Request Body:
```json
{
  "content": "Updated reply content..."
}
```

#### Delete Reply
```
DELETE /api/v1/forum/replies/{reply_id}
```

#### Mark as Accepted Answer (Instructor Only)
```
POST /api/v1/forum/replies/{reply_id}/accept
```

### Reaction Endpoints

#### Toggle Reaction on Thread
```
POST /api/v1/forum/threads/{thread_id}/reactions
```

Request Body:
```json
{
  "type": "like"  // Options: like, helpful, solved
}
```

Response:
```json
{
  "success": true,
  "message": "Reaction added successfully",
  "data": {
    "added": true  // true if added, false if removed
  }
}
```

#### Toggle Reaction on Reply
```
POST /api/v1/forum/replies/{reply_id}/reactions
```

Request Body:
```json
{
  "type": "helpful"
}
```

### Statistics Endpoints

#### Get Scheme Statistics
```
GET /api/v1/schemes/{scheme_id}/forum/statistics
```

Query Parameters:
- `period_start` (date): Start date (default: start of current month)
- `period_end` (date): End date (default: end of current month)
- `user_id` (integer): Optional user ID for user-specific stats

Response:
```json
{
  "success": true,
  "message": "Statistics retrieved successfully",
  "data": {
    "id": 1,
    "scheme_id": 1,
    "user_id": null,
    "threads_count": 25,
    "replies_count": 150,
    "views_count": 500,
    "avg_response_time_minutes": 45,
    "response_rate": 85.50,
    "period_start": "2025-12-01",
    "period_end": "2025-12-31"
  }
}
```

#### Get Current User Statistics
```
GET /api/v1/schemes/{scheme_id}/forum/statistics/me
```

## API Documentation

Dokumentasi API interaktif tersedia di:
- **Scramble UI:** `/docs/api`
- **Scalar UI:** `/scalar`
- **OpenAPI JSON:** `/docs/api.json`

## Authorization

All endpoints require authentication via JWT token:
```
Authorization: Bearer {token}
```

### Access Control

- **Thread/Reply Creation**: User must be enrolled in the scheme
- **Thread/Reply Update**: Only author can update their own content
- **Thread/Reply Delete**: Author or moderators can delete
- **Pin/Close Thread**: Only moderators (admin or instructor)
- **Mark Accepted Answer**: Only instructors

## Events and Notifications

The module fires the following events:

- `ThreadCreated`: Notifies instructor and enrolled users (if author is instructor)
- `ReplyCreated`: Notifies thread/reply author
- `ThreadPinned`: Notifies all enrolled users
- `ReactionAdded`: Notifies content author

## Models

### Thread
- `id`: Primary key
- `scheme_id`: Foreign key to courses
- `author_id`: Foreign key to users
- `title`: Thread title
- `content`: Thread content
- `is_pinned`: Boolean flag
- `is_closed`: Boolean flag
- `is_resolved`: Boolean flag
- `views_count`: View counter
- `replies_count`: Reply counter
- `last_activity_at`: Last activity timestamp

### Reply
- `id`: Primary key
- `thread_id`: Foreign key to threads
- `parent_id`: Foreign key to replies (nullable)
- `author_id`: Foreign key to users
- `content`: Reply content
- `depth`: Nesting depth (0-5)
- `is_accepted_answer`: Boolean flag

### Reaction
- `id`: Primary key
- `user_id`: Foreign key to users
- `reactable_type`: Polymorphic type (Thread or Reply)
- `reactable_id`: Polymorphic ID
- `type`: Reaction type (like, helpful, solved)

### ForumStatistic
- `id`: Primary key
- `scheme_id`: Foreign key to courses
- `user_id`: Foreign key to users (nullable)
- `threads_count`: Thread count
- `replies_count`: Reply count
- `views_count`: View count
- `avg_response_time_minutes`: Average response time
- `response_rate`: Response rate percentage
- `period_start`: Period start date
- `period_end`: Period end date

## Database Seeding

To seed sample forum data:

```bash
php artisan module:seed Forums
```

This will create:
- 5 threads per course
- 3-7 replies per thread
- Nested replies
- Reactions on threads and replies

## Testing

Run tests:
```bash
php artisan test --filter=Forums
```

## Performance Considerations

- Full-text search indexes on threads table
- Composite indexes for efficient querying
- Eager loading to prevent N+1 queries
- Pagination with 20 items per page
- Soft deletes for data preservation
