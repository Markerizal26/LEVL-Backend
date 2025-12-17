# Requirements Document

## Introduction

Dokumen ini mendefinisikan requirements untuk implementasi API hooks di frontend (prep-lsp-fe) yang akan mengintegrasikan semua endpoint backend modules. Implementasi akan menggunakan React Query (TanStack Query) dengan custom hooks pattern yang sudah ada, dan akan dilakukan secara bertahap sesuai prioritas module.

## Glossary

- **API Hook**: Custom React hook yang menggunakan React Query untuk fetch/mutate data dari API
- **React Query**: Library untuk data fetching, caching, dan state management (TanStack Query)
- **Custom Hook**: Reusable React hook yang mengenkapsulasi logic API calls
- **Query Hook**: Hook untuk GET requests (read operations)
- **Mutation Hook**: Hook untuk POST/PUT/DELETE requests (write operations)
- **Paginated Query**: Query yang support pagination dengan page dan perPage parameters
- **Query Invalidation**: Proses refresh cache setelah mutation berhasil
- **TypeScript Types**: Interface/type definitions untuk request/response data
- **Master Data**: Static/reference data yang digunakan di seluruh aplikasi (enums, statuses, types)
- **Master Data Type**: Kategori master data (e.g., categories, tags, user-status, roles)
- **Master Data Item**: Individual value dalam suatu master data type

## Requirements

### Requirement 1: Schemes Module API Hooks

**User Story:** As a frontend developer, I want complete API hooks for Schemes module, so that I can implement course, unit, lesson, and lesson block features with proper data fetching and caching.

#### Acceptance Criteria

1. WHEN implementing course features THEN the system SHALL provide hooks for units CRUD operations including reorder and publish/unpublish
2. WHEN implementing lesson features THEN the system SHALL provide hooks for lessons CRUD operations including publish/unpublish and complete lesson
3. WHEN implementing lesson block features THEN the system SHALL provide hooks for lesson blocks CRUD operations
4. WHEN implementing progress tracking THEN the system SHALL provide hooks for fetching course progress and completing lessons
5. WHEN implementing enrollment key management THEN the system SHALL provide hooks for generate, update, and remove enrollment keys

### Requirement 2: Enrollments Module API Hooks

**User Story:** As a frontend developer, I want complete API hooks for Enrollments module, so that I can implement enrollment management, status tracking, and reporting features.

#### Acceptance Criteria

1. WHEN implementing enrollment actions THEN the system SHALL provide hooks for enroll, cancel, withdraw, approve, decline, and remove operations
2. WHEN checking enrollment status THEN the system SHALL provide hooks for fetching enrollment status by course
3. WHEN viewing enrollments THEN the system SHALL provide hooks for fetching managed enrollments and enrollments by course
4. WHEN generating reports THEN the system SHALL provide hooks for completion rate, enrollment funnel, and CSV export
5. WHEN performing enrollment mutations THEN the system SHALL invalidate relevant queries to refresh UI data

### Requirement 3: Learning Module API Hooks

**User Story:** As a frontend developer, I want complete API hooks for Learning module, so that I can implement assignment and submission management features.

#### Acceptance Criteria

1. WHEN managing assignments THEN the system SHALL provide hooks for assignments CRUD operations including publish/unpublish
2. WHEN managing submissions THEN the system SHALL provide hooks for submissions CRUD operations including grade submission
3. WHEN fetching assignments THEN the system SHALL provide hooks for fetching assignments by lesson
4. WHEN fetching submissions THEN the system SHALL provide hooks for fetching submissions by assignment
5. WHEN performing mutations THEN the system SHALL invalidate relevant queries to refresh assignment and submission data

### Requirement 4: Master Data Module API Hooks Enhancement

**User Story:** As a frontend developer, I want enhanced master data hooks with type listing capability, so that I can build a master data management page that shows all available types and their values.

#### Acceptance Criteria

