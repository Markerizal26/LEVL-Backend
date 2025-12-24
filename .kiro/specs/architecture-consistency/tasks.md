# Implementation Plan

- [x] 1. Set up architecture validation infrastructure
  - Create static analysis tools and testing framework for detecting and validating architectural patterns
  - _Requirements: 1.1, 2.1, 2.2, 5.1, 12.1_

- [x] 1.1 Create ArchitectureValidator class
  - Implement `app/Support/ArchitectureValidator.php` with methods to scan codebase for violations
  - Include methods: `scanForServiceInterfaces()`, `scanForDirectModelQueries()`, `scanForManualAuthorization()`, `scanForMissingRepositories()`
  - _Requirements: 1.1, 2.2, 3.1, 5.1_

- [x] 1.2 Create InterfaceGenerator class
  - Implement `app/Support/InterfaceGenerator.php` to generate interface files from existing classes
  - Include methods: `generateServiceInterface()`, `generateRepositoryInterface()`, `writeInterface()`
  - _Requirements: 1.1, 5.1_

- [x] 1.3 Create RepositoryGenerator class
  - Implement `app/Support/RepositoryGenerator.php` to generate repository classes and interfaces
  - Include methods: `generateRepository()`, `generateRepositoryInterface()`, `writeRepository()`
  - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5, 5.6_

- [x] 1.4 Create RefactoringService class
  - Implement `app/Services/RefactoringService.php` to orchestrate refactoring operations
  - Include methods: `analyzeModule()`, `generateReport()`, `suggestRefactoring()`, `validateRefactoring()`
  - _Requirements: 1.1, 2.1, 5.1_

- [x] 1.5 Create violation and task models
  - Implement `app/Models/ArchitectureViolation.php` and `app/Models/RefactoringTask.php`
  - Define properties and methods as specified in design document
  - _Requirements: 1.1, 2.1_

- [ ]* 1.6 Write property test for static analysis tool
  - **Property 1: Service Interface Implementation**
  - **Validates: Requirements 1.1, 1.4**

- [ ]* 1.7 Write property test for controller purity
  - **Property 3: No Direct Model Queries in Controllers**
  - **Validates: Requirements 2.2, 13.1, 13.2, 13.3, 13.4, 13.5**

- [x] 2. Fix Schemes module architectural violations
  - Refactor Schemes module to follow all architectural patterns consistently
  - _Requirements: 1.1, 1.3, 2.1, 2.2, 3.1, 6.1, 7.1_

- [x] 2.1 Make CourseService implement CourseServiceInterface
  - Update `Modules/Schemes/app/Services/CourseService.php` to implement interface
  - Ensure all interface methods are implemented with correct signatures
  - _Requirements: 1.1, 1.2, 1.4_

- [x] 2.2 Update CourseController to use interface type hints
  - Change constructor parameter from `CourseService` to `CourseServiceInterface`
  - Remove direct `CourseRepository` injection (should go through service)
  - _Requirements: 1.3, 2.1_

- [x] 2.3 Refactor CourseController authorization to use policies
  - Replace manual `Gate::forUser()` checks with `$this->authorize()`
  - Update `unpublish()`, `generateEnrollmentKey()`, `updateEnrollmentKey()`, `removeEnrollmentKey()` methods
  - _Requirements: 3.1, 3.2, 3.3, 3.4_

- [x] 2.4 Add missing CourseService interface methods
  - Implement `verifyEnrollmentKey()`, `generateEnrollmentKey()`, `hasEnrollmentKey()` in CourseService
  - Ensure methods match interface signatures
  - _Requirements: 1.4_

- [x] 2.5 Update CourseService provider bindings
  - Verify `SchemesServiceProvider` binds `CourseServiceInterface` to `CourseService`
  - Ensure binding uses `bind()` not `singleton()` unless stateful
  - _Requirements: 6.1, 6.2, 6.3_

- [ ]* 2.6 Write property test for service interface implementation
  - **Property 1: Service Interface Implementation**
  - **Validates: Requirements 1.1, 1.4**

- [ ]* 2.7 Write property test for controller dependency injection
  - **Property 2: Controller Dependency Injection via Interfaces**
  - **Validates: Requirements 1.3**

- [x] 3. Fix Enrollments module architectural violations
  - Refactor Enrollments module to eliminate business logic in controllers and ensure proper service usage
  - _Requirements: 2.1, 2.2, 2.3, 4.1, 11.1, 11.2, 11.3, 11.4_

