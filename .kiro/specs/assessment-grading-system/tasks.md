# Implementation Plan: Assessment & Grading System

## Overview

This implementation plan focuses on refactoring and extending the existing Assessment & Grading System in the Laravel LMS. The approach is incremental: review existing code, refactor where needed, and add new features while maintaining backward compatibility.

## Tasks

- [x] 1. Audit and refactor existing models and migrations
  - Review existing Assignment, Submission, and Grade models in Modules/Learning and Modules/Grading
  - Identify gaps between current schema and target design
  - Create migration to add missing fields (polymorphic relationships, state fields, configuration fields)
  - Refactor models to add relationships, casts, and scopes
  - _Requirements: 1.1, 1.2, 1.3, 1.4, 6.1, 6.2, 7.1, 7.2, 8.1, 14.1_

- [ ]* 1.1 Write property test for polymorphic attachment uniqueness
  - **Property 1: Assignment Polymorphic Attachment Uniqueness**
  - **Validates: Requirements 1.1, 1.4**

- [x] 2. Implement Question model and question management
  - [x] 2.1 Create Question model with migrations
    - Create questions table with type, content, options, answer_key, weight, order fields
    - Add relationship to Assignment model
    - Implement question type enum (multiple_choice, checkbox, essay, file_upload)
    - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.8, 4.1_

  - [ ]* 2.2 Write property test for question data round-trip
    - **Property 9: Question Data Round-Trip**
    - **Validates: Requirements 3.8**

  - [ ]* 2.3 Write property test for question weight validation
    - **Property 10: Question Weight Validation**
    - **Validates: Requirements 4.1**

  - [x] 2.4 Create QuestionRepository and QuestionService
    - Implement QuestionRepositoryInterface with CRUD operations
    - Implement QuestionServiceInterface with business logic
    - Add question creation, update, and answer key management
    - _Requirements: 3.8, 4.1_

  - [x] 2.5 Implement question randomization logic
    - Add generateQuestionSet method to QuestionService
    - Support static order, random order, and bank selection
    - Store question_set in submission for audit  
    - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5, 5.6_

  - [ ]* 2.6 Write property test for randomization uniqueness
    - **Property 13: Randomization Produces Unique Question Sets**
    - **Validates: Requirements 5.4**

  - [ ]* 2.7 Write property test for question set persistence
    - **Property 15: Question Set Persistence**
    - **Validates: Requirements 5.6**

- [x] 3. Implement Answer model and answer storage
  - [x] 3.1 Create Answer model with migrations
    - Create answers table with submission_id, question_id, content, selected_options, file_paths, score, feedback fields
    - Add relationships to Submission and Question models
    - Implement answer type handling for different question types
    - _Requirements: 3.5, 3.6, 3.7, 3.8_

  - [x] 3.2 Implement file upload handling for File Upload questions
    - Use Laravel's storage system for secure file storage
    - Implement file type and size validation
    - Store file paths in Answer model
    - _Requirements: 18.1, 18.2, 18.3, 18.4, 18.7_

  - [ ]* 3.3 Write property test for file type validation
    - **Property 56: File Upload Type Validation**
    - **Validates: Requirements 18.2**

  - [ ]* 3.4 Write property test for file size validation
    - **Property 57: File Upload Size Validation**
    - **Validates: Requirements 18.3**

  - [ ]* 3.5 Write property test for file storage and retrieval
    - **Property 58: File Storage and Retrieval**
    - **Validates: Requirements 18.4, 18.6**

- [x] 4. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [x] 5. Implement prerequisite checking system
  - [x] 5.1 Create prerequisite relationship in Assignment model
    - Add many-to-many relationship for prerequisites
    - Create assignment_prerequisites pivot table
    - _Requirements: 2.1_

  - [x] 5.2 Implement hierarchical prerequisite checking logic
    - Add checkPrerequisites method to AssignmentService
    - Implement scope-based checking (Lesson, Unit, Course)
    - Return list of incomplete prerequisites
    - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.6_

  - [ ]* 5.3 Write property test for hierarchical prerequisite enforcement
    - **Property 3: Hierarchical Prerequisite Enforcement**
    - **Validates: Requirements 2.1, 2.2, 2.3, 2.4**

  - [ ]* 5.4 Write property test for prerequisite acyclicity
    - **Property 4: Prerequisite Acyclicity**
    - **Validates: Requirements 2.5**

  - [ ]* 5.5 Write property test for incomplete prerequisites reporting
    - **Property 5: Incomplete Prerequisites Reporting**
    - **Validates: Requirements 2.6**

