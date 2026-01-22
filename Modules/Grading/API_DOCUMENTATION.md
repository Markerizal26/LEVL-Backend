# Assessment & Grading System - API Documentation

## Overview

This document describes the API endpoints for the Assessment & Grading System. All endpoints require authentication via API token (`auth:api` middleware).

**Base URL:** `/api/v1`

---

## Authentication

All endpoints require a valid API token in the Authorization header:

```
Authorization: Bearer <token>
```

---

## Grading Endpoints

### Auto-Grading

#### Trigger Auto-Grading
```
POST /submissions/{submission}/auto-grade
```

Auto-grades all auto-gradable questions (MCQ, Checkbox) and marks manual questions for manual grading.

**Authorization:** Admin, Instructor, Superadmin

**Response:**
```json
{
  "success": true,
  "data": {
    "submission": {
      "id": 1,
      "state": "auto_graded",
      "score": 85.5
    },
    "grade": { ... }
  }
}
```

---

### Manual Grading

#### Submit Manual Grades
```
POST /submissions/{submission}/manual-grade
```

Grade essay and file upload questions with partial credit support.

**Authorization:** Admin, Instructor, Superadmin

**Request Body:**
```json
{
  "grades": [
    {
      "question_id": 1,
      "score": 8.5,
      "feedback": "Good analysis"
    }
  ],
  "feedback": "Overall good work"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "grade": { ... }
  }
}
```

---

### Grading Queue

#### Get Grading Queue
```
GET /grading/queue
```

Returns submissions pending manual grading, ordered by submission timestamp (oldest first).

**Authorization:** Admin, Instructor, Superadmin

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| assignment_id | integer | Filter by assignment |
| user_id | integer | Filter by student |
| date_from | date | Filter by start date |
| date_to | date | Filter by end date |
| page | integer | Page number (default: 1) |
| per_page | integer | Items per page (default: 15) |

**Response:**
```json
{
  "success": true,
  "data": {
    "queue": [ ... ],
    "meta": {
      "total": 100,
      "per_page": 15,
      "current_page": 1,
      "last_page": 7
    }
  }
}
```

#### Return to Queue
```
POST /submissions/{submission}/return-to-queue
```

Return a submission back to the pending manual grading queue.

**Authorization:** Admin, Instructor, Superadmin

---

### Draft Grades

#### Save Draft Grade
```
POST /submissions/{submission}/draft-grade
```

Save grading progress without finalizing.

**Authorization:** Admin, Instructor, Superadmin

**Request Body:**
```json
{
  "grades": [
    {
      "question_id": 1,
      "score": 7.5,
      "feedback": "Partial feedback"
    }
  ]
}
```

#### Get Draft Grade
```
GET /submissions/{submission}/draft-grade
```

Retrieve previously saved draft grades.

**Authorization:** Admin, Instructor, Superadmin

---

### Grade Override

#### Override Grade
```
POST /submissions/{submission}/override-grade
```

Manually override the final grade with required justification.

**Authorization:** Admin, Instructor, Superadmin

**Request Body:**
```json
{
  "score": 95.0,
  "reason": "Extra credit for exceptional work"
}
```

---

### Grade Release

#### Release Grade
```
POST /submissions/{submission}/release-grade
```

Release grade to make it visible to the student.

**Authorization:** Admin, Instructor, Superadmin

---

### Grading Status

#### Check Grading Status
```
GET /submissions/{submission}/grading-status
```

Check if grading is complete for a submission.

**Authorization:** Admin, Instructor, Superadmin

**Response:**
```json
{
  "success": true,
  "data": {
    "submission_id": 1,
    "is_complete": true,
    "graded_questions": 5,
    "total_questions": 5,
    "can_finalize": true,
    "can_release": true
  }
}
```

---

### Bulk Operations

#### Bulk Release Grades
```
POST /grading/bulk-release
```

Release grades for multiple submissions at once.

**Authorization:** Admin, Instructor, Superadmin

**Request Body:**
```json
{
  "submission_ids": [1, 2, 3, 4, 5],
  "async": false
}
```

**Note:** Set `async: true` to process as a background job.

#### Bulk Apply Feedback
```
POST /grading/bulk-feedback
```

Apply the same feedback to multiple submissions.

**Authorization:** Admin, Instructor, Superadmin

**Request Body:**
```json
{
  "submission_ids": [1, 2, 3, 4, 5],
  "feedback": "Great work on this assignment!",
  "async": false
}
```

---

## Appeal Endpoints

### Submit Appeal
```
POST /submissions/{submission}/appeals
```

Submit an appeal for a late submission rejection.

**Authorization:** Student (owner of submission only)

**Request Body (multipart/form-data):**
| Field | Type | Required | Description |
|-------|------|----------|-------------|
| reason | string | Yes | Reason for appeal |
| documents[] | file | No | Supporting documents |

---

### Approve Appeal
```
POST /appeals/{appeal}/approve
```

Approve a pending appeal.

**Authorization:** Admin, Instructor, Superadmin

---

### Deny Appeal
```
POST /appeals/{appeal}/deny
```

Deny a pending appeal with reason.

**Authorization:** Admin, Instructor, Superadmin

**Request Body:**
```json
{
  "reason": "Insufficient justification provided"
}
```

---

### Get Pending Appeals
```
GET /appeals/pending
```

Get all pending appeals for the instructor.

**Authorization:** Admin, Instructor, Superadmin

---

### Get Appeal Details
```
GET /appeals/{appeal}
```

Get details of a specific appeal.

**Authorization:** Student (own appeals), Admin, Instructor, Superadmin

---