- [x] 3.1 Extract course query logic from EnrollmentsController to service
  - Move `indexManaged()` Course query to `EnrollmentService::getManagedEnrollments()`
  - Pass user and filters to service method
  - _Requirements: 2.1, 2.2, 2.3_

- [x] 3.2 Move helper methods from EnrollmentsController to service
  - Move `canModifyEnrollment()` logic to policy or service
  - Move `getCourseManagers()` to service method
  - Move `getCourseUrl()` and `getEnrollmentsUrl()` to URL helper class
  - _Requirements: 11.1, 11.2, 11.3, 11.4_

- [x] 3.3 Create URL helper class for consistent URL generation
  - Implement `app/Support/Helpers/UrlHelper.php`
  - Include methods: `getCourseUrl()`, `getEnrollmentsUrl()`, `getLessonUrl()`
  - Use across all modules that generate frontend URLs
  - _Requirements: 4.3, 11.4_

- [ ]* 3.4 Write property test for controller method complexity
  - **Property 11: Controller Method Complexity**
  - **Validates: Requirements 2.1**

- [ ] 4. Fix Auth module architectural violations
  - Refactor Auth module to eliminate direct model access and move business logic to services
  - _Requirements: 2.1, 2.2, 2.4, 2.5, 2.6, 2.7, 2.8, 2.9_

- [ ] 4.1 Create ProfileAuditLogRepository and interface
  - Implement `Modules/Auth/app/Repositories/ProfileAuditLogRepository.php`
  - Implement `Modules/Auth/app/Contracts/Repositories/ProfileAuditLogRepositoryInterface.php`
  - Include methods: `create()`, `findByUserId()`, `paginate()`
  - _Requirements: 5.2, 5.3, 5.7_

- [ ] 4.2 Create PinnedBadgeRepository and interface
  - Implement `Modules/Auth/app/Repositories/PinnedBadgeRepository.php`
  - Implement `Modules/Auth/app/Contracts/Repositories/PinnedBadgeRepositoryInterface.php`
  - Include methods: `findByUserAndBadge()`, `create()`, `delete()`
  - _Requirements: 5.2, 5.3, 5.7_

- [ ] 4.3 Create PasswordResetTokenRepository and interface
  - Implement `Modules/Auth/app/Repositories/PasswordResetTokenRepository.php`
  - Implement `Modules/Auth/app/Contracts/Repositories/PasswordResetTokenRepositoryInterface.php`
  - Include methods: `create()`, `findByEmail()`, `deleteByEmail()`, `findValidTokens()`
  - _Requirements: 5.2, 5.5, 5.7_

- [ ] 4.4 Refactor AdminProfileController to use repositories
  - Replace direct `ProfileAuditLog::create()` with repository method calls
  - Inject `ProfileAuditLogRepositoryInterface` in constructor
  - _Requirements: 2.1, 2.2, 2.7_

- [ ] 4.5 Refactor ProfileAchievementController to use repositories
  - Replace direct `PinnedBadge::where()` and `::create()` with repository methods
  - Inject `PinnedBadgeRepositoryInterface` in constructor
  - _Requirements: 2.1, 2.2, 2.6_

- [ ] 4.6 Refactor PasswordResetController to use repositories
  - Replace direct `PasswordResetToken::query()` with repository methods
  - Inject `PasswordResetTokenRepositoryInterface` in constructor
  - _Requirements: 2.1, 2.2, 2.9_

- [ ] 4.7 Refactor AuthApiController OAuth logic to service
  - Move User and SocialAccount queries to `AuthService::handleOAuthCallback()`
  - Keep controller focused on HTTP concerns
  - _Requirements: 2.1, 2.2, 2.8_

- [ ] 4.8 Update Auth module service provider bindings
  - Add bindings for new repository interfaces
  - Verify all Auth interfaces are bound
  - _Requirements: 6.1, 6.2, 6.4_

- [ ]* 4.9 Write property test for repository interface implementation
  - **Property 5: Repository Interface Implementation**
  - **Validates: Requirements 5.1**

- [ ]* 4.10 Write property test for repository method naming
  - **Property 10: Repository Method Naming Consistency**
  - **Validates: Requirements 5.7**

- [ ] 5. Fix Search module architectural violations
  - Refactor Search module to use repository pattern for SearchHistory
  - _Requirements: 2.1, 2.2, 2.4, 5.1, 5.2_