- [x] 6. Implement submission state machine
  - [x] 6.1 Add state field and state transition logic to Submission model
    - Add state enum (in_progress, submitted, auto_graded, pending_manual_grading, graded, released)
    - Implement transitionState method with validation
    - Add state transition events
    - _Requirements: 9.1, 9.2, 9.3, 9.4, 9.5, 9.6, 9.7_

  - [ ]* 6.2 Write property test for valid state transitions
    - **Property 24: Valid State Transitions Only**
    - **Validates: Requirements 9.1-9.7**

  - [x] 6.3 Refactor SubmissionService to use state machine
    - Update startSubmission, submitAnswers methods to use state transitions
    - Add prerequisite, deadline, and attempt limit checks
    - _Requirements: 6.3, 6.4, 7.3, 7.4, 8.3_

  - [ ]* 6.4 Write property test for deadline tolerance acceptance
    - **Property 16: Deadline Tolerance Window Acceptance**
    - **Validates: Requirements 6.3, 6.6**

  - [ ]* 6.5 Write property test for post-tolerance rejection
    - **Property 17: Post-Tolerance Rejection**
    - **Validates: Requirements 6.4**

  - [ ]* 6.6 Write property test for maximum attempts enforcement
    - **Property 18: Maximum Attempts Enforcement**
    - **Validates: Requirements 7.3, 7.6**

  - [ ]* 6.7 Write property test for cooldown period enforcement
    - **Property 19: Cooldown Period Enforcement**
    - **Validates: Requirements 7.4**

  - [ ]* 6.8 Write property test for re-take mode single submission limit
    - **Property 20: Re-Take Mode Single Submission Limit**
    - **Validates: Requirements 8.3**

- [x] 7. Implement auto-grading system
  - [x] 7.1 Create grading strategy interfaces and implementations
    - Create GradingStrategyInterface
    - Implement MultipleChoiceGradingStrategy
    - Implement CheckboxGradingStrategy
    - Implement ManualGradingStrategy (returns null)
    - _Requirements: 3.5, 3.6, 3.7_

  - [ ]* 7.2 Write property test for MCQ auto-grading correctness
    - **Property 6: Multiple Choice Auto-Grading Correctness**
    - **Validates: Requirements 3.5**

  - [ ]* 7.3 Write property test for checkbox auto-grading set equality
    - **Property 7: Checkbox Auto-Grading Set Equality**
    - **Validates: Requirements 3.6**

  - [ ]* 7.4 Write property test for manual question grading state
    - **Property 8: Manual Question Grading State**
    - **Validates: Requirements 3.7**

  - [x] 7.5 Implement GradingService with auto-grading logic
    - Create GradingService and GradingRepository
    - Implement autoGrade method using grading strategies
    - Implement calculateScore method with weighted average formula
    - Update submission state after auto-grading
    - _Requirements: 3.5, 3.6, 3.7, 4.2, 4.4, 23.1, 23.2_

  - [ ]* 7.6 Write property test for weighted score calculation
    - **Property 11: Weighted Score Calculation Correctness**
    - **Validates: Requirements 4.2, 4.3, 4.4, 12.3**

  - [ ]* 7.7 Write property test for grading uses submission question set
    - **Property 14: Grading Uses Submission Question Set**
    - **Validates: Requirements 5.5**

  - [ ]* 7.8 Write property test for auto-grading score updates
    - **Property 67: Auto-Grading Score Updates**
    - **Validates: Requirements 23.1**

