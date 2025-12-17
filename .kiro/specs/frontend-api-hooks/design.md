# Design Document: Frontend API Hooks Implementation

## Overview

This design document outlines the implementation of comprehensive API hooks for the frontend application (prep-lsp-fe) to integrate with all backend module endpoints. The implementation will follow the existing patterns established in the codebase, using React Query (TanStack Query) with custom hook utilities for consistent data fetching, caching, and state management.

The implementation will be done in phases, prioritizing modules based on user-facing features and dependencies. Each module will have its own hooks file and types file, following the DRY principle by leveraging existing hook utilities (`useApiQuery`, `usePaginatedQuery`, `useCreateMutation`, `useUpdateMutation`, `useDeleteMutation`).

## Architecture

### High-Level Structure

```
prep-lsp-fe/
└── lib/
    └── api/
        ├── hooks/                    # Reusable hook utilities (existing)
        │   ├── use-api-query.ts
        │   ├── use-api-mutation.ts
        │   └── use-paginated-query.ts
        ├── modules/                  # Module-specific hooks
        │   ├── auth.ts              # ✅ Existing
        │   ├── schemes.ts           # ⚠️  Partial - needs enhancement
        │   ├── enrollments.ts       # ⚠️  Partial - needs enhancement
        │   ├── learning.ts          # ⚠️  Partial - needs enhancement
        │   ├── master-data.ts       # ⚠️  Partial - needs enhancement
        │   ├── search.ts            # ❌ New
        │   ├── grading.ts           # ❌ New
        │   ├── notifications.ts     # ❌ New
        │   ├── forums.ts            # ❌ New
        │   ├── gamification.ts      # ❌ New
        │   ├── content.ts           # ❌ New
        │   ├── operations.ts        # ❌ New
        │   ├── types/               # TypeScript type definitions
        │   │   ├── auth.ts          # ✅ Existing
        │   │   ├── schemes.ts       # ✅ Existing
        │   │   ├── enrollments.ts   # ✅ Existing
        │   │   ├── learning.ts      # ✅ Existing
        │   │   ├── master-data.ts   # ✅ Existing
        │   │   ├── search.ts        # ❌ New
        │   │   ├── grading.ts       # ❌ New
        │   │   ├── notifications.ts # ❌ New
        │   │   ├── forums.ts        # ❌ New
        │   │   ├── gamification.ts  # ❌ New
        │   │   ├── content.ts       # ❌ New
        │   │   ├── operations.ts    # ❌ New
        │   │   └── index.ts         # Central type exports
        │   └── index.ts             # Central hook exports
        ├── axios-client.ts          # ✅ Existing
        ├── token-storage.ts         # ✅ Existing
        ├── token-refresh.ts         # ✅ Existing
        └── types.ts                 # ✅ Existing (common types)
```

### Module Implementation Priority

1. **Schemes** - Enhance existing with Units, Lessons, Blocks, Progress
2. **Enrollments** - Enhance existing with enrollment actions and reports
3. **Learning** - Enhance existing with Assignments and Submissions
4. **Master Data** - Enhance existing with type listing capability
5. **Search** - New module for course search and history
6. **Grading** - New module for grade management
7. **Notifications** - New module for notifications and preferences
8. **Forums** - New module for threads, replies, reactions
9. **Gamification** - New module for challenges, leaderboards, badges
10. **Content** - New module for announcements, news, statistics
11. **Operations** - New module for operations management

## Components and Interfaces

### Hook Utilities (Existing - Reuse)

These utilities are already implemented and will be reused across all modules:

#### useApiQuery
```typescript
// For GET requests (single resource or list without pagination)
useApiQuery<TData>({
  endpoint: string,
  params?: QueryParams,
  queryKey?: unknown[],
  ...queryOptions
})
```

#### usePaginatedQuery
```typescript
// For GET requests with pagination
usePaginatedQuery<TData>({
  endpoint: string,
  initialPage?: number,
  perPage?: number,
  params?: QueryParams,
  ...queryOptions
})
```

#### useCreateMutation
```typescript
// For POST requests
useCreateMutation<TData, TVariables>({
  endpoint: string,
  onSuccess?: (response) => void,
  ...mutationOptions
})
```

