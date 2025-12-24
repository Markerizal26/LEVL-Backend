# Design Document

## Overview

This design document outlines the architectural refactoring approach for the Laravel LMS application to ensure strict adherence to established design patterns. The refactoring will eliminate architectural inconsistencies, improve code maintainability, reduce duplication, and enforce proper separation of concerns across all modules.

The refactoring will be performed incrementally, module by module, to minimize disruption and allow for continuous testing and validation. The approach focuses on automated detection of violations through static analysis tools and property-based testing to ensure consistency is maintained over time.

## Architecture

### Current Architecture Issues

The current architecture has several layers but they are not consistently applied:

```
Current (Inconsistent):
┌─────────────────────────────────────────────────────────────────┐
│  Request ──► FormRequest ──► Controller ──► Service ──► Response│
│                                    │           │                 │
│                                    │           ▼                 │
│                                    │      Repository             │
│                                    │           │                 │
│                                    ▼           ▼                 │
│                                 Model ◄────── Model              │
│                                                                  │
│  Issues:                                                         │
│  - Controllers query models directly (bypassing services)        │
│  - Services don't implement interfaces                           │
│  - Business logic scattered in controllers                       │
│  - Repositories missing for many models                          │
│  - Authorization logic duplicated                                │
└─────────────────────────────────────────────────────────────────┘
```

### Target Architecture

The target architecture enforces strict layer separation:

```
Target (Consistent):
┌─────────────────────────────────────────────────────────────────┐
│  Request ──► FormRequest ──► Controller ──► ServiceInterface    │
│                                    │              │              │
│                                    │              ▼              │
│                                    │         Service             │
│                                    │              │              │
│                                    │              ▼              │
│                                    │    RepositoryInterface      │
│                                    │              │              │
│                                    │              ▼              │
│                                    │         Repository          │
│                                    │              │              │
│                                    ▼              ▼              │
│                                 Policy ◄────── Model             │
│                                                                  │
│  Enforced Rules:                                                 │
│  - Controllers ONLY call services (via interfaces)               │
│  - Services implement interfaces                                 │
│  - All data access through repositories                          │
│  - Authorization through policies                                │
│  - No business logic in controllers                              │
└─────────────────────────────────────────────────────────────────┘
```

### Layer Responsibilities (Enforced)

**Controller Layer:**
- Validate input via FormRequest
- Convert request to DTO
- Call Service methods (via interface)
- Authorize via Policy ($this->authorize())
- Return Response via ApiResponse trait
- **FORBIDDEN:** Business logic, database queries, direct model access

**Service Layer:**
- Implement ServiceInterface
- Business logic and rules
- Orchestrate repository calls
- Transaction management
- Return DTO or Model objects
- **FORBIDDEN:** Direct database queries, HTTP concerns

**Repository Layer:**
- Implement RepositoryInterface
- All database queries
- CRUD operations
- Query building with filters/sorts
- **FORBIDDEN:** Business logic, HTTP concerns

**Policy Layer:**
- Authorization logic
- Permission checks
- **FORBIDDEN:** Business logic, data access

## Components and Interfaces

### Static Analysis Tool

A custom static analysis tool will be created to detect architectural violations:

**Purpose:** Scan codebase for pattern violations
**Technology:** PHP-based scanner using Reflection and AST parsing
**Location:** `app/Support/ArchitectureValidator.php`

**Capabilities:**
- Detect services not implementing interfaces
- Find direct model queries in controllers
- Identify business logic in controllers
- Verify repository pattern usage
- Check service provider bindings
- Validate DTO usage
- Detect manual authorization checks
- Find duplicate code patterns

### Refactoring Service

A service to coordinate refactoring operations:

**Purpose:** Orchestrate refactoring tasks
**Location:** `app/Services/RefactoringService.php`

**Methods:**
- `analyzeModule(string $moduleName): array` - Analyze violations in a module
- `generateReport(): array` - Generate comprehensive violation report
- `suggestRefactoring(string $file): array` - Suggest refactoring for a file
- `validateRefactoring(string $file): bool` - Validate refactoring was successful

### Interface Generator

A tool to generate missing interfaces:

**Purpose:** Create interface files from existing classes
**Location:** `app/Support/InterfaceGenerator.php`

