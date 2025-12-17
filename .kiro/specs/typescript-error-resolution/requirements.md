# Requirements Document

## Introduction

This document defines requirements for resolving TypeScript compilation errors and improving type safety across the frontend application (prep-lsp-fe). The current codebase has 111 TypeScript errors across 15 files, including implicit 'any' types, UI component type mismatches, missing dependencies, and type conflicts. This spec will systematically address these issues to achieve zero TypeScript errors and improve overall code quality.

## Glossary

- **TypeScript Compiler**: The tsc tool that checks TypeScript code for type errors
- **Implicit Any**: When TypeScript cannot infer a type and defaults to 'any' without explicit annotation
- **Type Parameter**: Generic type argument passed to a function or component (e.g., `<TData>`)
- **Base UI**: The @base-ui/react component library used for UI components
- **DND Kit**: The @dnd-kit library suite for drag-and-drop functionality
- **Type Assertion**: Explicitly telling TypeScript what type a value should be
- **Union Type**: A type that can be one of several types (e.g., `string | null`)
- **Type Guard**: A function or condition that narrows down types
- **Index Signature**: A type definition that allows dynamic property access with `[key: string]`

## Requirements

### Requirement 1: Fix Import Errors

**User Story:** As a developer, I want all module imports to resolve correctly, so that the application can compile without import errors.

#### Acceptance Criteria

1. WHEN importing from @tanstack/react-query THEN the system SHALL use the correct package name without typos
2. WHEN importing from @dnd-kit packages THEN the system SHALL have all required @dnd-kit dependencies installed
3. WHEN running TypeScript compiler THEN the system SHALL resolve all module imports successfully
4. WHEN checking package.json THEN the system SHALL include all required dependencies for UI components
5. WHEN importing modules THEN the system SHALL use consistent import paths across the codebase

### Requirement 2: Fix Type Export Conflicts

**User Story:** As a developer, I want to resolve type export conflicts, so that there are no duplicate type definitions causing ambiguity.

#### Acceptance Criteria

1. WHEN exporting types from multiple modules THEN the system SHALL ensure no duplicate named exports exist
2. WHEN the Progress type is exported THEN the system SHALL export it from only one module or use explicit re-exports
3. WHEN importing types THEN the system SHALL resolve to the correct type definition without ambiguity
4. WHEN organizing type exports THEN the system SHALL use a clear naming convention to avoid conflicts
5. WHEN re-exporting types THEN the system SHALL use explicit named exports when conflicts exist

### Requirement 3: Fix Implicit Any Types in Components

**User Story:** As a developer, I want all function parameters to have explicit types, so that TypeScript can catch type errors at compile time.

#### Acceptance Criteria

1. WHEN defining callback parameters THEN the system SHALL provide explicit type annotations
2. WHEN using array methods with callbacks THEN the system SHALL type the callback parameters explicitly
3. WHEN destructuring event objects THEN the system SHALL type the destructured properties
4. WHEN using generic functions THEN the system SHALL provide type parameters to avoid implicit any
5. WHEN TypeScript cannot infer types THEN the system SHALL add explicit type annotations

### Requirement 4: Fix Base UI Component Type Mismatches

**User Story:** As a developer, I want UI components to use correct prop types, so that components work with the latest Base UI library API.

#### Acceptance Criteria

1. WHEN using SelectValue component THEN the system SHALL not pass placeholder prop if unsupported by the library
2. WHEN using SelectContent component THEN the system SHALL use correct prop names (side vs position)
3. WHEN using PopoverTrigger component THEN the system SHALL not pass asChild prop if unsupported
4. WHEN using SortableContent component THEN the system SHALL not pass render prop if unsupported
5. WHEN using onValueChange handlers THEN the system SHALL handle null values in the callback signature

### Requirement 5: Fix Null Handling in Event Handlers

**User Story:** As a developer, I want event handlers to properly handle null values, so that type checking passes and runtime errors are prevented.

#### Acceptance Criteria

1. WHEN Select components emit null values THEN the system SHALL handle null in onValueChange callbacks
2. WHEN filtering null values THEN the system SHALL use type guards to narrow types
3. WHEN assigning values that may be null THEN the system SHALL handle the null case explicitly
4. WHEN using union types with null THEN the system SHALL check for null before using the value
5. WHEN TypeScript expects non-null types THEN the system SHALL filter or provide default values

### Requirement 6: Fix API Module Type Issues

**User Story:** As a developer, I want API mutation hooks to have correct type signatures, so that TypeScript validates API calls properly.

#### Acceptance Criteria

1. WHEN using useApiMutation hooks THEN the system SHALL check if getId property is supported
2. WHEN getId is not supported THEN the system SHALL use alternative approaches for dynamic endpoints
3. WHEN typing mutation data parameters THEN the system SHALL provide explicit type annotations
4. WHEN using custom mutation options THEN the system SHALL ensure options match the hook's type signature
5. WHEN refactoring mutation hooks THEN the system SHALL maintain backward compatibility with existing usage

### Requirement 7: Fix QueryParams Type Compatibility

**User Story:** As a developer, I want query parameter types to be compatible across the codebase, so that all query hooks work consistently.

#### Acceptance Criteria