#### useUpdateMutation
```typescript
// For PUT requests
useUpdateMutation<TData, TVariables>({
  endpoint: string,
  getId?: (variables) => string | number,
  onSuccess?: (response, variables) => void,
  ...mutationOptions
})
```

#### useDeleteMutation
```typescript
// For DELETE requests
useDeleteMutation<TData, TVariables>({
  endpoint: string,
  getId?: (variables) => string | number,
  onSuccess?: () => void,
  ...mutationOptions
})
```

### Module Hook Pattern

Each module will follow this consistent pattern:

```typescript
"use client";

import { useQueryClient } from "@tanstack/react-query";
import { useApiQuery, useApiQueryById } from "../hooks/use-api-query";
import { usePaginatedQuery } from "../hooks/use-paginated-query";
import {
  useCreateMutation,
  useUpdateMutation,
  useDeleteMutation,
} from "../hooks/use-api-mutation";
import type { QueryParams } from "../types";
import type { /* Module-specific types */ } from "./types/[module]";

// Constants
const ENDPOINT = "[module-endpoint]";
const QUERY_KEYS = {
  [resource]: [ENDPOINT],
  [resourceDetail]: (id: number) => [ENDPOINT, id],
  // ... other query keys
};

// Query Hooks
export function use[Resource]Query(params?: QueryParams) {
  return usePaginatedQuery<[Resource]>({
    endpoint: ENDPOINT,
    params,
  });
}

export function use[Resource]ById(
  id: number | string,
  params?: Omit<QueryParams, "page" | "perPage">
) {
  return useApiQueryById<[Resource]>({
    endpoint: ENDPOINT,
    id,
    params,
  });
}

// Mutation Hooks
export function useCreate[Resource]() {
  const queryClient = useQueryClient();

  return useCreateMutation<[Resource], Create[Resource]Data>({
    endpoint: ENDPOINT,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: QUERY_KEYS.[resource] });
    },
  });
}

export function useUpdate[Resource]() {
  const queryClient = useQueryClient();

  return useUpdateMutation<[Resource], Update[Resource]Data>({
    endpoint: ENDPOINT,
    getId: (data) => data.id,
    onSuccess: (_, variables) => {
      queryClient.invalidateQueries({ 
        queryKey: QUERY_KEYS.[resourceDetail](variables.id) 
      });
      queryClient.invalidateQueries({ queryKey: QUERY_KEYS.[resource] });
    },
  });
}

export function useDelete[Resource]() {
  const queryClient = useQueryClient();

  return useDeleteMutation<void, number>({
    endpoint: ENDPOINT,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: QUERY_KEYS.[resource] });
    },
  });
}
```

## Data Models

### Common Types (Existing)

```typescript
// lib/api/types.ts
export interface ApiResponse<T> {
  data: T;
  message?: string;
  meta?: PaginationMeta;
}

export interface PaginatedResponse<T> {
  data: T[];
  meta: PaginationMeta;
}

export interface PaginationMeta {
  current_page: number;
  per_page: number;
  total: number;
  last_page: number;
  from: number;
  to: number;
}

export interface QueryParams {
  page?: number;
  perPage?: number;
  search?: string;
  sort?: string;
  order?: "asc" | "desc";
  [key: string]: any;
}

export interface ApiError {
  message: string;
  errors?: Record<string, string[]>;
}
```

### Module-Specific Types

Each module will have its own type file following this pattern:

```typescript
// lib/api/modules/types/[module].ts

// Resource interface
export interface [Resource] {
  id: number;
  // ... other fields
  created_at: string;
  updated_at: string;
}

// Create data interface
export interface Create[Resource]Data {
  // ... required fields for creation
}

// Update data interface
export interface Update[Resource]Data {
  id: number;
  // ... optional fields for update
}

// Enums as union types
export type [Status] = "draft" | "published" | "archived";
```

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system-essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property 1: Query Invalidation After Mutations
*For any* mutation hook (create, update, delete), when the mutation succeeds, the hook SHALL call `queryClient.invalidateQueries()` with the appropriate query keys to refresh related data.
**Validates: Requirements 2.5, 3.5, 6.4, 7.5, 9.5, 11.4, 14.1**

