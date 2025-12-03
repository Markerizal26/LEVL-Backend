# Requirements Document - Database Consistency Review

## Introduction

This document outlines the requirements for reviewing and improving database schema consistency, normalization, naming conventions, and enum usage across the LMS Sertifikasi application. The review focuses on ensuring production-ready database design that follows best practices and maintains consistency across all modules.

## Glossary

- **System**: The LMS Sertifikasi application database layer
- **Enum**: A database column type that restricts values to a predefined set
- **Normalization**: The process of organizing database tables to reduce redundancy
- **Foreign Key**: A constraint that links records between tables
- **Polymorphic Relation**: A relationship where a model can belong to multiple other models
- **Soft Delete**: A pattern where records are marked as deleted rather than physically removed
- **Migration**: A version-controlled database schema change file
- **PHP Enum**: A native PHP 8.1+ enum type for type-safe value constraints

## Requirements

### Requirement 1: Consistent Enum Usage

**User Story:** As a developer, I want all status and type columns to use proper enum constraints, so that data integrity is enforced at the database level and code is type-safe.

#### Acceptance Criteria

1. WHEN a column represents a fixed set of values THEN the system SHALL use database enum type instead of plain string
2. WHEN enum columns are defined in migrations THEN the system SHALL have corresponding PHP Enum classes for type-safe casting
3. WHEN models use enum columns THEN the system SHALL cast them to PHP Enum types in the $casts array
4. WHEN enum values need to be extended THEN the system SHALL use a migration to add new values
5. IF an enum column currently uses string type THEN the system SHALL migrate it to proper enum type

**Identified String Columns That Should Be Enum:**

| Table | Column | Current Type | Should Be Enum Values |
|-------|--------|--------------|----------------------|
| `assessment_registrations` | `status` | string(50) | `pending`, `confirmed`, `completed`, `cancelled`, `no_show` |
| `assessment_registrations` | `payment_status` | string(50) | `pending`, `paid`, `failed`, `refunded` |
| `assessment_registrations` | `payment_method` | string(50) | `bank_transfer`, `credit_card`, `e_wallet`, `cash` |
| `assessment_registrations` | `result` | string(50) | `passed`, `failed`, `pending` |
| `notification_preferences` | `category` | string(50) | `system`, `assignment`, `assessment`, `grading`, `gamification`, `news`, `forum` |
| `notification_preferences` | `channel` | string(50) | `in_app`, `email`, `push` |
| `notification_preferences` | `frequency` | string(50) | `immediate`, `daily`, `weekly`, `never` |
| `otp_codes` | `provider` | string(50) | `email`, `sms` (or should be nullable enum) |

### Requirement 2: Consistent Naming Conventions

**User Story:** As a developer, I want all database columns and tables to follow consistent naming conventions, so that the codebase is predictable and maintainable.

#### Acceptance Criteria

1. WHEN naming foreign key columns THEN the system SHALL use the pattern `{related_table_singular}_id` (e.g., `user_id`, `course_id`)
2. WHEN naming polymorphic columns THEN the system SHALL use the pattern `{relation}_type` and `{relation}_id` (e.g., `scope_type`, `scope_id`)
3. WHEN naming boolean columns THEN the system SHALL use the prefix `is_` or `has_` (e.g., `is_active`, `has_completed`)
4. WHEN naming timestamp columns THEN the system SHALL use the suffix `_at` (e.g., `published_at`, `completed_at`)
5. WHEN naming count columns THEN the system SHALL use the suffix `_count` (e.g., `views_count`, `replies_count`)
6. WHEN naming JSON columns THEN the system SHALL use the suffix `_json` or descriptive names without suffix (e.g., `tags_json`, `criteria`)

### Requirement 3: Database Normalization

**User Story:** As a developer, I want the database to be properly normalized, so that data redundancy is minimized and data integrity is maintained.

#### Acceptance Criteria

1. WHEN data is repeated across multiple rows THEN the system SHALL extract it into a separate table with foreign key relationships
2. WHEN a column contains JSON arrays of simple values THEN the system SHALL evaluate if a pivot table is more appropriate
3. WHEN polymorphic relationships are used THEN the system SHALL ensure proper indexing on type and id columns
4. WHEN a table has redundant columns THEN the system SHALL remove them and compute values dynamically
5. WHEN related data is frequently queried together THEN the system SHALL use proper foreign key constraints

### Requirement 4: Index Optimization

**User Story:** As a developer, I want proper database indexes on frequently queried columns, so that query performance is optimized.

#### Acceptance Criteria

1. WHEN a column is used in WHERE clauses frequently THEN the system SHALL have an index on that column
2. WHEN multiple columns are queried together THEN the system SHALL have composite indexes
3. WHEN foreign key columns are defined THEN the system SHALL have indexes on those columns
4. WHEN full-text search is needed THEN the system SHALL use FULLTEXT indexes on appropriate columns
5. WHEN status and date columns are filtered together THEN the system SHALL have composite indexes

### Requirement 5: Consistent Status Values

**User Story:** As a developer, I want status values to be consistent across all modules, so that the application behavior is predictable.

#### Acceptance Criteria