**Methods:**
- `generateServiceInterface(string $serviceClass): string` - Generate service interface
- `generateRepositoryInterface(string $repositoryClass): string` - Generate repository interface
- `writeInterface(string $path, string $content): void` - Write interface file

### Repository Generator

A tool to generate missing repositories:

**Purpose:** Create repository classes for models
**Location:** `app/Support/RepositoryGenerator.php`

**Methods:**
- `generateRepository(string $modelClass): string` - Generate repository class
- `generateRepositoryInterface(string $modelClass): string` - Generate repository interface
- `writeRepository(string $path, string $content): void` - Write repository files

## Data Models

### Violation Report Model

```php
class ArchitectureViolation
{
    public string $type;              // Type of violation
    public string $file;              // File path
    public int $line;                 // Line number
    public string $description;       // Human-readable description
    public string $suggestion;        // Suggested fix
    public string $severity;          // critical|high|medium|low
    public array $context;            // Additional context
}
```

### Refactoring Task Model

```php
class RefactoringTask
{
    public string $module;            // Module name
    public string $type;              // Type of refactoring
    public string $targetFile;        // File to refactor
    public array $violations;         // List of violations
    public string $status;            // pending|in_progress|completed
    public ?string $newFile;          // New file created (if any)
}
```

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system-essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property 1: Service Interface Implementation

*For any* service class in the codebase, that service class should implement its corresponding service interface
**Validates: Requirements 1.1, 1.4**

### Property 2: Controller Dependency Injection via Interfaces

*For any* controller constructor parameter that is a service, the type hint should be an interface, not a concrete class
**Validates: Requirements 1.3**

### Property 3: No Direct Model Queries in Controllers

*For any* controller method, the method body should not contain direct model queries (Model::query(), Model::where(), Model::find(), Model::create())
**Validates: Requirements 2.2, 13.1, 13.2, 13.3, 13.4, 13.5**

### Property 4: Authorization via Policies

*For any* controller method that requires authorization, the method should use $this->authorize() instead of manual Gate checks
**Validates: Requirements 3.1, 3.2, 3.3, 3.4**

### Property 5: Repository Interface Implementation

*For any* repository class in the codebase, that repository class should implement its corresponding repository interface
**Validates: Requirements 5.1**

### Property 6: Service Provider Bindings Completeness

*For any* service or repository interface in a module, the module's service provider should contain a binding for that interface
**Validates: Requirements 6.1, 6.2, 6.3, 6.4**

### Property 7: DTO Usage in Service Methods

*For any* service method that accepts complex data, the method should accept a DTO object or have explicit array type hints with documentation
**Validates: Requirements 7.1, 7.2, 7.3, 7.4**

### Property 8: Query Building in Services or Repositories

*For any* usage of Spatie QueryBuilder, the QueryBuilder configuration should be in a service or repository method, not in a controller
**Validates: Requirements 9.1, 9.2, 9.3**

### Property 9: Configuration Access Pattern

*For any* access to environment variables, the code should use config() helper, not env() directly, except in config files
**Validates: Requirements 15.1, 15.2, 15.3, 15.4**

### Property 10: Repository Method Naming Consistency

*For any* repository class, CRUD methods should follow consistent naming: findById(), create(), update(), delete()
**Validates: Requirements 5.7**

### Property 11: Controller Method Complexity

*For any* controller method, the method should not contain business logic (complex conditionals, loops with business rules, calculations)
**Validates: Requirements 2.1**

### Property 12: Module Directory Structure

*For any* module, the module should contain Contracts/Services and Contracts/Repositories directories if it has services or repositories
**Validates: Requirements 12.1, 12.2, 12.3, 12.4, 12.5**

## Error Handling

### Violation Detection Errors

**ViolationDetectionException**
- Thrown when: Static analysis fails to parse a file
- Handling: Log error, skip file, continue analysis
- HTTP Code: N/A (internal tool)

**InvalidModuleException**
- Thrown when: Module structure is invalid
- Handling: Report error, suggest module structure fix
- HTTP Code: N/A (internal tool)

### Refactoring Errors

**RefactoringFailedException**
- Thrown when: Automated refactoring cannot be completed
- Handling: Report error, provide manual refactoring guide
- HTTP Code: N/A (internal tool)