### Property 2: Master Data Type Flexibility
*For any* master data type string, the master data hooks SHALL construct the correct endpoint and query keys dynamically to support all master data types without code duplication.
**Validates: Requirements 4.2**

### Property 3: Consistent Hook Utility Usage
*For any* query hook, it SHALL use either `useApiQuery` or `usePaginatedQuery` from the existing hook utilities, not implement custom fetch logic.
**Validates: Requirements 13.1**

### Property 4: Consistent Mutation Utility Usage
*For any* mutation hook, it SHALL use `useCreateMutation`, `useUpdateMutation`, or `useDeleteMutation` from the existing hook utilities, not implement custom mutation logic.
**Validates: Requirements 13.2**

### Property 5: Query Key Consistency
*For any* query invalidation call, the query keys used SHALL match the keys defined in the module's QUERY_KEYS constant to ensure consistent cache management.
**Validates: Requirements 14.5**

### Property 6: Nested Resource Invalidation
*For any* mutation on a nested resource (e.g., lesson within unit within course), the hook SHALL invalidate both the nested resource queries and parent resource queries.
**Validates: Requirements 14.4**

### Property 7: Detail Query Invalidation
*For any* update or delete mutation on a specific resource, the hook SHALL invalidate both the list query and the detail query for that specific resource ID.
**Validates: Requirements 14.2**

## Error Handling

### React Query Built-in Error Handling

All hooks leverage React Query's built-in error handling:

```typescript
const { data, isLoading, error, isError } = useResourceQuery();

if (isLoading) return <Loading />;
if (isError) return <Error message={error.message} />;
```

### Mutation Error Handling

```typescript
const mutation = useCreateResource();

mutation.mutate(data, {
  onError: (error) => {
    // Handle validation errors
    if (error.response?.data?.errors) {
      // Display field-specific errors
    }
    // Handle general errors
    toast.error(error.response?.data?.message || "An error occurred");
  },
});
```

### Token Refresh Integration

The existing `axios-client.ts` already handles token refresh automatically via interceptors. All hooks will benefit from this without additional configuration.

## Testing Strategy

### Unit Testing

- Test that each hook exports the correct function signature
- Test that hooks use the correct utility functions (`useApiQuery`, `usePaginatedQuery`, etc.)
- Test that constants (ENDPOINT, QUERY_KEYS) are defined correctly
- Test that TypeScript types are properly exported

### Integration Testing

- Test that query hooks fetch data correctly from mock API
- Test that mutation hooks call the correct endpoints with correct data
- Test that query invalidation works after mutations
- Test that pagination works correctly with `usePaginatedQuery`
- Test that nested resource invalidation works correctly

### Property-Based Testing

Since this is primarily about code structure and patterns rather than algorithmic logic, property-based testing is less applicable. However, we can use example-based tests to verify:

- All modules follow the same structure pattern
- All hooks use the correct utility functions
- All query invalidations use consistent query keys
- All TypeScript types are properly defined

## Implementation Notes

### Phase 1: Schemes Module Enhancement

**Files to modify:**
- `prep-lsp-fe/lib/api/modules/schemes.ts`
- `prep-lsp-fe/lib/api/modules/types/schemes.ts`

**New hooks to add:**
- Units: `useUnitsQuery`, `useUnitQuery`, `useCreateUnit`, `useUpdateUnit`, `useDeleteUnit`, `useReorderUnits`, `usePublishUnit`, `useUnpublishUnit`
- Lessons: `useLessonsQuery`, `useLessonQuery`, `useCreateLesson`, `useUpdateLesson`, `useDeleteLesson`, `usePublishLesson`, `useUnpublishLesson`, `useCompleteLesson`
- Lesson Blocks: `useLessonBlocksQuery`, `useLessonBlockQuery`, `useCreateLessonBlock`, `useUpdateLessonBlock`, `useDeleteLessonBlock`
- Progress: `useCourseProgress`, `useCompleteLessonMutation`
- Enrollment Keys: `useGenerateEnrollmentKey`, `useUpdateEnrollmentKey`, `useRemoveEnrollmentKey`

### Phase 2: Enrollments Module Enhancement

