# Requirements Document: Assessment & Grading System

## Introduction

This document specifies the requirements for a comprehensive Assessment & Grading System for a Laravel-based Learning Management System. The system enables instructors to create assignments with various question types, manage submissions with deadline enforcement, support auto-grading and manual grading workflows, track student attempts, and issue certificates based on performance.

## Glossary

- **Assignment**: An assessable activity that can be attached to a Lesson, Unit, or Course
- **Submission**: A student's attempt at completing an assignment
- **Question**: An individual assessable item within an assignment
- **Answer_Key**: The correct answer(s) for a question used in auto-grading
- **Grade**: The final score and feedback for a submission
- **Attempt**: A single try at completing an assignment
- **Prerequisite**: A required assignment that must be completed before accessing another assignment
- **Review_Mode**: Configuration determining when students can see answers and feedback
- **Tolerance_Window**: Grace period after deadline before submissions are rejected
- **Certificate**: A credential issued upon meeting course completion requirements
- **Grading_Queue**: List of submissions awaiting manual grading by instructors
- **Audit_Log**: Immutable record of system actions for compliance

## Requirements

### Requirement 1: Polymorphic Assignment Attachment

**User Story:** As an instructor, I want to attach assignments to lessons, units, or courses, so that I can assess students at different levels of granularity.

#### Acceptance Criteria

1. THE Assignment SHALL be attachable to exactly one of: Lesson, Unit, or Course
2. WHEN an Assignment is attached to a scope, THE System SHALL store the polymorphic relationship using Laravel's morphTo pattern
3. WHEN retrieving an Assignment, THE System SHALL include the attached scope (Lesson, Unit, or Course)
4. THE System SHALL validate that an Assignment has exactly one parent scope before saving

### Requirement 2: Hierarchical Prerequisite Management

**User Story:** As an instructor, I want to define prerequisite assignments based on scope hierarchy, so that students progress through content in the correct order.

#### Acceptance Criteria

1. WHEN an Assignment has prerequisites, THE System SHALL enforce completion of all prerequisites before allowing access
2. WHEN checking prerequisites for a Lesson assignment, THE System SHALL only check prerequisites within the same Lesson
3. WHEN checking prerequisites for a Unit assignment, THE System SHALL check prerequisites within the same Unit and its Lessons
4. WHEN checking prerequisites for a Course assignment, THE System SHALL check prerequisites across the entire Course
5. THE System SHALL prevent circular prerequisite dependencies
6. WHEN a student attempts to access an Assignment, THE System SHALL return a list of incomplete prerequisites if access is denied

### Requirement 3: Question Type Support with Auto-Grading

**User Story:** As an instructor, I want to create assignments with multiple question types, so that I can assess different types of knowledge and skills.

#### Acceptance Criteria

1. THE System SHALL support Multiple Choice questions with exactly one correct answer
2. THE System SHALL support Checkbox questions with one or more correct answers
3. THE System SHALL support Essay questions requiring manual grading
4. THE System SHALL support File Upload questions requiring manual grading
5. WHEN a Multiple Choice question is answered, THE System SHALL auto-grade by comparing the selected option to the Answer_Key
6. WHEN a Checkbox question is answered, THE System SHALL auto-grade by comparing all selected options to the Answer_Key
7. WHEN an Essay or File Upload question is answered, THE System SHALL mark it as pending manual grading
8. THE System SHALL store question type, content, options (for MCQ/Checkbox), and Answer_Key for each question

### Requirement 4: Weighted Scoring System

**User Story:** As an instructor, I want to assign different weights to questions, so that I can emphasize more important assessment items.

#### Acceptance Criteria

1. WHEN creating a question, THE System SHALL accept a weight value (positive number)
2. WHEN calculating submission score, THE System SHALL compute weighted average: (sum of weighted scores) / (sum of weights)
3. WHEN all questions have equal weight, THE System SHALL produce the same result as simple average
4. THE System SHALL normalize final scores to a 0-100 scale
5. WHEN a question weight is updated, THE System SHALL recalculate all affected submission scores

### Requirement 5: Question Randomization

**User Story:** As an instructor, I want to randomize question order or select from a question bank, so that I can reduce cheating and create varied assessments.

#### Acceptance Criteria

1. THE System SHALL support static question order (default)
2. THE System SHALL support random question order from a fixed set
3. THE System SHALL support random selection from a question bank with specified count
4. WHEN randomization is enabled, THE System SHALL generate a unique question set per submission attempt
5. WHEN grading, THE System SHALL use the specific questions presented to that submission
6. THE System SHALL store the question set used for each submission for audit purposes

### Requirement 6: Deadline Enforcement with Tolerance

**User Story:** As an instructor, I want to enforce assignment deadlines with a configurable grace period, so that I can maintain fairness while accommodating minor delays.

#### Acceptance Criteria