**InterfaceGenerationException**
- Thrown when: Interface cannot be generated from class
- Handling: Log error, provide template for manual creation
- HTTP Code: N/A (internal tool)

### Runtime Errors (After Refactoring)

**BusinessException**
- Thrown when: Business rules are violated
- Handling: Return 422 with error details
- HTTP Code: 422

**ModelNotFoundException**
- Thrown when: Resource not found
- Handling: Return 404 with error message
- HTTP Code: 404

**AuthorizationException**
- Thrown when: Policy authorization fails
- Handling: Return 403 with error message
- HTTP Code: 403

## Testing Strategy

### Unit Testing

Unit tests will verify specific refactoring operations:

**Test Categories:**
1. **Interface Generation Tests**
   - Test generating service interfaces from service classes
   - Test generating repository interfaces from repository classes
   - Test interface method signature extraction

2. **Violation Detection Tests**
   - Test detecting services without interfaces
   - Test detecting direct model queries in controllers
   - Test detecting manual authorization checks
   - Test detecting missing repositories

3. **Refactoring Operation Tests**
   - Test extracting business logic from controllers to services
   - Test creating repository methods from direct queries
   - Test converting manual Gate checks to authorize() calls
   - Test adding interface implementations to services

4. **Service Provider Tests**
   - Test detecting missing bindings
   - Test generating binding code
   - Test validating bindings resolve correctly

### Property-Based Testing

Property-based tests will verify architectural rules hold across the entire codebase. We will use **Pest PHP with Pest Plugin Drift** for property-based testing, as it's already installed in the project.

**Configuration:**
- Each property test will run a minimum of 100 iterations
- Tests will generate random file paths, class names, and code samples
- Tests will use reflection and AST parsing to analyze code structure

**Property Test Categories:**

1. **Service Interface Compliance**
   - Generate random service class names
   - Verify each service implements its interface
   - Verify interface methods match service methods

2. **Controller Purity**
   - Generate random controller file paths
   - Parse controller methods
   - Verify no direct model queries exist
   - Verify no business logic patterns exist

3. **Repository Pattern Compliance**
   - Generate random repository class names
   - Verify each repository implements its interface
   - Verify repositories follow naming conventions

4. **Authorization Pattern Compliance**
   - Generate random controller methods with authorization
   - Verify $this->authorize() is used
   - Verify no manual Gate checks exist

5. **Service Provider Binding Completeness**
   - Generate random module names
   - Extract all interfaces from module
   - Verify all interfaces have bindings in service provider

6. **Configuration Access Pattern**
   - Generate random PHP files (excluding config directory)
   - Scan for env() calls
   - Verify no direct env() usage outside config files

**Property Test Implementation Notes:**
- Each property-based test will be tagged with a comment referencing the design document property
- Format: `// Feature: architecture-consistency, Property X: [property description]`
- Tests will use Pest's `dataset()` feature to generate test cases
- Tests will use PHP Reflection and nikic/php-parser for code analysis

### Integration Testing

Integration tests will verify refactored modules work correctly:

**Test Scenarios:**
1. **End-to-End Controller Tests**
   - Test refactored controllers still handle requests correctly
   - Test authorization still works via policies
   - Test data is correctly retrieved via services

2. **Service Integration Tests**
   - Test services correctly call repositories
   - Test services correctly handle business logic
   - Test services correctly throw exceptions

3. **Repository Integration Tests**
   - Test repositories correctly query database
   - Test repositories correctly handle filters and sorts
   - Test repositories correctly return models

### Regression Testing

After each module refactoring:
1. Run full test suite to ensure no functionality broken
2. Run property-based tests to verify architectural compliance
3. Run static analysis to verify no new violations introduced
4. Manual testing of critical user flows

### Testing Tools

**PHPStan** - Static analysis for type safety and code quality
- Configuration: `phpstan.neon`
- Level: 8 (strictest)
- Custom rules for architectural patterns

**Pest PHP** - Testing framework with property-based testing support
- Plugin: pest-plugin-drift for property-based testing
- Configuration: `tests/Pest.php`

**PHP Parser** - AST parsing for code analysis
- Library: nikic/php-parser
- Used by static analysis tool

**Reflection API** - Runtime class analysis
- Used for interface verification
- Used for method signature extraction