1. WHEN accessing master data page THEN the system SHALL provide a hook to fetch all available master data types
2. WHEN selecting a master data type THEN the system SHALL provide hooks to fetch all items for that type with pagination
3. WHEN viewing master data details THEN the system SHALL provide hooks to fetch individual master data items
4. WHEN managing master data THEN the system SHALL provide hooks for CRUD operations on master data items (Superadmin only)
5. WHEN fetching enum-based master data THEN the system SHALL provide individual hooks for each enum type (user-status, roles, course-status, etc.)

### Requirement 5: Search Module API Hooks

**User Story:** As a frontend developer, I want API hooks for Search module, so that I can implement course search, autocomplete, and search history features.

#### Acceptance Criteria

1. WHEN implementing course search THEN the system SHALL provide hooks for searching courses with query parameters
2. WHEN implementing autocomplete THEN the system SHALL provide hooks for fetching autocomplete suggestions
3. WHEN viewing search history THEN the system SHALL provide hooks for fetching user's search history
4. WHEN clearing search history THEN the system SHALL provide hooks for deleting search history
5. WHEN performing search THEN the system SHALL support public access (no authentication required)

### Requirement 6: Grading Module API Hooks

**User Story:** As a frontend developer, I want API hooks for Grading module, so that I can implement grade management features.

#### Acceptance Criteria

1. WHEN managing grades THEN the system SHALL provide hooks for gradings CRUD operations
2. WHEN fetching grades THEN the system SHALL provide hooks with pagination support
3. WHEN viewing grade details THEN the system SHALL provide hooks for fetching individual grades
4. WHEN performing mutations THEN the system SHALL invalidate relevant queries to refresh grade data
5. WHEN accessing grading features THEN the system SHALL require authentication

### Requirement 7: Notifications Module API Hooks

**User Story:** As a frontend developer, I want API hooks for Notifications module, so that I can implement notification center and preference management features.

#### Acceptance Criteria

1. WHEN managing notifications THEN the system SHALL provide hooks for notifications CRUD operations
2. WHEN viewing notifications THEN the system SHALL provide hooks with pagination support
3. WHEN managing notification preferences THEN the system SHALL provide hooks for fetching and updating preferences
4. WHEN resetting preferences THEN the system SHALL provide hooks for resetting to default values
5. WHEN performing mutations THEN the system SHALL invalidate relevant queries to refresh notification data

### Requirement 8: Forums Module API Hooks

**User Story:** As a frontend developer, I want API hooks for Forums module, so that I can implement forum threads, replies, reactions, and statistics features.

#### Acceptance Criteria

1. WHEN managing forum threads THEN the system SHALL provide hooks for threads CRUD operations including search, pin, and close
2. WHEN managing replies THEN the system SHALL provide hooks for replies CRUD operations including accept answer
3. WHEN managing reactions THEN the system SHALL provide hooks for toggling reactions on threads and replies
4. WHEN viewing forum statistics THEN the system SHALL provide hooks for fetching forum statistics and user stats
5. WHEN accessing forum features THEN the system SHALL check forum access permissions via middleware

### Requirement 9: Gamification Module API Hooks

**User Story:** As a frontend developer, I want API hooks for Gamification module, so that I can implement challenges, leaderboards, badges, and points features.

#### Acceptance Criteria

1. WHEN viewing challenges THEN the system SHALL provide hooks for fetching all challenges, my challenges, and completed challenges
2. WHEN claiming challenges THEN the system SHALL provide hooks for claiming challenge rewards
3. WHEN viewing leaderboards THEN the system SHALL provide hooks for fetching leaderboard data and user's rank
4. WHEN viewing gamification dashboard THEN the system SHALL provide hooks for summary, badges, points history, and achievements
5. WHEN performing mutations THEN the system SHALL invalidate relevant queries to refresh gamification data

### Requirement 10: Content Module API Hooks

**User Story:** As a frontend developer, I want API hooks for Content module, so that I can implement announcements, news, statistics, and content approval features.

#### Acceptance Criteria

1. WHEN managing announcements THEN the system SHALL provide hooks for announcements CRUD operations including publish, schedule, and mark as read
2. WHEN managing news THEN the system SHALL provide hooks for news CRUD operations including trending, publish, and schedule
3. WHEN managing course announcements THEN the system SHALL provide hooks for fetching and creating course-specific announcements
4. WHEN viewing content statistics THEN the system SHALL provide hooks for fetching statistics, trending content, and most viewed content
5. WHEN managing content approval THEN the system SHALL provide hooks for submit, approve, reject, and pending review operations