## Assignment Endpoints

### List Assignments
```
GET /courses/{course}/units/{unit}/lessons/{lesson}/assignments
```

### Create Assignment
```
POST /courses/{course}/units/{unit}/lessons/{lesson}/assignments
```

**Authorization:** Admin, Instructor, Superadmin

### Show Assignment
```
GET /assignments/{assignment}
```

### Update Assignment
```
PUT /assignments/{assignment}
```

**Authorization:** Admin, Instructor, Superadmin

### Delete Assignment
```
DELETE /assignments/{assignment}
```

**Authorization:** Admin, Instructor, Superadmin

### Publish/Unpublish Assignment
```
PUT /assignments/{assignment}/publish
PUT /assignments/{assignment}/unpublish
```

**Authorization:** Admin, Instructor, Superadmin

---

### Question Management

#### List Questions
```
GET /assignments/{assignment}/questions
```

#### Add Question
```
POST /assignments/{assignment}/questions
```

**Authorization:** Admin, Instructor, Superadmin

**Request Body:**
```json
{
  "type": "multiple_choice",
  "content": "What is 2 + 2?",
  "options": ["3", "4", "5", "6"],
  "answer_key": ["4"],
  "weight": 1.0
}
```

**Question Types:** `multiple_choice`, `checkbox`, `essay`, `file_upload`

#### Update Question
```
PUT /assignments/{assignment}/questions/{question}
```

**Authorization:** Admin, Instructor, Superadmin

#### Delete Question
```
DELETE /assignments/{assignment}/questions/{question}
```

**Authorization:** Admin, Instructor, Superadmin

---

### Prerequisite Checking
```
GET /assignments/{assignment}/check-prerequisites
```

Check if the current student can access an assignment based on prerequisites.

**Response:**
```json
{
  "success": true,
  "data": {
    "can_access": false,
    "incomplete_prerequisites": [
      { "id": 1, "title": "Introduction Quiz" }
    ],
    "message": "Complete prerequisites first"
  }
}
```

---

### Override Management

#### Grant Override
```
POST /assignments/{assignment}/overrides
```

Grant an override to a student.

**Authorization:** Admin, Instructor, Superadmin

**Request Body:**
```json
{
  "student_id": 123,
  "type": "deadline",
  "reason": "Medical emergency",
  "value": {
    "extended_deadline": "2024-12-31T23:59:59Z"
  }
}
```

**Override Types:** `prerequisite`, `attempts`, `deadline`

#### List Overrides
```
GET /assignments/{assignment}/overrides
```

**Authorization:** Admin, Instructor, Superadmin

---

### Assignment Duplication
```
POST /assignments/{assignment}/duplicate
```

Duplicate an assignment with all questions and settings.

**Authorization:** Admin, Instructor, Superadmin

---

## Submission Endpoints

### Start Submission
```
POST /assignments/{assignment}/submissions/start
```

Start a new submission attempt.

### Submit Answers
```
POST /submissions/{submission}/submit
```

Submit answers for a submission.

**Request Body:**
```json
{
  "answers": [
    {
      "question_id": 1,
      "selected_options": ["4"]
    },
    {
      "question_id": 2,
      "content": "Essay answer text..."
    }
  ]
}
```

### Check Deadline
```
GET /assignments/{assignment}/check-deadline
```

Check deadline status including tolerance window.

**Response:**
```json
{
  "success": true,
  "data": {
    "deadline": {
      "deadline_at": "2024-12-15T23:59:59Z",
      "tolerance_minutes": 30,
      "tolerance_end_at": "2024-12-16T00:29:59Z",
      "is_past_deadline": false,
      "is_within_tolerance": false,
      "is_past_tolerance": false,
      "remaining_seconds": 86400,
      "can_submit": true,
      "has_deadline_override": false
    }
  }
}
```

### Check Attempts
```
GET /assignments/{assignment}/check-attempts
```

Check attempt limits and cooldown status.

### Search Submissions
```
GET /submissions/search
```

Search submissions with filters.

**Authorization:** Admin, Instructor, Superadmin

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| q | string | Search query (student name/email) |
| state | string | Filter by state |
| score_min | float | Minimum score |
| score_max | float | Maximum score |
| date_from | date | Start date |
| date_to | date | End date |
| assignment_id | integer | Filter by assignment |

### My Submissions
```
GET /assignments/{assignment}/my-submissions
```

Get all submissions for the current student with highest marked.

### Highest Submission
```
GET /assignments/{assignment}/highest-submission
```

Get the highest scoring submission for the current student.

---

## Audit Log Endpoints

### Search Audit Logs
```
GET /audit-logs
```

Search and filter audit logs.

**Authorization:** Admin, Superadmin only

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| action | string | Filter by action type |
| actor_id | integer | Filter by actor |
| subject_id | integer | Filter by subject |
| subject_type | string | Filter by subject type |
| start_date | date | Start date |
| end_date | date | End date |

### Get Audit Log Entry
```
GET /audit-logs/{id}
```

**Authorization:** Admin, Superadmin only

### Get Available Actions
```
GET /audit-logs/actions
```

Get list of available action types for filtering.

**Authorization:** Admin, Superadmin only

---

## Error Responses

All endpoints return consistent error responses:

```json
{
  "success": false,
  "message": "Error description",
  "errors": []
}
```

**HTTP Status Codes:**
- `400` - Bad Request (validation errors)
- `401` - Unauthorized
- `403` - Forbidden (insufficient permissions)
- `404` - Not Found
- `409` - Conflict (invalid state transition)
- `422` - Unprocessable Entity (business rule violation)
- `500` - Internal Server Error