- [ ] 5.1 Create SearchHistoryRepository and interface
  - Implement `Modules/Search/app/Repositories/SearchHistoryRepository.php`
  - Implement `Modules/Search/app/Contracts/Repositories/SearchHistoryRepositoryInterface.php`
  - Include methods: `findByUserId()`, `create()`, `deleteById()`, `deleteByUserId()`
  - _Requirements: 5.1, 5.2, 5.7_

- [ ] 5.2 Refactor SearchController to use repository
  - Replace direct `SearchHistory::where()` with repository method calls
  - Inject `SearchHistoryRepositoryInterface` in constructor
  - _Requirements: 2.1, 2.2, 2.4_

- [ ] 5.3 Update Search module service provider bindings
  - Add binding for SearchHistoryRepositoryInterface
  - _Requirements: 6.1, 6.2_

- [ ] 6. Fix Content module architectural violations
  - Refactor Content module to eliminate direct model access in controllers
  - _Requirements: 2.1, 2.2, 2.5_

- [ ] 6.1 Refactor ContentApprovalController to use repositories
  - Replace `News::find()` and `Announcement::find()` with repository methods
  - Create `findContentById()` method in respective repositories
  - _Requirements: 2.1, 2.2, 2.5_

- [ ] 6.2 Refactor ContentStatisticsController to use repositories
  - Replace `News::where('slug', $slug)->firstOrFail()` with repository method
  - Add `findBySlugOrFail()` method to NewsRepository
  - _Requirements: 2.1, 2.2, 2.5_

- [ ] 7. Fix Forums module architectural violations
  - Refactor Forums module to eliminate direct model access and use repository pattern
  - _Requirements: 2.1, 2.2, 13.1, 13.2, 13.3_

- [ ] 7.1 Add findById methods to Thread and Reply repositories
  - Update `ThreadRepositoryInterface` and `ReplyRepositoryInterface`
  - Implement `findById()` and `findByIdOrFail()` methods
  - _Requirements: 5.7, 13.1, 13.2_

- [ ] 7.2 Refactor ThreadController to use repository methods
  - Replace `Thread::find()` with `$this->threadRepository->findById()`
  - Inject `ThreadRepositoryInterface` in constructor if not already
  - _Requirements: 2.1, 2.2, 13.1_

- [ ] 7.3 Refactor ReplyController to use repository methods
  - Replace `Reply::find()` with `$this->replyRepository->findById()`
  - Inject `ReplyRepositoryInterface` in constructor if not already
  - _Requirements: 2.1, 2.2, 13.2_

- [ ] 7.4 Create ReactionRepository and interface
  - Implement `Modules/Forums/app/Repositories/ReactionRepository.php`
  - Implement `Modules/Forums/app/Contracts/Repositories/ReactionRepositoryInterface.php`
  - Include methods: `findByUserAndReactable()`, `create()`, `delete()`
  - _Requirements: 5.1, 5.2, 5.7_

- [ ] 7.5 Refactor ReactionController to use repository
  - Replace `Reaction::where()` with repository method calls
  - Inject `ReactionRepositoryInterface` in constructor
  - _Requirements: 2.1, 2.2, 13.3_

- [ ] 7.6 Update Forums module service provider bindings
  - Add binding for ReactionRepositoryInterface
  - Verify all Forums interfaces are bound
  - _Requirements: 6.1, 6.2_

- [ ] 8. Fix Learning module architectural violations
  - Refactor Learning module to eliminate direct Enrollment queries in controllers
  - _Requirements: 2.1, 2.2, 13.4, 13.5_

- [ ] 8.1 Add enrollment check method to EnrollmentService
  - Implement `EnrollmentService::isUserEnrolledInCourse(int $userId, int $courseId): bool`
  - Implement `EnrollmentService::getActiveEnrollment(int $userId, int $courseId): ?Enrollment`
  - _Requirements: 4.1, 13.4, 13.5_

- [ ] 8.2 Refactor LessonController to use enrollment service
  - Replace direct `Enrollment::where()` with `$enrollmentService->getActiveEnrollment()`
  - Inject `EnrollmentServiceInterface` in constructor
  - _Requirements: 2.1, 2.2, 13.4, 13.5_

- [ ] 9. Fix Enrollments ReportController violations
  - Refactor ReportController to move query logic to services
  - _Requirements: 2.1, 2.2, 2.10_

- [ ] 9.1 Create EnrollmentReportService and interface
  - Implement `Modules/Enrollments/app/Services/EnrollmentReportService.php`
  - Implement `Modules/Enrollments/app/Contracts/Services/EnrollmentReportServiceInterface.php`
  - Include methods: `getCourseStatistics()`, `getEnrollmentReport()`, `getDetailedEnrollments()`
  - _Requirements: 2.10, 14.1_