1. WHEN creating an Assignment, THE System SHALL accept a deadline timestamp
2. WHEN creating an Assignment, THE System SHALL accept a tolerance duration in minutes (default: 0)
3. WHEN a student submits after deadline but within tolerance, THE System SHALL accept the submission and mark it as late
4. WHEN a student submits after deadline plus tolerance, THE System SHALL reject the submission
5. THE System SHALL display remaining time to students before deadline
6. THE System SHALL display "late but accepted" status for submissions within tolerance window

### Requirement 7: Attempt Management

**User Story:** As an instructor, I want to control how many times students can attempt assignments and enforce cooldown periods, so that I can prevent gaming the system.

#### Acceptance Criteria

1. WHEN creating an Assignment, THE System SHALL accept maximum attempts allowed (null for unlimited)
2. WHEN creating an Assignment, THE System SHALL accept cooldown period in minutes between attempts
3. WHEN a student starts a new attempt, THE System SHALL verify they have not exceeded maximum attempts
4. WHEN a student starts a new attempt, THE System SHALL verify cooldown period has elapsed since last submission
5. THE System SHALL display remaining attempts and next available attempt time to students
6. WHEN maximum attempts is reached, THE System SHALL prevent new submissions and display appropriate message

### Requirement 8: Re-take Mode Support

**User Story:** As an instructor, I want to enable re-take mode for assignments, so that students can improve their scores through multiple attempts.

#### Acceptance Criteria

1. WHEN creating an Assignment, THE System SHALL accept a re-take mode flag (boolean)
2. WHEN re-take mode is enabled, THE System SHALL allow students to submit multiple attempts within attempt limits
3. WHEN re-take mode is disabled, THE System SHALL allow only one submission per student
4. WHEN calculating final grade with re-take mode, THE System SHALL use the highest score across all attempts
5. THE System SHALL display all attempt scores to students when re-take mode is enabled

### Requirement 9: Submission State Machine

**User Story:** As a system architect, I want submissions to follow a clear state machine, so that the grading workflow is predictable and auditable.

#### Acceptance Criteria

1. WHEN a submission is created, THE System SHALL set state to "in_progress"
2. WHEN a student submits answers, THE System SHALL transition state to "submitted"
3. WHEN auto-grading completes, THE System SHALL transition state to "auto_graded" if no manual grading needed
4. WHEN manual grading is required, THE System SHALL transition state to "pending_manual_grading"
5. WHEN instructor completes grading, THE System SHALL transition state to "graded"
6. WHEN instructor releases grades, THE System SHALL transition state to "released"
7. THE System SHALL prevent invalid state transitions
8. THE System SHALL log all state transitions with timestamp and actor

### Requirement 10: Manual Grading Queue

**User Story:** As an instructor, I want to see all submissions requiring manual grading in a queue, so that I can efficiently grade student work.

#### Acceptance Criteria

1. WHEN submissions require manual grading, THE System SHALL add them to the Grading_Queue
2. THE Grading_Queue SHALL display submissions ordered by submission timestamp (oldest first)
3. THE Grading_Queue SHALL filter by assignment, student, or date range
4. THE Grading_Queue SHALL display submission metadata: student name, assignment title, submission time, questions requiring grading
5. WHEN an instructor starts grading, THE System SHALL mark the submission as "in_review" to prevent duplicate grading
6. THE System SHALL allow instructors to return submissions to queue if needed

### Requirement 11: Partial Grading Support

**User Story:** As an instructor, I want to save grading progress without finalizing, so that I can grade complex submissions over multiple sessions.

#### Acceptance Criteria

1. WHEN grading a submission, THE System SHALL allow instructors to save draft grades
2. WHEN saving draft grades, THE System SHALL store partial scores and feedback without changing submission state
3. WHEN resuming grading, THE System SHALL restore previously saved draft grades
4. WHEN finalizing grading, THE System SHALL validate all required questions have scores
5. THE System SHALL prevent grade release until all questions are graded

### Requirement 12: Partial Credit for Manual Questions

**User Story:** As an instructor, I want to award partial credit for essay and file upload questions, so that I can fairly assess partially correct answers.

#### Acceptance Criteria

1. WHEN grading a manual question, THE System SHALL accept a score between 0 and the question's maximum points
2. THE System SHALL accept decimal scores for fine-grained grading
3. WHEN calculating submission score, THE System SHALL include partial credit in weighted average
4. THE System SHALL display partial credit scores to students in review mode

### Requirement 13: Feedback System

**User Story:** As an instructor, I want to provide feedback on individual questions and overall submissions, so that students can learn from their mistakes.

#### Acceptance Criteria

1. WHEN grading a question, THE System SHALL accept text feedback for that question
2. WHEN grading a submission, THE System SHALL accept overall submission feedback
3. WHEN review mode allows, THE System SHALL display question-level feedback to students
4. WHEN review mode allows, THE System SHALL display overall feedback to students
5. THE System SHALL support rich text formatting in feedback (HTML)

