# Design Document: TypeScript Error Resolution and Type Safety

## Overview

This design document outlines the systematic approach to resolving 111 TypeScript compilation errors across 15 files in the prep-lsp-fe codebase. The errors fall into distinct categories: import errors, type mismatches with UI libraries, implicit 'any' types, null handling issues, and type export conflicts. Each category requires specific solutions that maintain code functionality while achieving full type safety.

The resolution strategy prioritizes fixing foundational issues first (imports, dependencies) before addressing component-level type issues, ensuring that each fix builds on a stable foundation.

## Architecture

### Error Categories and Priority

```
Priority 1: Critical Infrastructure
├── Import errors (blocks compilation)
├── Missing dependencies (blocks compilation)
└── Type export conflicts (causes ambiguity)

Priority 2: Type Safety Foundation
├── Hook utility type definitions
├── QueryParams compatibility
└── Common type interfaces

Priority 3: Component Type Fixes
├── Base UI component props
├── Event handler signatures
├── Null handling in callbacks
└── Implicit 'any' annotations

Priority 4: Validation
├── Full TypeScript compilation
├── Existing test suite
└── Runtime verification
```

### File Organization

```
prep-lsp-fe/
├── components/
│   ├── data-table/
│   │   ├── data-table-filter-list.tsx      # 11 errors
│   │   ├── data-table-filter-menu.tsx      # 4 errors
│   │   ├── data-table-pagination.tsx       # 2 errors
│   │   ├── data-table-slider-filter.tsx    # 1 error
│   │   └── data-table-sort-list.tsx        # 5 errors
│   └── ui/
│       ├── faceted.tsx                      # 1 error
│       └── sortable.tsx                     # 16 errors
├── lib/
│   ├── api/
│   │   ├── hooks/
│   │   │   └── use-api-mutation.ts         # Needs getId support
│   │   ├── modules/
│   │   │   ├── content.ts                  # 19 errors
│   │   │   ├── enrollments.ts              # 14 errors
│   │   │   ├── forums.ts                   # 14 errors
│   │   │   ├── gamification.ts             # 2 errors
│   │   │   ├── learning.ts                 # 6 errors
│   │   │   ├── schemes.ts                  # 14 errors
│   │   │   ├── search.ts                   # 1 error
│   │   │   └── types/
│   │   │       └── index.ts                # 1 error (duplicate export)
│   │   └── types.ts                        # Needs index signature
│   └── parsers.ts                          # Referenced in errors
└── package.json                            # Missing @dnd-kit packages
```

## Components and Interfaces

### 1. Fixed Import Statement

```typescript
// BEFORE (content.ts line 3)
import { useQueryClient } from "@tantml:react-query";

// AFTER
import { useQueryClient } from "@tanstack/react-query";
```

### 2. Updated QueryParams Interface

```typescript
// lib/api/types.ts
export interface QueryParams {
  page?: number;
  perPage?: number;
  search?: string;
  sort?: string;
  order?: "asc" | "desc";
  [key: string]: any; // Add index signature for compatibility
}
```

### 3. Enhanced UseApiMutationOptions

```typescript
// lib/api/hooks/use-api-mutation.ts
export interface UseApiMutationOptions<TData, TVariables> {
  endpoint: string;
  method?: "POST" | "PUT" | "PATCH" | "DELETE";
  getId?: (variables: TVariables) => string | number; // Add getId support
  onSuccess?: (data: TData, variables: TVariables) => void;
  onError?: (error: ApiError) => void;
  // ... other options
}
```

### 4. Fixed Type Export Conflict

```typescript
// lib/api/modules/types/index.ts
export * from "./auth";
export * from "./schemes";
export * from "./enrollments";
export * from "./learning"; // Contains Progress type
export * from "./master-data";
// ... other exports

// Solution: Use explicit re-export to avoid conflict
export type { Progress as LearningProgress } from "./learning";
export type { Progress as SchemeProgress } from "./schemes";
```

### 5. Base UI Component Prop Fixes

```typescript
// SelectValue - Remove placeholder prop
// BEFORE
<SelectValue placeholder={value} />

// AFTER
<SelectValue>{value}</SelectValue>

// SelectContent - Use correct prop name
// BEFORE
<SelectContent side="top">

// AFTER  
<SelectContent placement="top">

// PopoverTrigger - Remove asChild if unsupported
// BEFORE
<PopoverTrigger asChild>

// AFTER
<PopoverTrigger>
```