- [ ] 9.2 Refactor ReportController to use report service
  - Move all Enrollment and CourseProgress queries to EnrollmentReportService
  - Inject `EnrollmentReportServiceInterface` in constructor
  - _Requirements: 2.1, 2.2, 2.10_

- [ ] 9.3 Update Enrollments module service provider bindings
  - Add binding for EnrollmentReportServiceInterface
  - _Requirements: 6.1, 6.2_

- [ ] 10. Fix Gamification module violations
  - Refactor Gamification module to use repository pattern consistently
  - _Requirements: 2.1, 2.2_

- [ ] 10.1 Create UserGamificationStatRepository and interface
  - Implement `Modules/Gamification/app/Repositories/UserGamificationStatRepository.php`
  - Implement `Modules/Gamification/app/Contracts/Repositories/UserGamificationStatRepositoryInterface.php`
  - Include methods: `findByUserId()`, `create()`, `update()`
  - _Requirements: 5.1, 5.2, 5.7_

- [ ] 10.2 Create UserBadgeRepository and interface
  - Implement `Modules/Gamification/app/Repositories/UserBadgeRepository.php`
  - Implement `Modules/Gamification/app/Contracts/Repositories/UserBadgeRepositoryInterface.php`
  - Include methods: `countByUserId()`, `findByUserId()`, `create()`
  - _Requirements: 5.1, 5.2, 5.7_

- [ ] 10.3 Create PointRepository and interface
  - Implement `Modules/Gamification/app/Repositories/PointRepository.php`
  - Implement `Modules/Gamification/app/Contracts/Repositories/PointRepositoryInterface.php`
  - Include methods: `paginateByUserId()`, `create()`, `sumByUserId()`
  - _Requirements: 5.1, 5.2, 5.7_

- [ ] 10.4 Refactor GamificationController to use repositories
  - Replace direct model queries with repository method calls
  - Inject repository interfaces in constructor
  - _Requirements: 2.1, 2.2_

- [ ] 10.5 Update Gamification module service provider bindings
  - Add bindings for new repository interfaces
  - _Requirements: 6.1, 6.2_

- [ ] 11. Standardize DTO usage across modules
  - Ensure consistent DTO usage for data transfer between layers
  - _Requirements: 7.1, 7.2, 7.3, 7.4, 7.5_

- [ ] 11.1 Audit service methods for DTO usage
  - Scan all service methods that accept complex data
  - Identify methods accepting arrays without DTOs
  - _Requirements: 7.1, 7.2_

- [ ] 11.2 Create missing DTOs for service methods
  - Generate DTOs for service methods identified in audit
  - Use Spatie Laravel Data with validation attributes
  - _Requirements: 7.4, 7.5_

- [ ] 11.3 Update service method signatures to use DTOs
  - Change method signatures to accept DTO objects
  - Maintain backward compatibility with array acceptance where needed
  - _Requirements: 7.2, 7.3_

- [ ]* 11.4 Write property test for DTO usage
  - **Property 7: DTO Usage in Service Methods**
  - **Validates: Requirements 7.1, 7.2, 7.3, 7.4**

- [ ] 12. Standardize query building patterns
  - Move all QueryBuilder configuration to services or repositories
  - _Requirements: 9.1, 9.2, 9.3, 9.4, 9.5_

- [ ] 12.1 Audit controllers for QueryBuilder usage
  - Scan all controllers for Spatie QueryBuilder usage
  - Identify QueryBuilder configurations in controllers
  - _Requirements: 9.1, 9.2_

- [ ] 12.2 Move QueryBuilder logic to services
  - Extract QueryBuilder configurations from controllers
  - Create service methods with QueryBuilder logic
  - _Requirements: 9.1, 9.2, 9.3_

- [ ]* 12.3 Write property test for query building location
  - **Property 8: Query Building in Services or Repositories**
  - **Validates: Requirements 9.1, 9.2, 9.3**

- [ ] 13. Standardize configuration access
  - Eliminate direct env() calls outside config files
  - _Requirements: 15.1, 15.2, 15.3, 15.4, 15.5_

- [ ] 13.1 Audit codebase for env() usage
  - Scan all PHP files (excluding config directory) for env() calls
  - Document each env() usage and its purpose
  - _Requirements: 15.1, 15.4_