### Requirement 14: Review Mode Configuration

**User Story:** As an instructor, I want to control when students can see answers and feedback, so that I can prevent answer sharing before all students complete the assignment.

#### Acceptance Criteria

1. WHEN creating an Assignment, THE System SHALL accept a review mode: "immediate", "deferred", or "hidden"
2. WHEN review mode is "immediate", THE System SHALL show answers and feedback immediately after submission
3. WHEN review mode is "deferred", THE System SHALL show answers and feedback only after instructor releases them
4. WHEN review mode is "hidden", THE System SHALL never show answers or detailed feedback to students
5. THE System SHALL always show final scores regardless of review mode
6. WHEN instructor releases grades in deferred mode, THE System SHALL notify students

### Requirement 15: Answer Key Changes and Recalculation

**User Story:** As an instructor, I want to update answer keys and have grades recalculated automatically, so that I can correct mistakes without manual regrading.

#### Acceptance Criteria

1. WHEN an instructor updates an Answer_Key, THE System SHALL identify all affected submissions
2. WHEN an Answer_Key changes, THE System SHALL recalculate scores for all auto-graded questions using the new key
3. WHEN recalculation completes, THE System SHALL update submission scores and states
4. THE System SHALL log answer key changes and recalculations in Audit_Log
5. THE System SHALL notify affected students of grade changes
6. WHEN manual grades exist, THE System SHALL preserve them during recalculation

### Requirement 16: Manual Grade Override

**User Story:** As an instructor, I want to manually override final grades, so that I can handle exceptional circumstances and appeals.

#### Acceptance Criteria

1. WHEN viewing a graded submission, THE System SHALL allow instructors to override the final score
2. WHEN overriding a grade, THE System SHALL require a reason/justification
3. WHEN a grade is overridden, THE System SHALL preserve the original calculated score
4. THE System SHALL display both original and override scores to instructors
5. THE System SHALL display only the override score to students
6. THE System SHALL log all grade overrides with reason in Audit_Log

### Requirement 17: Late Submission Appeals

**User Story:** As a student, I want to appeal late submission rejections, so that I can explain extenuating circumstances.

#### Acceptance Criteria

1. WHEN a submission is rejected for lateness, THE System SHALL allow students to submit an appeal
2. WHEN submitting an appeal, THE System SHALL require a reason and optional supporting documents
3. THE System SHALL notify instructors of pending appeals
4. WHEN an instructor approves an appeal, THE System SHALL allow the student to submit despite deadline
5. WHEN an instructor denies an appeal, THE System SHALL notify the student with reason
6. THE System SHALL log all appeals and decisions in Audit_Log

### Requirement 18: File Upload Handling

**User Story:** As a student, I want to upload files as assignment answers, so that I can submit documents, images, and other file types.

#### Acceptance Criteria

1. WHEN a question type is File Upload, THE System SHALL accept file uploads from students
2. THE System SHALL validate file types against instructor-configured allowed types
3. THE System SHALL validate file size against configured maximum (default: 10MB)
4. THE System SHALL store files securely using Laravel's storage system
5. THE System SHALL prevent unauthorized access to uploaded files
6. WHEN an instructor views a submission, THE System SHALL provide secure download links for uploaded files
7. THE System SHALL support multiple file uploads per question if configured

### Requirement 19: File Retention Policies

**User Story:** As a system administrator, I want to enforce file retention policies, so that I can manage storage costs and comply with data regulations.

#### Acceptance Criteria

1. WHEN configuring the system, THE System SHALL accept a file retention period in days
2. WHEN the retention period expires, THE System SHALL mark files for deletion
3. THE System SHALL provide a cleanup command to delete expired files
4. THE System SHALL preserve file metadata and submission records after file deletion
5. WHEN accessing a deleted file, THE System SHALL display "file expired" message

### Requirement 20: Comprehensive Audit Logging

**User Story:** As a compliance officer, I want comprehensive audit logs of all grading activities, so that I can ensure academic integrity and investigate disputes.

#### Acceptance Criteria

1. THE System SHALL log all submission creations, updates, and state transitions
2. THE System SHALL log all grading actions with instructor identity and timestamp
3. THE System SHALL log all answer key changes and recalculations
4. THE System SHALL log all grade overrides with reasons
5. THE System SHALL log all appeals and decisions
6. THE Audit_Log SHALL be immutable (append-only)
7. THE System SHALL provide audit log search and filtering capabilities

### Requirement 21: Notification System Integration

**User Story:** As a user, I want to receive notifications about grading events, so that I stay informed about my progress and responsibilities.

#### Acceptance Criteria