- [x] 8. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [x] 9. Implement manual grading workflow
  - [x] 9.1 Extend GradingService with manual grading methods
    - Implement manualGrade method for instructor grading
    - Implement saveDraftGrade method for partial grading
    - Implement getGradingQueue method with filters
    - Add validation for complete grading before finalization
    - _Requirements: 10.1, 10.2, 10.3, 10.4, 10.6, 11.1, 11.2, 11.3, 11.4, 11.5, 12.1, 12.2_

  - [ ]* 9.2 Write property test for manual grading queue inclusion
    - **Property 26: Manual Grading Queue Inclusion**
    - **Validates: Requirements 10.1**

  - [ ]* 9.3 Write property test for grading queue ordering
    - **Property 27: Grading Queue Chronological Ordering**
    - **Validates: Requirements 10.2**

  - [ ]* 9.4 Write property test for grading queue filters
    - **Property 28: Grading Queue Filter Correctness**
    - **Validates: Requirements 10.3**

  - [ ]* 9.5 Write property test for draft grade persistence
    - **Property 31: Draft Grade Persistence Without State Change**
    - **Validates: Requirements 11.1, 11.2**

  - [ ]* 9.6 Write property test for draft grade round-trip
    - **Property 32: Draft Grade Round-Trip**
    - **Validates: Requirements 11.3**

  - [ ]* 9.7 Write property test for finalization validation
    - **Property 33: Finalization Requires Complete Grading**
    - **Validates: Requirements 11.4**

  - [ ]* 9.8 Write property test for manual question score range validation
    - **Property 35: Manual Question Score Range Validation**
    - **Validates: Requirements 12.1**

  - [x] 9.9 Implement feedback system
    - Add feedback fields to Answer and Grade models
    - Support rich text (HTML) feedback
    - Implement review mode visibility logic
    - _Requirements: 13.1, 13.2, 13.5, 14.2, 14.3, 14.4, 14.5_

  - [ ]* 9.10 Write property test for feedback persistence
    - **Property 37: Feedback Persistence**
    - **Validates: Requirements 13.1, 13.2**

  - [ ]* 9.11 Write property test for review mode visibility rules
    - **Property 39: Review Mode Visibility Rules**
    - **Validates: Requirements 14.2, 14.3, 14.4**

- [x] 10. Implement grade override and answer key recalculation
  - [x] 10.1 Add grade override functionality to GradingService
    - Implement overrideGrade method
    - Store original score and override score separately
    - Require reason for override
    - _Requirements: 16.1, 16.2, 16.3_

  - [ ]* 10.2 Write property test for grade override requires justification
    - **Property 47: Grade Override Requires Justification**
    - **Validates: Requirements 16.2**

  - [ ]* 10.3 Write property test for original score preservation
    - **Property 48: Original Score Preservation on Override**
    - **Validates: Requirements 16.3**

  - [x] 10.4 Implement answer key change recalculation
    - Add updateAnswerKey method to QuestionService
    - Queue background job for grade recalculation
    - Identify affected submissions and recalculate auto-graded questions
    - Preserve manual grades during recalculation
    - _Requirements: 15.1, 15.2, 15.3, 15.6_

  - [ ]* 10.5 Write property test for answer key change identifies affected submissions
    - **Property 42: Answer Key Change Identifies Affected Submissions**
    - **Validates: Requirements 15.1**

  - [ ]* 10.6 Write property test for answer key recalculation
    - **Property 43: Answer Key Change Triggers Auto-Grade Recalculation**
    - **Validates: Requirements 15.2, 15.3**

  - [ ]* 10.7 Write property test for manual grades preserved during recalculation
    - **Property 46: Manual Grades Preserved During Recalculation**
    - **Validates: Requirements 15.6**

  - [ ]* 10.8 Write property test for weight change triggers recalculation
    - **Property 12: Weight Change Triggers Recalculation**
    - **Validates: Requirements 4.5**