### Requirement 11: Operations Module API Hooks

**User Story:** As a frontend developer, I want API hooks for Operations module, so that I can implement operations management features.

#### Acceptance Criteria

1. WHEN managing operations THEN the system SHALL provide hooks for operations CRUD operations
2. WHEN fetching operations THEN the system SHALL provide hooks with pagination support
3. WHEN viewing operation details THEN the system SHALL provide hooks for fetching individual operations
4. WHEN performing mutations THEN the system SHALL invalidate relevant queries to refresh operations data
5. WHEN accessing operations features THEN the system SHALL require authentication

### Requirement 12: TypeScript Type Definitions

**User Story:** As a frontend developer, I want complete TypeScript type definitions for all API requests and responses, so that I have type safety and autocomplete support.

#### Acceptance Criteria

1. WHEN using API hooks THEN the system SHALL provide TypeScript interfaces for all request data types
2. WHEN receiving API responses THEN the system SHALL provide TypeScript interfaces for all response data types
3. WHEN working with nested data THEN the system SHALL provide TypeScript interfaces for nested objects and arrays
4. WHEN using enums THEN the system SHALL provide TypeScript union types matching backend enums (e.g., "draft" | "published")
5. WHEN organizing types THEN the system SHALL create module-specific type files under `lib/api/modules/types/` and export them via `types/index.ts`

### Requirement 13: Consistent Hook Patterns

**User Story:** As a frontend developer, I want consistent patterns across all API hooks, so that the codebase is maintainable and follows DRY principles.

#### Acceptance Criteria

1. WHEN creating query hooks THEN the system SHALL use `useApiQuery` or `usePaginatedQuery` from existing hook utilities
2. WHEN creating mutation hooks THEN the system SHALL use `useCreateMutation`, `useUpdateMutation`, or `useDeleteMutation` from existing hook utilities
3. WHEN defining endpoints THEN the system SHALL use constant variables for base endpoints (e.g., `const ENDPOINT = "courses"`)
4. WHEN defining query keys THEN the system SHALL use constant objects for query keys (e.g., `const QUERY_KEYS = { courses: ["courses"] }`)
5. WHEN implementing hooks THEN the system SHALL follow the same structure as existing modules (auth.ts, schemes.ts, enrollments.ts)

### Requirement 14: Query Invalidation Strategy

**User Story:** As a frontend developer, I want consistent query invalidation after mutations, so that the UI automatically reflects the latest data without manual refresh.

#### Acceptance Criteria

1. WHEN a mutation succeeds THEN the system SHALL invalidate related list queries using `queryClient.invalidateQueries()`
2. WHEN a mutation succeeds THEN the system SHALL invalidate related detail queries by ID
3. WHEN a mutation affects multiple resources THEN the system SHALL invalidate all affected query keys
4. WHEN using nested resources THEN the system SHALL invalidate parent and child queries appropriately
5. WHEN invalidating queries THEN the system SHALL use consistent query key patterns matching the QUERY_KEYS constant

### Requirement 15: File Organization and Exports

**User Story:** As a frontend developer, I want consistent file organization and exports, so that imports are clean and predictable.

#### Acceptance Criteria

1. WHEN creating module hooks THEN the system SHALL create files under `lib/api/modules/` with module name (e.g., `forums.ts`, `gamification.ts`)
2. WHEN creating types THEN the system SHALL create files under `lib/api/modules/types/` with module name (e.g., `forums.ts`, `gamification.ts`)
3. WHEN exporting hooks THEN the system SHALL add exports to `lib/api/modules/index.ts`
4. WHEN exporting types THEN the system SHALL add exports to `lib/api/modules/types/index.ts`
5. WHEN naming hooks THEN the system SHALL use descriptive names following the pattern `use[Action][Resource]` (e.g., `useCreateThread`, `useClaimChallenge`)
