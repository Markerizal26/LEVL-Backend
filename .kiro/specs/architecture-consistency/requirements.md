# Requirements Document

## Introduction

This document outlines the requirements for refactoring the Laravel LMS application to ensure strict adherence to architectural patterns including Repository Pattern, Service Layer, Interface-based design, and Laravel best practices. The goal is to eliminate architectural inconsistencies, reduce code duplication, and improve maintainability through proper separation of concerns.

## Glossary

- **Controller**: HTTP request handler that delegates to services and returns responses
- **Service**: Business logic layer that orchestrates operations between repositories
- **Repository**: Data access layer that handles database queries and model operations
- **Interface**: Contract defining methods that implementations must provide
- **DTO (Data Transfer Object)**: Type-safe object for transferring data between layers using Spatie Laravel Data
- **Business Logic**: Rules and operations that define how the application works
- **Trait**: Reusable code component that can be included in multiple classes
- **Facade**: Static interface to underlying service classes
- **Policy**: Authorization logic for determining user permissions
- **Service Provider**: Laravel component that binds interfaces to implementations

## Requirements

### Requirement 1

**User Story:** As a developer, I want all services to implement their corresponding interfaces, so that the codebase follows dependency inversion principles and is easier to test and maintain.

#### Acceptance Criteria

1. WHEN a service class is defined THEN the system SHALL ensure it implements its corresponding service interface
2. WHEN CourseService is instantiated THEN the system SHALL verify it implements CourseServiceInterface
3. WHEN a controller depends on a service THEN the system SHALL inject the service via its interface type hint, not the concrete class
4. WHEN service methods are called THEN the system SHALL ensure all interface-defined methods are implemented in the service class
5. WHEN new service methods are added THEN the system SHALL require corresponding interface method declarations

### Requirement 2

**User Story:** As a developer, I want all controllers to be free of business logic and direct database queries, so that business rules are centralized in services and the codebase follows single responsibility principle.

#### Acceptance Criteria

1. WHEN a controller method executes THEN the system SHALL delegate all business logic to service layer methods
2. WHEN a controller needs data THEN the system SHALL call service or repository methods instead of direct model queries
3. WHEN EnrollmentsController.indexManaged executes THEN the system SHALL move the Course query logic to a service method
4. WHEN SearchController accesses SearchHistory THEN the system SHALL move direct model queries to a repository
5. WHEN ContentApprovalController finds models THEN the system SHALL use repository methods instead of Model::find()
6. WHEN ProfileAchievementController manages badges THEN the system SHALL move PinnedBadge queries to a service
7. WHEN AdminProfileController creates audit logs THEN the system SHALL delegate ProfileAuditLog creation to a service
8. WHEN AuthApiController handles OAuth THEN the system SHALL move User and SocialAccount queries to a service
9. WHEN PasswordResetController manages tokens THEN the system SHALL move PasswordResetToken queries to a repository
10. WHEN ReportController calculates statistics THEN the system SHALL move Enrollment and CourseProgress queries to a service

### Requirement 3

**User Story:** As a developer, I want authorization logic to be consistently handled through policies, so that permission checks are centralized and not duplicated across controllers.

#### Acceptance Criteria

1. WHEN a controller method requires authorization THEN the system SHALL use $this->authorize() with policy methods
2. WHEN CourseController.unpublish checks permissions THEN the system SHALL use $this->authorize('update', $course) instead of manual Gate checks
3. WHEN CourseController.generateEnrollmentKey checks permissions THEN the system SHALL use $this->authorize() instead of manual Gate::forUser() checks
4. WHEN any controller checks user roles THEN the system SHALL move complex authorization logic to policy classes
5. WHEN authorization fails THEN the system SHALL throw AuthorizationException automatically via authorize() method

### Requirement 4

**User Story:** As a developer, I want reusable business logic to be extracted into traits or helper classes, so that code duplication is minimized and maintenance is simplified.

#### Acceptance Criteria

1. WHEN multiple controllers check enrollment status THEN the system SHALL provide a shared trait or service method
2. WHEN multiple controllers validate course ownership THEN the system SHALL use the existing ManagesCourse trait consistently
3. WHEN multiple controllers format URLs THEN the system SHALL extract URL generation logic to a helper class
4. WHEN multiple controllers send similar emails THEN the system SHALL create reusable email service methods
5. WHEN multiple controllers handle pagination THEN the system SHALL use consistent pagination patterns via base repository

### Requirement 5

**User Story:** As a developer, I want all repositories to follow the repository pattern consistently, so that data access is standardized and testable.

#### Acceptance Criteria

1. WHEN a repository is created THEN the system SHALL ensure it implements a repository interface
2. WHEN SearchHistory is accessed THEN the system SHALL create SearchHistoryRepository with interface
3. WHEN PinnedBadge is accessed THEN the system SHALL create PinnedBadgeRepository with interface
4. WHEN ProfileAuditLog is accessed THEN the system SHALL create ProfileAuditLogRepository with interface
5. WHEN PasswordResetToken is accessed THEN the system SHALL create PasswordResetTokenRepository with interface
6. WHEN any model is queried THEN the system SHALL route queries through repository methods
7. WHEN repositories perform CRUD operations THEN the system SHALL follow consistent method naming (findById, create, update, delete)

### Requirement 6

**User Story:** As a developer, I want service provider bindings to be complete and consistent, so that dependency injection works correctly throughout the application.

#### Acceptance Criteria

1. WHEN a service interface exists THEN the system SHALL bind it to its implementation in the module's service provider
2. WHEN a repository interface exists THEN the system SHALL bind it to its implementation in the module's service provider
3. WHEN CourseService is bound THEN the system SHALL bind CourseServiceInterface to CourseService, not bind the concrete class directly
4. WHEN controllers are instantiated THEN the system SHALL resolve dependencies via interface bindings
5. WHEN new interfaces are created THEN the system SHALL register corresponding bindings in service providers