### 6. Null-Safe Event Handlers

```typescript
// Handle null in onValueChange
// BEFORE
onValueChange={(value: FilterOperator) => handleChange(value)}

// AFTER
onValueChange={(value: FilterOperator | null) => {
  if (value !== null) {
    handleChange(value);
  }
}}

// Or with default value
onValueChange={(value: FilterOperator | null) => {
  handleChange(value ?? defaultOperator);
}}
```

### 7. Explicit Type Annotations

```typescript
// BEFORE
getItemValue={(item) => item.filterId}

// AFTER
getItemValue={(item: ExtendedColumnFilter<TData>) => item.filterId}

// BEFORE
onDragStart({ active }) {

// AFTER
onDragStart({ active }: { active: Active }) {
```

### 8. Fixed Popover Event Handler

```typescript
// BEFORE
onOpenChangeProp?.(newOpen);

// AFTER
onOpenChangeProp?.(newOpen, { source: 'manual' });

// Or create wrapper
const handleOpenChange = (open: boolean, eventDetails: PopoverRoot.ChangeEventDetails) => {
  onOpenChangeProp?.(open);
};
```

### 9. Slider Value Type Fix

```typescript
// BEFORE
onValueChange={(value: RangeValue) => onSliderValueChange(value)}

// AFTER
onValueChange={(value: number | readonly number[]) => {
  if (Array.isArray(value) && value.length === 2) {
    onSliderValueChange(value as [number, number]);
  }
}}
```

### 10. API Module getId Pattern

```typescript
// BEFORE - Using getId (not supported)
useApiMutation({
  endpoint: ENDPOINT,
  getId: (data) => `${data.id}/action`,
});

// AFTER - Use endpoint function or custom implementation
useApiMutation({
  endpoint: `${ENDPOINT}/${data.id}/action`,
  method: "POST",
});

// OR create custom mutation hook
const useCustomAction = () => {
  const queryClient = useQueryClient();
  
  return useMutation({
    mutationFn: async (data: ActionData) => {
      const response = await axiosClient.post(
        `${ENDPOINT}/${data.id}/action`,
        data
      );
      return response.data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: QUERY_KEYS.resource });
    },
  });
};
```

## Data Models

### Type Definitions for Components

```typescript
// components/ui/sortable.tsx
import type { Active, Over } from "@dnd-kit/core";

interface SortableProps<T> {
  value: T[];
  onValueChange: (value: T[]) => void;
  getItemValue: (item: T) => string | number;
  orientation?: "vertical" | "horizontal" | "mixed";
}

interface DragEventHandlers {
  onDragStart: (event: { active: Active }) => void;
  onDragOver: (event: { active: Active; over: Over | null }) => void;
  onDragEnd: (event: { active: Active; over: Over | null }) => void;
  onDragCancel: (event: { active: Active }) => void;
  onDragMove: (event: { active: Active; over: Over | null }) => void;
}
```

### Extended Filter Types

```typescript
// components/data-table/types.ts
export type JoinOperator = "and" | "or";
export type FilterOperator = 
  | "iLike" 
  | "notILike" 
  | "eq" 
  | "ne" 
  | "isEmpty" 
  | "isNotEmpty" 
  | "lt" 
  | "lte" 
  | "gt" 
  | "gte" 
  | "isBetween" 
  | "isRelativeToToday" 
  | "inArray" 
  | "notInArray";

export interface ExtendedColumnFilter<TData> {
  filterId: string;
  id: keyof TData;
  operator: FilterOperator;
  value: string | string[] | null;
}
```

### API Module Type Patterns

```typescript
// Pattern for mutations with dynamic endpoints
export interface PublishData {
  id: number;
  // other fields
}

export function usePublishResource() {
  const queryClient = useQueryClient();
  
  return useMutation({
    mutationFn: async (data: PublishData) => {
      const response = await axiosClient.post(
        `${ENDPOINT}/${data.id}/publish`
      );
      return response.data;
    },
    onSuccess: (_, variables) => {
      queryClient.invalidateQueries({ 
        queryKey: QUERY_KEYS.resourceDetail(variables.id) 
      });
      queryClient.invalidateQueries({ 
        queryKey: QUERY_KEYS.resource 
      });
    },
  });
}
```

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system-essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property 1: Import Resolution
*For any* import statement in the codebase, the imported module SHALL resolve to a valid package or file path without errors.
**Validates: Requirements 1.1, 1.2, 1.3, 1.4, 1.5**