- [ ] 13.2 Create config entries for environment values
  - Add config entries for all env() values found
  - Provide sensible defaults in config files
  - _Requirements: 15.2, 15.5_

- [ ] 13.3 Replace env() calls with config() calls
  - Update all code to use config() instead of env()
  - Test that configuration still works correctly
  - _Requirements: 15.1, 15.3, 15.4_

- [ ]* 13.4 Write property test for configuration access
  - **Property 9: Configuration Access Pattern**
  - **Validates: Requirements 15.1, 15.2, 15.3, 15.4**

- [ ] 14. Standardize module directory structure
  - Ensure all modules follow consistent directory structure
  - _Requirements: 12.1, 12.2, 12.3, 12.4, 12.5_

- [ ] 14.1 Audit module directory structures
  - Check each module for Contracts/Services and Contracts/Repositories directories
  - Document missing directories
  - _Requirements: 12.1, 12.2_

- [ ] 14.2 Create missing directories in modules
  - Add Contracts/Services and Contracts/Repositories directories where missing
  - Move interfaces to Contracts directory if misplaced
  - _Requirements: 12.1, 12.2, 12.3_

- [ ]* 14.3 Write property test for module structure
  - **Property 12: Module Directory Structure**
  - **Validates: Requirements 12.1, 12.2, 12.3, 12.4, 12.5**

- [ ] 15. Implement comprehensive property-based tests
  - Create property-based tests for all architectural rules
  - _Requirements: All requirements_

- [ ]* 15.1 Write property test for authorization pattern
  - **Property 4: Authorization via Policies**
  - **Validates: Requirements 3.1, 3.2, 3.3, 3.4**

- [ ]* 15.2 Write property test for service provider bindings
  - **Property 6: Service Provider Bindings Completeness**
  - **Validates: Requirements 6.1, 6.2, 6.3, 6.4**

- [ ] 16. Create PHPStan custom rules for architecture enforcement
  - Implement custom PHPStan rules to detect violations during static analysis
  - _Requirements: All requirements_

- [ ] 16.1 Create PHPStan rule for service interface implementation
  - Implement rule to detect services not implementing interfaces
  - Add to phpstan.neon configuration
  - _Requirements: 1.1, 1.4_

- [ ] 16.2 Create PHPStan rule for controller purity
  - Implement rule to detect direct model queries in controllers
  - Detect business logic patterns in controllers
  - _Requirements: 2.1, 2.2_

- [ ] 16.3 Create PHPStan rule for repository pattern
  - Implement rule to detect repositories not implementing interfaces
  - Verify repository method naming conventions
  - _Requirements: 5.1, 5.7_

- [ ] 16.4 Update phpstan.neon with custom rules
  - Register all custom rules in PHPStan configuration
  - Set appropriate error levels
  - _Requirements: All requirements_

- [ ] 17. Create documentation for architectural patterns
  - Document the enforced architectural patterns for team reference
  - _Requirements: All requirements_

- [ ] 17.1 Update ARCHITECTURE.md with enforcement rules
  - Add section on architectural violations and how to avoid them
  - Document property-based tests and their purpose
  - Include examples of correct patterns
  - _Requirements: All requirements_

- [ ] 17.2 Create REFACTORING_GUIDE.md
  - Document step-by-step refactoring process
  - Include before/after examples
  - Provide troubleshooting tips
  - _Requirements: All requirements_

- [ ] 17.3 Create TESTING_ARCHITECTURE.md
  - Document how to run property-based tests
  - Explain what each property test validates
  - Provide guidance on adding new architectural tests
  - _Requirements: All requirements_

- [ ] 18. Final validation and cleanup
  - Run all tests and validation tools to ensure architectural consistency
  - _Requirements: All requirements_

- [ ] 18.1 Run full property-based test suite
  - Execute all property tests with 100+ iterations
  - Verify all properties pass
  - _Requirements: All requirements_

- [ ] 18.2 Run PHPStan with custom rules
  - Execute PHPStan at level 8 with custom architectural rules
  - Fix any remaining violations
  - _Requirements: All requirements_

- [ ] 18.3 Run full application test suite
  - Execute all unit, integration, and feature tests
  - Ensure no functionality broken by refactoring
  - _Requirements: All requirements_

- [ ] 18.4 Generate final architecture report
  - Use ArchitectureValidator to generate comprehensive report
  - Verify zero violations
  - Document any accepted exceptions
  - _Requirements: All requirements_

- [ ] 19. Checkpoint - Ensure all tests pass, ask the user if questions arise