- [x] 11. Implement highest score logic and course grade calculation
  - [x] 11.1 Add highest score selection to SubmissionService
    - Implement getHighestScoreSubmission method
    - Update course grade calculation to use highest scores
    - Trigger course grade recalculation on new high scores
    - _Requirements: 8.4, 22.1, 22.2, 22.4, 22.5_

  - [ ]* 11.2 Write property test for highest score selection
    - **Property 21: Highest Score Selection**
    - **Validates: Requirements 8.4, 22.1, 22.2**

  - [ ]* 11.3 Write property test for new high score updates final grade
    - **Property 22: New High Score Updates Final Grade**
    - **Validates: Requirements 22.4**

  - [ ]* 11.4 Write property test for assignment score change cascades
    - **Property 23: Assignment Score Change Cascades to Course Grade**
    - **Validates: Requirements 22.5**

- [x] 12. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [x] 13. Implement appeal system
  - [x] 13.1 Create Appeal model and migrations
    - Create appeals table with submission_id, student_id, reason, status, decision_reason fields
    - Add relationships to Submission and User models
    - _Requirements: 17.1, 17.2_

  - [x] 13.2 Create AppealService with appeal workflow
    - Implement submitAppeal method with validation
    - Implement approveAppeal and denyAppeal methods
    - Grant deadline extension on approval
    - _Requirements: 17.1, 17.2, 17.3, 17.4, 17.5_

  - [ ]* 13.3 Write property test for appeal requires reason
    - **Property 51: Appeal Requires Reason**
    - **Validates: Requirements 17.2**

  - [ ]* 13.4 Write property test for approved appeal grants access
    - **Property 53: Approved Appeal Grants Submission Access**
    - **Validates: Requirements 17.4**

- [x] 14. Implement instructor override system
  - [x] 14.1 Create Override model and migrations
    - Create overrides table with assignment_id, student_id, type, reason, value fields
    - Add relationships to Assignment and User models
    - _Requirements: 24.1, 24.2, 24.3, 24.4_

  - [x] 14.2 Extend AssignmentService with override functionality
    - Implement grantOverride method for prerequisites, attempts, deadlines
    - Check for overrides in prerequisite and attempt limit checks
    - Require reason for all overrides
    - _Requirements: 24.1, 24.2, 24.3, 24.4_

  - [ ]* 14.3 Write property test for instructor override functionality
    - **Property 70: Instructor Override Functionality**
    - **Validates: Requirements 24.1, 24.2, 24.3, 24.4**

- [x] 15. Implement audit logging system
  - [x] 15.1 Create AuditLog model and migrations
    - Create audit_logs table with action, actor_id, actor_type, subject_id, subject_type, context fields
    - Make model append-only (prevent updates and deletes)
    - _Requirements: 20.6_

  - [x] 15.2 Create AuditService with logging methods
    - Implement logging methods for all critical operations
    - Log submission creation, state transitions, grading, answer key changes, overrides, appeals
    - Store context data as JSON
    - _Requirements: 20.1, 20.2, 20.3, 20.4, 20.5_

  - [ ]* 15.3 Write property test for state transition audit logging
    - **Property 25: State Transition Audit Logging**
    - **Validates: Requirements 9.8**

  - [ ]* 15.4 Write property test for comprehensive audit logging
    - **Property 64: Comprehensive Audit Logging**
    - **Validates: Requirements 20.1-20.5, 20.7**

  - [ ]* 15.5 Write property test for audit log immutability
    - **Property 65: Audit Log Immutability**
    - **Validates: Requirements 20.6**

  - [x] 15.6 Integrate audit logging throughout the system
    - Add audit logging to all service methods
    - Use Laravel events to trigger audit logging
    - Implement search and filtering for audit logs
    - _Requirements: 20.7_