### Property 2: Type Export Uniqueness
*For any* type name exported from the types index file, the type SHALL be exported exactly once or with explicit aliasing to avoid ambiguity.
**Validates: Requirements 2.1, 2.2, 2.3, 2.4, 2.5**

### Property 3: No Implicit Any
*For any* function parameter, variable, or expression in the codebase, TypeScript SHALL be able to infer or have an explicit type annotation without defaulting to implicit 'any'.
**Validates: Requirements 3.1, 3.2, 3.3, 3.4, 3.5**

### Property 4: Component Prop Compatibility
*For any* component from a third-party library, the props passed SHALL match the component's type definition from the library.
**Validates: Requirements 4.1, 4.2, 4.3, 4.4, 4.5**

### Property 5: Null Safety in Handlers
*For any* event handler that receives values from UI components, the handler SHALL explicitly handle null values when the component's type signature includes null.
**Validates: Requirements 5.1, 5.2, 5.3, 5.4, 5.5**

### Property 6: Hook Option Compatibility
*For any* custom hook that wraps a utility hook, the options passed SHALL match the utility hook's type signature.
**Validates: Requirements 6.1, 6.2, 6.3, 6.4, 6.5**

### Property 7: QueryParams Compatibility
*For any* interface used as query parameters, the interface SHALL be assignable to QueryParams type through structural typing.
**Validates: Requirements 7.1, 7.2, 7.3, 7.4, 7.5**

### Property 8: Zero Compilation Errors
*For any* TypeScript compilation run with `npx tsc --noEmit`, the compiler SHALL produce zero errors.
**Validates: Requirements 11.1, 11.2, 11.3, 11.4, 11.5**

## Error Handling

### Compilation Error Handling

- **Import Errors**: Fix typos and install missing packages before proceeding
- **Type Errors**: Address in order of dependency (utilities before components)
- **Runtime Errors**: Verify fixes don't break existing functionality with tests

### Migration Strategy

1. **Phase 1**: Fix blocking errors (imports, dependencies)
2. **Phase 2**: Fix type infrastructure (utilities, common types)
3. **Phase 3**: Fix component errors (UI components, API modules)
4. **Phase 4**: Validate with compilation and tests

### Rollback Plan

- Each fix should be atomic and testable
- If a fix causes runtime issues, revert and use alternative approach
- Maintain git commits per logical fix group for easy rollback

## Testing Strategy

### Compilation Testing

```bash
# Run TypeScript compiler in check mode
npx tsc --noEmit

# Expected result: 0 errors
```

### Component Testing

```typescript
// Verify components still render after prop changes
describe('DataTableFilterList', () => {
  it('should render without type errors', () => {
    render(<DataTableFilterList {...props} />);
    expect(screen.getByRole('list')).toBeInTheDocument();
  });
  
  it('should handle filter changes', () => {
    const onChange = jest.fn();
    render(<DataTableFilterList onChange={onChange} />);
    // Trigger change
    expect(onChange).toHaveBeenCalled();
  });
});
```

### API Hook Testing

```typescript
// Verify API hooks still work after type fixes
describe('usePublishAnnouncement', () => {
  it('should call correct endpoint', async () => {
    const { result } = renderHook(() => usePublishAnnouncement());
    
    await act(async () => {
      result.current.mutate({ announcementId: 1 });
    });
    
    expect(mockAxios.post).toHaveBeenCalledWith(
      '/announcements/1/publish'
    );
  });
});
```

### Integration Testing

- Run existing test suite after each major fix
- Verify no regressions in functionality
- Test edge cases (null values, empty arrays, etc.)

## Implementation Notes

### Priority 1: Critical Infrastructure Fixes

**Files to modify:**
- `prep-lsp-fe/package.json` - Add @dnd-kit packages
- `prep-lsp-fe/lib/api/modules/content.ts` - Fix import typo
- `prep-lsp-fe/lib/api/modules/types/index.ts` - Fix duplicate Progress export

**Commands to run:**
```bash
npm install @dnd-kit/core @dnd-kit/modifiers @dnd-kit/sortable @dnd-kit/utilities
```

### Priority 2: Type Safety Foundation

**Files to modify:**
- `prep-lsp-fe/lib/api/types.ts` - Add index signature to QueryParams
- `prep-lsp-fe/lib/api/hooks/use-api-mutation.ts` - Add getId option support
- `prep-lsp-fe/lib/api/modules/types/search.ts` - Add index signature to SearchQueryParams