**Files to modify:**
- `prep-lsp-fe/lib/api/modules/enrollments.ts`
- `prep-lsp-fe/lib/api/modules/types/enrollments.ts`

**New hooks to add:**
- Actions: `useEnrollCourse`, `useCancelEnrollment`, `useWithdrawEnrollment`, `useApproveEnrollment`, `useDeclineEnrollment`, `useRemoveEnrollment`
- Status: `useEnrollmentStatus`
- Lists: `useManagedEnrollments`, `useEnrollmentsByCourse`
- Reports: `useCourseCompletionRate`, `useEnrollmentFunnel`, `useExportEnrollmentsCsv`

### Phase 3: Learning Module Enhancement

**Files to modify:**
- `prep-lsp-fe/lib/api/modules/learning.ts`
- `prep-lsp-fe/lib/api/modules/types/learning.ts`

**New hooks to add:**
- Assignments: `useAssignmentsQuery`, `useAssignmentQuery`, `useCreateAssignment`, `useUpdateAssignment`, `useDeleteAssignment`, `usePublishAssignment`, `useUnpublishAssignment`
- Submissions: `useSubmissionsQuery`, `useSubmissionQuery`, `useCreateSubmission`, `useUpdateSubmission`, `useGradeSubmission`

### Phase 4: Master Data Module Enhancement

**Files to modify:**
- `prep-lsp-fe/lib/api/modules/master-data.ts`
- `prep-lsp-fe/lib/api/modules/types/master-data.ts`

**New hooks to add:**
- Type listing: `useMasterDataTypes`
- Enum hooks: `useUserStatuses`, `useRoles`, `useCourseStatuses`, `useCourseTypes`, `useEnrollmentTypes`, `useLevelTags`, `useProgressionModes`, `useContentTypes`, `useEnrollmentStatuses`, `useProgressStatuses`, `useAssignmentStatuses`, `useSubmissionStatuses`, `useSubmissionTypes`, `useContentStatuses`, `usePriorities`, `useTargetTypes`, `useChallengeTypes`, `useChallengeAssignmentStatuses`, `useChallengeCriteriaTypes`, `useBadgeTypes`, `usePointSourceTypes`, `usePointReasons`, `useNotificationTypes`, `useNotificationChannels`, `useNotificationFrequencies`, `useGradeStatuses`, `useGradeSourceTypes`, `useCategoryStatuses`, `useSettingTypes`

### Phase 5-11: New Modules

For each new module (Search, Grading, Notifications, Forums, Gamification, Content, Operations):

1. Create `prep-lsp-fe/lib/api/modules/[module].ts`
2. Create `prep-lsp-fe/lib/api/modules/types/[module].ts`
3. Add exports to `prep-lsp-fe/lib/api/modules/index.ts`
4. Add type exports to `prep-lsp-fe/lib/api/modules/types/index.ts`
5. Implement hooks following the module hook pattern
6. Implement TypeScript types matching backend responses

### Query Key Patterns

Consistent query key patterns across all modules:

```typescript
const QUERY_KEYS = {
  // List queries
  [resource]: [ENDPOINT],
  [resource]WithParams: (params: QueryParams) => [ENDPOINT, params],
  
  // Detail queries
  [resourceDetail]: (id: number) => [ENDPOINT, id],
  
  // Nested queries
  [nestedResource]: (parentId: number) => [ENDPOINT, parentId, "nested"],
  
  // Special queries
  [specialQuery]: [ENDPOINT, "special-query-name"],
};
```

### Invalidation Patterns

Consistent invalidation patterns:

```typescript
// After create - invalidate list
queryClient.invalidateQueries({ queryKey: QUERY_KEYS.[resource] });

// After update - invalidate both list and detail
queryClient.invalidateQueries({ queryKey: QUERY_KEYS.[resourceDetail](id) });
queryClient.invalidateQueries({ queryKey: QUERY_KEYS.[resource] });

// After delete - invalidate list
queryClient.invalidateQueries({ queryKey: QUERY_KEYS.[resource] });

// For nested resources - invalidate parent and child
queryClient.invalidateQueries({ queryKey: QUERY_KEYS.[parentResource](parentId) });
queryClient.invalidateQueries({ queryKey: QUERY_KEYS.[nestedResource](parentId) });
```