- [x] 16. Implement notification system
  - [x] 16.1 Create notification classes for grading events
    - Create SubmissionGradedNotification
    - Create GradesReleasedNotification
    - Create ManualGradingRequiredNotification
    - Create AppealSubmittedNotification
    - Create AppealDecisionNotification
    - Create GradeRecalculatedNotification
    - _Requirements: 21.1, 21.2, 21.3, 21.4, 21.5, 21.6_

  - [x] 16.2 Create NotificationService to dispatch notifications
    - Implement notification methods for all events
    - Queue notifications for async delivery
    - Support email and in-app notification channels
    - _Requirements: 21.1, 21.2, 21.3, 21.4, 21.5, 21.6_

  - [ ]* 16.3 Write property test for event-driven notifications
    - **Property 66: Event-Driven Notifications**
    - **Validates: Requirements 21.1-21.6**

  - [x] 16.4 Integrate notifications throughout the system
    - Trigger notifications from service methods
    - Use Laravel events to decouple notification logic
    - _Requirements: 14.6, 15.5, 17.3, 17.5_

  - [ ]* 16.5 Write property test for deferred release notification
    - **Property 41: Deferred Release Triggers Notification**
    - **Validates: Requirements 14.6**

- [x] 17. Implement file retention and cleanup
  - [x] 17.1 Add file retention logic to Answer model
    - Add retention_period configuration
    - Implement markExpiredFiles method
    - Create cleanup command for expired files
    - _Requirements: 19.1, 19.2, 19.3, 19.4, 19.5_

  - [ ]* 17.2 Write property test for expired file marking
    - **Property 61: Expired File Marking**
    - **Validates: Requirements 19.2**

  - [ ]* 17.3 Write property test for metadata preservation
    - **Property 62: Metadata Preservation After File Deletion**
    - **Validates: Requirements 19.4**

  - [ ]* 17.4 Write property test for deleted file access error
    - **Property 63: Deleted File Access Error**
    - **Validates: Requirements 19.5**

- [x] 18. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [x] 19. Implement bulk operations
  - [x] 19.1 Add bulk operations to GradingService
    - Implement bulkReleaseGrades method
    - Implement bulkApplyFeedback method
    - Add validation before bulk operations
    - _Requirements: 26.2, 26.4, 26.5_

  - [ ]* 19.2 Write property test for bulk grade release
    - **Property 73: Bulk Grade Release**
    - **Validates: Requirements 26.2**

  - [ ]* 19.3 Write property test for bulk feedback application
    - **Property 74: Bulk Feedback Application**
    - **Validates: Requirements 26.4**

  - [ ]* 19.4 Write property test for bulk operation validation
    - **Property 75: Bulk Operation Validation**
    - **Validates: Requirements 26.5**

- [x] 20. Implement search and filtering
  - [x] 20.1 Configure Laravel Scout with Meilisearch for Submission model
    - Add Searchable trait to Submission model
    - Define toSearchableArray method
    - Configure Meilisearch indexes
    - _Requirements: 27.5_

  - [x] 20.2 Implement search and filter methods in SubmissionRepository
    - Add search method using Scout
    - Add filter methods for state, score range, date range
    - Return results with metadata and sorting
    - _Requirements: 27.1, 27.2, 27.3, 27.4, 27.6_

  - [ ]* 20.3 Write property test for search result correctness
    - **Property 76: Search Result Correctness**
    - **Validates: Requirements 27.1-27.4**

  - [ ]* 20.4 Write property test for search result metadata
    - **Property 77: Search Result Metadata Completeness**
    - **Validates: Requirements 27.6**

- [x] 21. Implement assignment duplication
  - [x] 21.1 Add duplication method to AssignmentService
    - Implement duplicateAssignment method
    - Copy all questions, settings, and configurations
    - Generate new ID, exclude submissions
    - _Requirements: 25.1, 25.2, 25.4_

  - [ ]* 21.2 Write property test for assignment duplication completeness
    - **Property 72: Assignment Duplication Completeness**
    - **Validates: Requirements 25.1, 25.2, 25.4**