1. WHEN content has publication status THEN the system SHALL use consistent values: `draft`, `published`, `archived`
2. WHEN enrollment has status THEN the system SHALL use consistent values: `pending`, `active`, `completed`, `cancelled`
3. WHEN user has status THEN the system SHALL use consistent values: `pending`, `active`, `inactive`, `banned`
4. WHEN assessment attempt has status THEN the system SHALL use consistent values: `in_progress`, `completed`, `expired`
5. WHEN submission has status THEN the system SHALL use consistent values: `draft`, `submitted`, `graded`, `late`

### Requirement 6: Foreign Key Constraints

**User Story:** As a developer, I want all relationships to have proper foreign key constraints, so that referential integrity is maintained.

#### Acceptance Criteria

1. WHEN a column references another table THEN the system SHALL have a foreign key constraint
2. WHEN a parent record is deleted THEN the system SHALL define appropriate cascade behavior (CASCADE, SET NULL, or RESTRICT)
3. WHEN soft deletes are used THEN the system SHALL ensure foreign keys handle soft-deleted records appropriately
4. WHEN polymorphic relations are used THEN the system SHALL document the relationship clearly in model PHPDoc

### Requirement 7: PHP Enum Classes

**User Story:** As a developer, I want PHP Enum classes for all enum columns, so that type safety is enforced in the application layer.

#### Acceptance Criteria

1. WHEN a database enum column exists THEN the system SHALL have a corresponding PHP Enum class
2. WHEN PHP Enum classes are created THEN the system SHALL place them in the appropriate module's `app/Enums` directory
3. WHEN models use enum columns THEN the system SHALL cast them using the PHP Enum class
4. WHEN enum values are used in code THEN the system SHALL reference the PHP Enum instead of magic strings
5. WHEN validation rules check enum values THEN the system SHALL use the PHP Enum's cases

**Required PHP Enum Classes:**

| Module | Enum Class | Values | Used By |
|--------|-----------|--------|---------|
| Auth | `UserStatus` | `pending`, `active`, `inactive`, `banned` | User model |
| Schemes | `CourseStatus` | `draft`, `published`, `archived` | Course model |
| Schemes | `CourseType` | `okupasi`, `kluster` | Course model |
| Schemes | `LevelTag` | `dasar`, `menengah`, `mahir` | Course model |
| Schemes | `EnrollmentType` | `auto_accept`, `key_based`, `approval` | Course model |
| Schemes | `ProgressionMode` | `sequential`, `free` | Course model |
| Schemes | `ContentType` | `markdown`, `video`, `link` | Lesson model |
| Enrollments | `EnrollmentStatus` | `pending`, `active`, `completed`, `cancelled` | Enrollment model |
| Assessments | `ExerciseType` | `quiz`, `exam` | Exercise model |
| Assessments | `ExerciseStatus` | `draft`, `published`, `archived` | Exercise model |
| Assessments | `ScopeType` | `course`, `unit`, `lesson` | Exercise, GradingRubric models |
| Assessments | `QuestionType` | `multiple_choice`, `free_text`, `file_upload` | Question model |
| Assessments | `AttemptStatus` | `in_progress`, `completed`, `expired` | Attempt model |
| Assessments | `RegistrationStatus` | `pending`, `confirmed`, `completed`, `cancelled`, `no_show` | AssessmentRegistration model |
| Assessments | `PaymentStatus` | `pending`, `paid`, `failed`, `refunded` | AssessmentRegistration model |
| Learning | `SubmissionType` | `text`, `file`, `mixed` | Assignment model |
| Learning | `SubmissionStatus` | `draft`, `submitted`, `graded`, `late` | Submission model |
| Learning | `AssignmentStatus` | `draft`, `published`, `archived` | Assignment model |
| Grading | `SourceType` | `assignment`, `attempt` | Grade model |
| Grading | `GradeStatus` | `pending`, `graded`, `reviewed` | Grade model |
| Gamification | `PointSourceType` | `lesson`, `assignment`, `attempt`, `system` | Point model |
| Gamification | `PointReason` | `completion`, `score`, `bonus`, `penalty` | Point model |
| Gamification | `BadgeType` | `achievement`, `milestone`, `completion` | Badge model |
| Gamification | `ChallengeType` | `daily`, `weekly`, `special` | Challenge model |
| Content | `ContentStatus` | `draft`, `published`, `scheduled` | Announcement, News models |
| Content | `TargetType` | `all`, `role`, `course` | Announcement model |
| Content | `Priority` | `low`, `normal`, `high` | Announcement, Notification models |
| Notifications | `NotificationType` | `system`, `assignment`, `assessment`, etc. | Notification model |
| Notifications | `NotificationChannel` | `in_app`, `email`, `push` | Notification model |
| Notifications | `NotificationFrequency` | `immediate`, `daily`, `weekly`, `never` | NotificationPreference model |
| Common | `CategoryStatus` | `active`, `inactive` | Category model |
| Common | `SettingType` | `string`, `number`, `boolean`, `json` | SystemSetting model |

### Requirement 8: Audit and Soft Delete Consistency