### TypeScript Type Organization

Each module's type file should export:

1. Resource interfaces (main entities)
2. Create/Update data interfaces
3. Enum types as union types
4. Nested object interfaces
5. Query parameter interfaces (if specific to module)

Example:

```typescript
// lib/api/modules/types/forums.ts

export interface Thread {
  id: number;
  scheme_id: number;
  user_id: number;
  title: string;
  content: string;
  is_pinned: boolean;
  is_closed: boolean;
  created_at: string;
  updated_at: string;
  user?: User;
  replies_count?: number;
  reactions_count?: number;
}

export interface CreateThreadData {
  scheme_id: number;
  title: string;
  content: string;
}

export interface UpdateThreadData {
  id: number;
  title?: string;
  content?: string;
}

export interface Reply {
  id: number;
  thread_id: number;
  user_id: number;
  content: string;
  is_accepted: boolean;
  created_at: string;
  updated_at: string;
  user?: User;
}

export interface CreateReplyData {
  thread_id: number;
  content: string;
}

export interface UpdateReplyData {
  id: number;
  content: string;
}

export type ReactionType = "like" | "helpful" | "insightful";

export interface Reaction {
  id: number;
  user_id: number;
  reactable_type: "thread" | "reply";
  reactable_id: number;
  type: ReactionType;
  created_at: string;
}
```

## Dependencies

- **React Query (TanStack Query)**: Already installed and configured
- **Axios**: Already installed and configured with interceptors
- **TypeScript**: Already configured
- **Existing hook utilities**: `useApiQuery`, `usePaginatedQuery`, `useCreateMutation`, `useUpdateMutation`, `useDeleteMutation`

## Performance Considerations

### Caching Strategy

React Query provides automatic caching with configurable stale times:

```typescript
// Default configuration (already set in query client)
{
  staleTime: 5 * 60 * 1000, // 5 minutes
  cacheTime: 10 * 60 * 1000, // 10 minutes
}
```

### Optimistic Updates

For better UX, consider implementing optimistic updates for mutations:

```typescript
const mutation = useUpdateResource();

mutation.mutate(data, {
  onMutate: async (newData) => {
    // Cancel outgoing refetches
    await queryClient.cancelQueries({ queryKey: QUERY_KEYS.resource });
    
    // Snapshot previous value
    const previousData = queryClient.getQueryData(QUERY_KEYS.resource);
    
    // Optimistically update
    queryClient.setQueryData(QUERY_KEYS.resource, (old) => ({
      ...old,
      ...newData,
    }));
    
    return { previousData };
  },
  onError: (err, newData, context) => {
    // Rollback on error
    queryClient.setQueryData(QUERY_KEYS.resource, context.previousData);
  },
  onSettled: () => {
    // Refetch after mutation
    queryClient.invalidateQueries({ queryKey: QUERY_KEYS.resource });
  },
});
```

### Prefetching

For better perceived performance, prefetch data on hover or route change:

```typescript
const queryClient = useQueryClient();

const prefetchResource = (id: number) => {
  queryClient.prefetchQuery({
    queryKey: QUERY_KEYS.resourceDetail(id),
    queryFn: () => fetchResource(id),
  });
};
```

## Security Considerations

### Authentication

All authenticated endpoints automatically include the Bearer token via axios interceptors (already implemented in `axios-client.ts`).

### Token Refresh

Token refresh is handled automatically by the axios interceptor (already implemented in `token-refresh.ts`).

### CORS

CORS is configured on the backend to allow requests from the frontend origin (already configured in `ta-prep-lsp-be/config/cors.php`).

## Monitoring and Logging

### React Query DevTools

Enable React Query DevTools in development for debugging:

```typescript
import { ReactQueryDevtools } from '@tanstack/react-query-devtools';

<QueryClientProvider client={queryClient}>
  <App />
  <ReactQueryDevtools initialIsOpen={false} />
</QueryClientProvider>
```

### Error Logging

Consider adding error logging for production:

```typescript
const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      onError: (error) => {
        // Log to error tracking service
        console.error('Query error:', error);
      },
    },
    mutations: {
      onError: (error) => {
        // Log to error tracking service
        console.error('Mutation error:', error);
      },
    },
  },
});
```