### Requirement 7

**User Story:** As a developer, I want DTOs to be used consistently for data transfer between layers, so that data validation and type safety are enforced.

#### Acceptance Criteria

1. WHEN a controller receives request data THEN the system SHALL convert it to a DTO using Spatie Laravel Data
2. WHEN a service method accepts data THEN the system SHALL accept DTO objects or arrays with clear type hints
3. WHEN CourseService.create is called THEN the system SHALL accept CreateCourseDTO or array with proper handling
4. WHEN data is passed between layers THEN the system SHALL use DTOs for complex data structures
5. WHEN DTOs are created THEN the system SHALL include validation rules using Spatie Data attributes

### Requirement 8

**User Story:** As a developer, I want error handling to be consistent across the application, so that exceptions are properly caught and transformed into appropriate HTTP responses.

#### Acceptance Criteria

1. WHEN business rules are violated THEN the system SHALL throw BusinessException with appropriate error messages
2. WHEN models are not found THEN the system SHALL throw ModelNotFoundException
3. WHEN validation fails THEN the system SHALL throw ValidationException
4. WHEN authorization fails THEN the system SHALL throw AuthorizationException
5. WHEN exceptions are thrown THEN the system SHALL handle them in the exception handler and return consistent API responses

### Requirement 9

**User Story:** As a developer, I want query building logic to be centralized in repositories or services, so that complex queries are not duplicated across controllers.

#### Acceptance Criteria

1. WHEN complex queries are needed THEN the system SHALL implement them in repository methods
2. WHEN Spatie QueryBuilder is used THEN the system SHALL configure it in service or repository methods, not controllers
3. WHEN filters and sorts are applied THEN the system SHALL define allowedFilters and allowedSorts in service layer
4. WHEN pagination is needed THEN the system SHALL delegate to repository or service methods
5. WHEN search functionality is needed THEN the system SHALL use repository methods with Scout integration

### Requirement 10

**User Story:** As a developer, I want media handling to be consistent across the application, so that file uploads follow the same pattern everywhere.

#### Acceptance Criteria

1. WHEN controllers handle file uploads THEN the system SHALL use the HandlesMediaUploads trait consistently
2. WHEN services handle media THEN the system SHALL provide dedicated methods for media operations
3. WHEN media is uploaded THEN the system SHALL use Spatie Media Library methods consistently
4. WHEN media is deleted THEN the system SHALL use clearMediaCollection() consistently
5. WHEN media URLs are needed THEN the system SHALL use model accessor methods consistently

### Requirement 11

**User Story:** As a developer, I want helper methods in controllers to be minimized, so that controllers remain thin and focused on HTTP concerns.

#### Acceptance Criteria

1. WHEN controllers need helper methods THEN the system SHALL extract them to traits or service classes
2. WHEN EnrollmentsController uses canModifyEnrollment THEN the system SHALL move it to a policy or service
3. WHEN EnrollmentsController uses getCourseManagers THEN the system SHALL move it to a service method
4. WHEN EnrollmentsController uses getCourseUrl THEN the system SHALL move it to a URL helper class
5. WHEN controllers have private methods THEN the system SHALL evaluate if they belong in services or traits

### Requirement 12

**User Story:** As a developer, I want all modules to follow the same directory structure and naming conventions, so that the codebase is predictable and easy to navigate.

#### Acceptance Criteria

1. WHEN a module is created THEN the system SHALL include Contracts/Repositories and Contracts/Services directories
2. WHEN interfaces are created THEN the system SHALL place them in the Contracts directory with Interface suffix
3. WHEN repositories are created THEN the system SHALL place them in the Repositories directory
4. WHEN services are created THEN the system SHALL place them in the Services directory
5. WHEN DTOs are created THEN the system SHALL place them in the DTOs directory

### Requirement 13

**User Story:** As a developer, I want direct model instantiation and queries in controllers to be eliminated, so that all data operations go through proper layers.

#### Acceptance Criteria

1. WHEN Forum controllers use Thread::find() THEN the system SHALL replace with repository method calls
2. WHEN Forum controllers use Reply::find() THEN the system SHALL replace with repository method calls
3. WHEN Forum controllers use Reaction::where() THEN the system SHALL replace with repository method calls
4. WHEN Lesson controllers check Enrollment status THEN the system SHALL use enrollment service methods
5. WHEN any controller uses Model::query(), Model::where(), Model::find(), or Model::create() THEN the system SHALL refactor to use repository or service methods

### Requirement 14

**User Story:** As a developer, I want service methods to have clear, single responsibilities, so that services are easy to understand and test.

#### Acceptance Criteria

1. WHEN a service method is created THEN the system SHALL ensure it performs one cohesive operation
2. WHEN EnrollmentService.enroll executes THEN the system SHALL keep email sending as a separate concern
3. WHEN services grow large THEN the system SHALL split them into focused service classes
4. WHEN services have many dependencies THEN the system SHALL evaluate if responsibilities should be distributed
5. WHEN service methods exceed 50 lines THEN the system SHALL consider extracting helper methods or sub-services

### Requirement 15

**User Story:** As a developer, I want all configuration and environment-dependent values to be accessed through config files, so that hard-coded values are eliminated.

#### Acceptance Criteria

1. WHEN services need URLs THEN the system SHALL read them from config files, not env() directly
2. WHEN services need timeouts or limits THEN the system SHALL read them from config files
3. WHEN services need feature flags THEN the system SHALL read them from config files
4. WHEN environment values are needed THEN the system SHALL access them via config() helper, not env() directly
5. WHEN new configuration is added THEN the system SHALL document it in config files with sensible defaults