- [x] 22. Refactor and create API controllers
  - [x] 22.1 Refactor existing AssignmentController
    - Review and improve existing endpoints
    - Add new endpoints for question management
    - Add prerequisite checking endpoint
    - Add override granting endpoint
    - Use service layer for all business logic
    - _Requirements: 1.1, 2.1, 24.1, 25.1_

  - [x] 22.2 Refactor existing SubmissionController
    - Review and improve existing endpoints
    - Add endpoints for submission workflow (start, submit, check deadline)
    - Add attempt limit checking endpoint
    - Use service layer for all business logic
    - _Requirements: 6.3, 6.4, 7.3, 7.4, 8.3_

  - [x] 22.3 Create or refactor GradingController
    - Add endpoints for auto-grading and manual grading
    - Add grading queue endpoint with filters
    - Add draft grade saving endpoint
    - Add grade override endpoint
    - Use service layer for all business logic
    - _Requirements: 10.1, 11.1, 16.1_

  - [x] 22.4 Create AppealController
    - Add endpoints for appeal submission, approval, denial
    - Add pending appeals endpoint for instructors
    - Use service layer for all business logic
    - _Requirements: 17.1, 17.4, 17.5_

  - [x] 22.5 Create AuditLogController
    - Add endpoint for audit log search and filtering
    - Restrict access to administrators
    - Use service layer for all business logic
    - _Requirements: 20.7_

- [x] 23. Implement performance optimizations
  - [x] 23.1 Add database indexes
    - Create indexes on frequently queried fields
    - Add composite indexes for common query patterns
    - _Requirements: 28.4_

  - [x] 23.2 Implement eager loading throughout the system
    - Add eager loading to all repository methods
    - Prevent N+1 query problems
    - _Requirements: 28.5_

  - [x] 23.3 Implement caching strategy
    - Cache assignment configurations
    - Cache question data
    - Cache student rosters
    - Implement cache invalidation on updates
    - _Requirements: 28.7, 28.10_

  - [x] 23.4 Implement background job processing
    - Queue grade recalculation jobs
    - Queue notification jobs
    - Queue bulk operation jobs
    - Configure queue workers
    - _Requirements: 28.6_

- [ ] 24. Write integration tests for complete workflows
  - [ ]* 24.1 Write integration test for complete submission and grading workflow
    - Test end-to-end: start submission → submit answers → auto-grade → manual grade → release
    - Verify state transitions, score calculations, notifications, audit logs

  - [ ]* 24.2 Write integration test for prerequisite enforcement workflow
    - Test prerequisite checking across different scopes
    - Verify access denial and prerequisite reporting

  - [ ]* 24.3 Write integration test for appeal workflow
    - Test appeal submission → instructor decision → access grant/denial
    - Verify notifications and audit logs

  - [ ]* 24.4 Write integration test for answer key recalculation workflow
    - Test answer key change → background job → grade recalculation → notifications
    - Verify score updates and audit logs

- [ ] 25. Write unit tests for edge cases and error conditions
  - [ ]* 25.1 Write unit tests for deadline edge cases
    - Test exact deadline, within tolerance, after tolerance
    - Test timezone handling

  - [ ]* 25.2 Write unit tests for attempt limit edge cases
    - Test at limit, over limit, with cooldown
    - Test with overrides

  - [ ]* 25.3 Write unit tests for grading edge cases
    - Test empty submission, all correct, all wrong, partial credit
    - Test mixed auto and manual questions

  - [ ]* 25.4 Write unit tests for error conditions
    - Test invalid state transitions
    - Test authorization failures
    - Test validation errors

- [ ] 26. Performance testing and optimization
  - [ ]* 26.1 Test auto-grading performance with 50 questions
    - Verify completion within 2 seconds
    - _Requirements: 28.1_

  - [ ]* 26.2 Test grading queue loading with 1000 submissions
    - Verify display within 1 second using pagination
    - _Requirements: 28.2_

  - [ ]* 26.3 Test course grade calculation for 500 students
    - Verify completion within 5 seconds
    - _Requirements: 28.3_

- [x] 27. Final checkpoint and documentation
  - Ensure all tests pass (unit, property, integration, performance)
  - Review code for security vulnerabilities
  - Update API documentation
  - Create deployment guide
  - Ask the user if any questions or concerns arise

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation
- Property tests validate universal correctness properties
- Unit tests validate specific examples and edge cases
- Integration tests validate end-to-end workflows
- Performance tests validate scalability requirements