1. WHEN defining custom query parameter interfaces THEN the system SHALL include index signatures for compatibility
2. WHEN using QueryParams type THEN the system SHALL ensure it has `[key: string]: any` index signature
3. WHEN creating module-specific param types THEN the system SHALL extend or be compatible with QueryParams
4. WHEN passing params to hooks THEN the system SHALL ensure type compatibility without casting
5. WHEN organizing param types THEN the system SHALL maintain consistency across all modules

### Requirement 8: Fix Sortable Component Type Issues

**User Story:** As a developer, I want sortable components to have proper type definitions, so that drag-and-drop functionality works without type errors.

#### Acceptance Criteria

1. WHEN using DND Kit types THEN the system SHALL import correct type definitions from @dnd-kit packages
2. WHEN accessing orientation config THEN the system SHALL type the orientation parameter explicitly
3. WHEN handling drag events THEN the system SHALL type active and over parameters
4. WHEN using getItemValue callback THEN the system SHALL type the item parameter
5. WHEN working with sortable arrays THEN the system SHALL maintain type safety throughout transformations

### Requirement 9: Fix Popover Event Handler Signatures

**User Story:** As a developer, I want popover event handlers to match the library's expected signature, so that components work correctly with Base UI.

#### Acceptance Criteria

1. WHEN using onOpenChange callback THEN the system SHALL provide both open and eventDetails parameters
2. WHEN wrapping library callbacks THEN the system SHALL match the expected function signature
3. WHEN calling optional callbacks THEN the system SHALL pass all required arguments
4. WHEN updating to new library versions THEN the system SHALL update callback signatures accordingly
5. WHEN handling events THEN the system SHALL maintain type safety in event handlers

### Requirement 10: Fix Slider Component Type Issues

**User Story:** As a developer, I want slider components to have correct value types, so that range sliders work properly.

#### Acceptance Criteria

1. WHEN using range sliders THEN the system SHALL type values as `[number, number]` tuples
2. WHEN handling slider value changes THEN the system SHALL distinguish between single and range values
3. WHEN typing onValueChange handlers THEN the system SHALL match the slider's value type
4. WHEN using slider components THEN the system SHALL ensure type compatibility with Base UI
5. WHEN working with slider values THEN the system SHALL maintain type safety in transformations

### Requirement 11: Improve Overall Type Safety

**User Story:** As a developer, I want comprehensive type safety across the codebase, so that TypeScript catches errors before runtime.

#### Acceptance Criteria

1. WHEN running `npx tsc --noEmit` THEN the system SHALL produce zero TypeScript errors
2. WHEN adding new code THEN the system SHALL maintain strict type checking without implicit any
3. WHEN refactoring code THEN the system SHALL preserve or improve type safety
4. WHEN using third-party libraries THEN the system SHALL ensure proper type definitions are available
5. WHEN organizing code THEN the system SHALL follow consistent typing patterns across modules

### Requirement 12: Install Missing Dependencies

**User Story:** As a developer, I want all required dependencies installed, so that imports resolve correctly and the application builds successfully.

#### Acceptance Criteria

1. WHEN using @dnd-kit functionality THEN the system SHALL have @dnd-kit/core installed
2. WHEN using drag modifiers THEN the system SHALL have @dnd-kit/modifiers installed
3. WHEN using sortable features THEN the system SHALL have @dnd-kit/sortable installed
4. WHEN using DND utilities THEN the system SHALL have @dnd-kit/utilities installed
5. WHEN checking dependencies THEN the system SHALL verify all peer dependencies are satisfied

### Requirement 13: Update Hook Utility Types

**User Story:** As a developer, I want API hook utilities to support all necessary options, so that module hooks can use them without type errors.

#### Acceptance Criteria

1. WHEN using useApiMutation THEN the system SHALL support getId option if needed by modules
2. WHEN defining mutation options THEN the system SHALL include all properties used by module hooks
3. WHEN typing hook utilities THEN the system SHALL use proper generic constraints
4. WHEN extending hook options THEN the system SHALL maintain backward compatibility
5. WHEN documenting hook utilities THEN the system SHALL clarify supported options

### Requirement 14: Standardize Type Definitions

**User Story:** As a developer, I want consistent type definitions across the codebase, so that types are predictable and maintainable.

#### Acceptance Criteria

1. WHEN defining interfaces THEN the system SHALL use consistent naming conventions
2. WHEN creating union types THEN the system SHALL include all valid values
3. WHEN exporting types THEN the system SHALL organize them logically by module
4. WHEN using generic types THEN the system SHALL provide meaningful type parameter names
5. WHEN documenting types THEN the system SHALL add JSDoc comments for complex types

### Requirement 15: Validate Type Safety with Tests

**User Story:** As a developer, I want to validate that type fixes don't break existing functionality, so that the application remains stable.

#### Acceptance Criteria

1. WHEN fixing type errors THEN the system SHALL run existing tests to ensure no regressions
2. WHEN updating component props THEN the system SHALL verify components still render correctly
3. WHEN changing API hook types THEN the system SHALL ensure existing API calls still work
4. WHEN refactoring types THEN the system SHALL check that all usages are updated
5. WHEN completing type fixes THEN the system SHALL run full TypeScript compilation successfully