### Priority 3: Component Type Fixes

**Data Table Components:**
- `data-table-filter-list.tsx` - Fix 11 errors (getItemValue, onValueChange, SelectValue, etc.)
- `data-table-filter-menu.tsx` - Fix 4 errors (onValueChange, SelectValue)
- `data-table-pagination.tsx` - Fix 2 errors (SelectValue, SelectContent)
- `data-table-slider-filter.tsx` - Fix 1 error (onValueChange)
- `data-table-sort-list.tsx` - Fix 5 errors (getItemValue, onValueChange, PopoverTrigger)

**UI Components:**
- `components/ui/sortable.tsx` - Fix 16 errors (DND Kit types, event handlers)
- `components/ui/faceted.tsx` - Fix 1 error (onOpenChange)

**API Modules:**
- All modules with getId errors - Refactor to use direct endpoint construction or custom mutations

### Specific Fix Patterns

#### Pattern 1: SelectValue Component
```typescript
// Remove placeholder prop, use children instead
<SelectValue>{displayValue}</SelectValue>
```

#### Pattern 2: Null-Safe onValueChange
```typescript
onValueChange={(value: T | null) => {
  if (value !== null) {
    handleChange(value);
  }
}}
```

#### Pattern 3: Typed Callback Parameters
```typescript
// Add explicit type to callback parameter
.map((item: ItemType) => item.id)
```

#### Pattern 4: Event Handler Signatures
```typescript
// Match library's expected signature
onOpenChange={(open: boolean, eventDetails: ChangeEventDetails) => {
  handleChange(open);
}}
```

#### Pattern 5: API Mutations with Dynamic Endpoints
```typescript
// Instead of getId, construct endpoint directly
const mutation = useMutation({
  mutationFn: async (data: MutationData) => {
    return axiosClient.post(`${ENDPOINT}/${data.id}/action`, data);
  },
});
```

### Base UI Library Compatibility

Check Base UI documentation for current API:
- SelectValue: May not support placeholder prop in current version
- SelectContent: Check if using `side` or `placement` prop
- PopoverTrigger: Check if `asChild` is supported
- SortableContent/SortableItem: Check if `render` prop is supported

If props are removed in newer versions, use alternative approaches:
- For SelectValue: Use children instead of placeholder
- For render props: Use component composition instead

## Dependencies

### Required Package Installations

```json
{
  "dependencies": {
    "@dnd-kit/core": "^6.1.0",
    "@dnd-kit/modifiers": "^7.0.0",
    "@dnd-kit/sortable": "^8.0.0",
    "@dnd-kit/utilities": "^3.2.2"
  }
}
```

### Existing Dependencies (Verify Versions)

- `@tanstack/react-query`: Already installed
- `@base-ui/react`: Check version for API compatibility
- `typescript`: Ensure using version 5.x for best type checking

## Performance Considerations

### Compilation Performance

- Type fixes should not impact compilation time significantly
- Adding explicit types may slightly improve compilation speed by reducing inference work

### Runtime Performance

- Type fixes are compile-time only and have zero runtime impact
- Null checks add minimal runtime overhead but prevent crashes

### Development Experience

- Zero TypeScript errors improve IDE performance (faster autocomplete, better IntelliSense)
- Explicit types improve code navigation and refactoring safety

## Security Considerations

### Type Safety as Security

- Proper typing prevents type confusion bugs that could lead to security issues
- Null handling prevents potential null pointer exceptions
- Explicit types make code review easier, catching potential security issues

### No Security Impact

- These changes are purely type-level and don't affect runtime security
- No changes to authentication, authorization, or data validation logic

## Monitoring and Logging

### TypeScript Compilation Monitoring

```bash
# Add to CI/CD pipeline
npm run type-check

# In package.json
{
  "scripts": {
    "type-check": "tsc --noEmit",
    "type-check:watch": "tsc --noEmit --watch"
  }
}
```

### Pre-commit Hook

```bash
# .husky/pre-commit
#!/bin/sh
npm run type-check || exit 1
```

### CI/CD Integration

```yaml
# .github/workflows/type-check.yml
name: Type Check
on: [push, pull_request]
jobs:
  type-check:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - uses: actions/setup-node@v2
      - run: npm ci
      - run: npm run type-check
```