1. WHEN a submission is graded, THE System SHALL notify the student
2. WHEN grades are released in deferred mode, THE System SHALL notify affected students
3. WHEN a submission requires manual grading, THE System SHALL notify assigned instructors
4. WHEN an appeal is submitted, THE System SHALL notify instructors
5. WHEN an appeal is decided, THE System SHALL notify the student
6. THE System SHALL support email and in-app notification channels

### Requirement 22: Highest Score Logic

**User Story:** As a student, I want my highest score across attempts to count as my final grade, so that I can improve my performance through practice.

#### Acceptance Criteria

1. WHEN multiple attempts exist for an assignment, THE System SHALL identify the highest scoring attempt
2. WHEN calculating course grades, THE System SHALL use the highest score for each assignment
3. THE System SHALL display all attempt scores to students with the highest clearly marked
4. WHEN a new attempt scores higher, THE System SHALL update the student's final grade for that assignment
5. THE System SHALL recalculate course grades when assignment scores change

### Requirement 23: Real-Time Score Updates

**User Story:** As a student, I want to see my scores update in real-time as auto-grading completes, so that I get immediate feedback.

#### Acceptance Criteria

1. WHEN auto-grading completes for a question, THE System SHALL update the submission score immediately
2. WHEN all auto-gradable questions are graded, THE System SHALL calculate and display partial score
3. WHEN manual grading completes, THE System SHALL update the final score immediately
4. THE System SHALL use Laravel events to trigger score updates
5. THE System SHALL broadcast score updates to connected clients if using WebSockets

### Requirement 24: Instructor Override Capabilities

**User Story:** As an instructor, I want to override system restrictions for specific students, so that I can accommodate special circumstances.

#### Acceptance Criteria

1. WHEN viewing a student's assignment access, THE System SHALL allow instructors to override prerequisite requirements
2. WHEN viewing a student's attempts, THE System SHALL allow instructors to grant additional attempts
3. WHEN viewing a student's deadline, THE System SHALL allow instructors to extend the deadline
4. WHEN granting an override, THE System SHALL require a reason
5. THE System SHALL log all overrides in Audit_Log
6. THE System SHALL display override status to instructors but not to students

### Requirement 25: Assignment Duplication

**User Story:** As an instructor, I want to duplicate assignments with all questions and settings, so that I can reuse assessments across courses or terms.

#### Acceptance Criteria

1. WHEN duplicating an Assignment, THE System SHALL copy all questions, settings, and configurations
2. WHEN duplicating an Assignment, THE System SHALL not copy submissions or grades
3. THE System SHALL allow instructors to modify the duplicated assignment before saving
4. THE System SHALL generate a new unique identifier for the duplicated assignment
5. THE System SHALL preserve question order and randomization settings

### Requirement 26: Bulk Operations

**User Story:** As an instructor, I want to perform bulk operations on submissions, so that I can efficiently manage large classes.

#### Acceptance Criteria

1. THE System SHALL allow instructors to select multiple submissions for bulk actions
2. THE System SHALL support bulk grade release for submissions in deferred review mode
3. THE System SHALL support bulk export of grades to CSV format
4. THE System SHALL support bulk feedback application to selected submissions
5. THE System SHALL validate bulk operations before execution and report any errors

### Requirement 27: Search and Filtering

**User Story:** As an instructor, I want to search and filter submissions, so that I can quickly find specific student work.

#### Acceptance Criteria

1. THE System SHALL allow searching submissions by student name or email
2. THE System SHALL allow filtering submissions by state (submitted, graded, released, etc.)
3. THE System SHALL allow filtering submissions by score range
4. THE System SHALL allow filtering submissions by submission date range
5. THE System SHALL integrate with Meilisearch via Laravel Scout for fast search
6. THE System SHALL display search results with relevant metadata and sorting options

### Requirement 28: Performance and Scalability

**User Story:** As a system administrator, I want the system to perform efficiently under load, so that students and instructors have a responsive experience.

#### Acceptance Criteria

1. WHEN auto-grading a submission with 50 questions, THE System SHALL complete grading within 2 seconds
2. WHEN loading the grading queue with 1000 submissions, THE System SHALL display results within 1 second using pagination
3. WHEN calculating course grades for 500 students, THE System SHALL complete within 5 seconds
4. THE System SHALL use database indexing on frequently queried fields (student_id, assignment_id, state, submission_time)
5. THE System SHALL use eager loading to prevent N+1 query problems when loading submissions with questions
6. WHEN recalculating grades after answer key changes, THE System SHALL process updates in background jobs using Laravel queues
7. THE System SHALL cache assignment configurations and question data to reduce database queries
8. WHEN handling file uploads, THE System SHALL stream files directly to storage without loading into memory
9. THE System SHALL use database transactions for grade updates to ensure data consistency
10. THE System SHALL implement query result caching for frequently accessed data (assignment lists, student rosters)