**User Story:** As a developer, I want audit and soft delete patterns to be consistent across all tables, so that data tracking is uniform.

#### Acceptance Criteria

1. WHEN a table supports soft deletes THEN the system SHALL have `deleted_at` column and `deleted_by` foreign key
2. WHEN records are created or updated THEN the system SHALL have `created_at` and `updated_at` timestamps
3. WHEN tracking who deleted a record THEN the system SHALL use `deleted_by` column with foreign key to users
4. WHEN audit logging is needed THEN the system SHALL use a consistent audit table structure

### Requirement 9: Data Type Consistency

**User Story:** As a developer, I want data types to be consistent for similar columns across tables, so that data handling is predictable.

#### Acceptance Criteria

1. WHEN storing scores THEN the system SHALL use consistent data types (integer or decimal with same precision)
2. WHEN storing text content THEN the system SHALL use appropriate types (string for short, text for medium, longText for large)
3. WHEN storing file paths THEN the system SHALL use `string(255)` consistently
4. WHEN storing counts THEN the system SHALL use `unsignedInteger` or `unsignedBigInteger` consistently
5. WHEN storing percentages THEN the system SHALL use `decimal(5,2)` or `float` consistently



### Requirement 10: API Routing Consistency

**User Story:** As a developer, I want all API routes to follow consistent patterns and naming conventions, so that the API is predictable and easy to use.

#### Acceptance Criteria

1. WHEN defining API routes THEN the system SHALL use consistent HTTP methods (GET for read, POST for create, PUT for update, DELETE for delete)
2. WHEN naming route paths THEN the system SHALL use kebab-case for multi-word resources (e.g., `course-tags` not `courseTags`)
3. WHEN using authentication middleware THEN the system SHALL use consistent middleware (`auth:api` not `auth:sanctum`)
4. WHEN defining nested resources THEN the system SHALL follow RESTful conventions consistently
5. WHEN route names are defined THEN the system SHALL use consistent naming patterns across modules
6. IF routes have potential conflicts THEN the system SHALL resolve them with proper ordering or unique paths

**Identified Routing Issues:**

| Module | Issue | Current | Should Be |
|--------|-------|---------|-----------|
| Gamification | Wrong auth middleware | `auth:sanctum` | `auth:api` |
| Grading | Wrong auth middleware | `auth:sanctum` | `auth:api` |
| Content | Inconsistent ID binding | `/{id}` for announcements | `/{announcement}` for model binding |
| Content | Mixed slug/id usage | `/{slug}` for news, `/{id}` for announcements | Should be consistent |
| Forums | Missing controller import | `ForumStatisticsController` not imported | Add import statement |
| Enrollments | Inconsistent action naming | `cancel`, `withdraw` as POST | Should be DELETE or use consistent verbs |
| Assessments | Inconsistent prefix | `assessments/exercises` | Could conflict with `assessments/{assessment}` |
| Search | Duplicate search endpoint | `/content/search` and `/search/courses` | Should consolidate or differentiate |

### Requirement 11: Route Name Consistency

**User Story:** As a developer, I want all routes to have consistent and predictable names, so that route generation is reliable.

#### Acceptance Criteria

1. WHEN routes are defined THEN the system SHALL have explicit route names using `->name()`
2. WHEN naming routes THEN the system SHALL follow the pattern `{module}.{resource}.{action}`
3. WHEN using apiResource THEN the system SHALL use consistent naming via `->names()`
4. WHEN routes are nested THEN the system SHALL reflect the hierarchy in the route name
5. WHERE routes lack names THEN the system SHALL add appropriate names

**Routes Missing Names:**

| Module | Route | Missing Name |
|--------|-------|--------------|
| Schemes | `GET courses` | `courses.index` |
| Schemes | `GET courses/{course:slug}` | `courses.show` |
| Schemes | `POST courses` | `courses.store` |
| Schemes | Most unit/lesson routes | Need explicit names |
| Content | All announcement routes | Need explicit names |
| Content | All news routes | Need explicit names |
| Forums | Most thread/reply routes | Need explicit names |
| Common | Category routes | Need explicit names |

### Requirement 12: Middleware Consistency

**User Story:** As a developer, I want middleware to be applied consistently across all modules, so that security and functionality are uniform.

#### Acceptance Criteria

1. WHEN protecting API routes THEN the system SHALL use `auth:api` middleware consistently
2. WHEN checking roles THEN the system SHALL use consistent role middleware format (`role:Admin|Instructor`)
3. WHEN applying rate limiting THEN the system SHALL use consistent throttle configurations
4. WHEN routes require specific permissions THEN the system SHALL use policy middleware consistently
5. WHERE middleware is inconsistent THEN the system SHALL standardize to the most common pattern

**Middleware Inconsistencies:**

| Module | Current | Should Be |
|--------|---------|-----------|
| Gamification | `auth:sanctum` | `auth:api` |
| Grading | `auth:sanctum` | `auth:api` |
| Auth | `role:admin` (lowercase) | `role:Admin` (consistent casing) |
| Schemes | `role:Superadmin\|Admin` | `role:Superadmin\|Admin\|Instructor` (include Instructor where appropriate) |
