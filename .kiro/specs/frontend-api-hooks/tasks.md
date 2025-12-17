# Implementation Plan: Frontend API Hooks

## Phase 1: Schemes Module Enhancement

- [x] 1. Enhance Schemes module with Units, Lessons, Blocks, and Progress hooks
  - Add TypeScript types for Units, Lessons, Blocks, Progress, and Enrollment Keys
  - Implement Units CRUD hooks with reorder, publish, and unpublish
  - Implement Lessons CRUD hooks with publish, unpublish, and complete
  - Implement Lesson Blocks CRUD hooks
  - Implement Progress tracking hooks
  - Implement Enrollment Key management hooks
  - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5_

- [x] 1.1 Add TypeScript types for Schemes enhancements
  - Create Unit interface with all fields from backend
  - Create Lesson interface with all fields from backend
  - Create LessonBlock interface with all fields from backend
  - Create Progress interface with all fields from backend
  - Create Create/Update data interfaces for each resource
  - Add union types for status enums
  - _Requirements: 1.1, 1.2, 1.3, 1.4, 12.1, 12.2, 12.3, 12.4_

- [x] 1.2 Implement Units hooks in schemes.ts
  - Add UNITS_ENDPOINT constant and query keys
  - Implement useUnitsQuery with pagination
  - Implement useUnitQuery for single unit
  - Implement useCreateUnit mutation
  - Implement useUpdateUnit mutation
  - Implement useDeleteUnit mutation
  - Implement useReorderUnits mutation
  - Implement usePublishUnit mutation
  - Implement useUnpublishUnit mutation
  - Add query invalidation for all mutations
  - _Requirements: 1.1, 13.1, 13.2, 13.3, 13.4, 14.1_

- [x] 1.3 Implement Lessons hooks in schemes.ts
  - Add LESSONS_ENDPOINT constant and query keys
  - Implement useLessonsQuery with pagination
  - Implement useLessonQuery for single lesson
  - Implement useCreateLesson mutation
  - Implement useUpdateLesson mutation
  - Implement useDeleteLesson mutation
  - Implement usePublishLesson mutation
  - Implement useUnpublishLesson mutation
  - Implement useCompleteLesson mutation
  - Add query invalidation for all mutations including parent course/unit
  - _Requirements: 1.2, 13.1, 13.2, 14.1, 14.4_

- [x] 1.4 Implement Lesson Blocks hooks in schemes.ts
  - Add LESSON_BLOCKS_ENDPOINT constant and query keys
  - Implement useLessonBlocksQuery with pagination
  - Implement useLessonBlockQuery for single block
  - Implement useCreateLessonBlock mutation
  - Implement useUpdateLessonBlock mutation
  - Implement useDeleteLessonBlock mutation
  - Add query invalidation for all mutations including parent lesson
  - _Requirements: 1.3, 13.1, 13.2, 14.1, 14.4_

- [x] 1.5 Implement Progress hooks in schemes.ts
  - Add PROGRESS_ENDPOINT constant and query keys
  - Implement useCourseProgress query
  - Implement useCompleteLessonMutation
  - Add query invalidation for progress and related course data
  - _Requirements: 1.4, 13.1, 13.2, 14.1_

- [x] 1.6 Implement Enrollment Key management hooks in schemes.ts
  - Implement useGenerateEnrollmentKey mutation
  - Implement useUpdateEnrollmentKey mutation
  - Implement useRemoveEnrollmentKey mutation
  - Add query invalidation for course data after key changes
  - _Requirements: 1.5, 13.2, 14.1_

## Phase 2: Enrollments Module Enhancement

- [x] 2. Enhance Enrollments module with enrollment actions, status, and reports
  - Add TypeScript types for enrollment actions and reports
  - Implement enrollment action hooks (enroll, cancel, withdraw, approve, decline, remove)
  - Implement enrollment status hooks
  - Implement enrollment list hooks (managed, by course)
  - Implement report hooks (completion rate, funnel, CSV export)
  - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5_

- [x] 2.1 Add TypeScript types for Enrollments enhancements
  - Create EnrollmentStatus interface
  - Create EnrollmentReport interfaces (CompletionRate, Funnel)
  - Create action data interfaces (Enroll, Cancel, Withdraw, Approve, Decline, Remove)
  - Update existing Enrollment interface if needed
  - _Requirements: 2.1, 2.2, 2.3, 2.4, 12.1, 12.2_

- [x] 2.2 Implement enrollment action hooks in enrollments.ts
  - Implement useEnrollCourse mutation
  - Implement useCancelEnrollment mutation
  - Implement useWithdrawEnrollment mutation
  - Implement useApproveEnrollment mutation
  - Implement useDeclineEnrollment mutation
  - Implement useRemoveEnrollment mutation
  - Add query invalidation for all enrollment mutations
  - _Requirements: 2.1, 2.5, 13.2, 14.1_

- [x] 2.3 Implement enrollment status and list hooks in enrollments.ts
  - Implement useEnrollmentStatus query
  - Implement useManagedEnrollments query with pagination
  - Implement useEnrollmentsByCourse query with pagination
  - Update query keys for new hooks
  - _Requirements: 2.2, 2.3, 13.1_

- [x] 2.4 Implement enrollment report hooks in enrollments.ts
  - Implement useCourseCompletionRate query
  - Implement useEnrollmentFunnel query
  - Implement useExportEnrollmentsCsv mutation
  - Add appropriate query keys
  - _Requirements: 2.4, 13.1, 13.2_

## Phase 3: Learning Module Enhancement

- [x] 3. Enhance Learning module with Assignments and Submissions
  - Add TypeScript types for Assignments and Submissions
  - Implement Assignment CRUD hooks with publish/unpublish
  - Implement Submission CRUD hooks with grade
  - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5_

- [x] 3.1 Add TypeScript types for Learning enhancements
  - Create Assignment interface with all fields
  - Create Submission interface with all fields
  - Create Create/Update data interfaces for both resources
  - Add union types for assignment and submission status enums
  - _Requirements: 3.1, 3.2, 12.1, 12.2, 12.4_

- [x] 3.2 Implement Assignment hooks in learning.ts
  - Add ASSIGNMENTS_ENDPOINT constant and query keys
  - Implement useAssignmentsQuery with pagination (by lesson)
  - Implement useAssignmentQuery for single assignment
  - Implement useCreateAssignment mutation
  - Implement useUpdateAssignment mutation
  - Implement useDeleteAssignment mutation
  - Implement usePublishAssignment mutation
  - Implement useUnpublishAssignment mutation
  - Add query invalidation for all mutations
  - _Requirements: 3.1, 3.3, 3.5, 13.1, 13.2, 14.1_

- [x] 3.3 Implement Submission hooks in learning.ts
  - Add SUBMISSIONS_ENDPOINT constant and query keys
  - Implement useSubmissionsQuery with pagination (by assignment)
  - Implement useSubmissionQuery for single submission
  - Implement useCreateSubmission mutation
  - Implement useUpdateSubmission mutation
  - Implement useGradeSubmission mutation
  - Add query invalidation for all mutations including parent assignment
  - _Requirements: 3.2, 3.4, 3.5, 13.1, 13.2, 14.1, 14.4_

## Phase 4: Master Data Module Enhancement

- [x] 4. Enhance Master Data module with type listing and enum hooks
  - Add TypeScript types for master data types list
  - Implement master data types listing hook
  - Implement individual enum hooks for all master data types
  - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5_

- [x] 4.1 Add TypeScript types for Master Data enhancements
  - Create MasterDataType interface for type listing
  - Add union types for all enum-based master data
  - Update existing master data types if needed
  - _Requirements: 4.1, 4.5, 12.1, 12.2, 12.4_

- [x] 4.2 Implement master data types listing hook in master-data.ts
  - Implement useMasterDataTypes query
  - Add appropriate query key
  - _Requirements: 4.1, 13.1_

- [x] 4.3 Implement enum-based master data hooks in master-data.ts (Part 1)
  - Implement useUserStatuses query
  - Implement useRoles query
  - Implement useCourseStatuses query
  - Implement useCourseTypes query
  - Implement useEnrollmentTypes query
  - Implement useLevelTags query
  - Implement useProgressionModes query
  - Implement useContentTypes query
  - _Requirements: 4.5, 13.1_

- [x] 4.4 Implement enum-based master data hooks in master-data.ts (Part 2)
  - Implement useEnrollmentStatuses query
  - Implement useProgressStatuses query
  - Implement useAssignmentStatuses query
  - Implement useSubmissionStatuses query
  - Implement useSubmissionTypes query
  - Implement useContentStatuses query
  - Implement usePriorities query
  - Implement useTargetTypes query
  - _Requirements: 4.5, 13.1_

- [x] 4.5 Implement enum-based master data hooks in master-data.ts (Part 3)
  - Implement useChallengeTypes query
  - Implement useChallengeAssignmentStatuses query
  - Implement useChallengeCriteriaTypes query
  - Implement useBadgeTypes query
  - Implement usePointSourceTypes query
  - Implement usePointReasons query
  - Implement useNotificationTypes query
  - Implement useNotificationChannels query
  - Implement useNotificationFrequencies query
  - Implement useGradeStatuses query
  - Implement useGradeSourceTypes query
  - Implement useCategoryStatuses query
  - Implement useSettingTypes query
  - _Requirements: 4.5, 13.1_

## Phase 5: Search Module Implementation

- [x] 5. Implement Search module for course search and history
  - Create search.ts module file
  - Create types/search.ts type file
  - Implement course search hooks
  - Implement autocomplete hooks
  - Implement search history hooks
  - Add exports to index files
  - _Requirements: 5.1, 5.2, 5.3, 5.4_

- [x] 5.1 Create Search module structure
  - Create prep-lsp-fe/lib/api/modules/search.ts
  - Create prep-lsp-fe/lib/api/modules/types/search.ts
  - Add exports to prep-lsp-fe/lib/api/modules/index.ts
  - Add type exports to prep-lsp-fe/lib/api/modules/types/index.ts
  - _Requirements: 15.1, 15.2, 15.3, 15.4_

- [x] 5.2 Add TypeScript types for Search module
  - Create SearchResult interface
  - Create AutocompleteResult interface
  - Create SearchHistory interface
  - Create search query parameter interfaces
  - _Requirements: 5.1, 5.2, 5.3, 12.1, 12.2_

- [x] 5.3 Implement Search hooks in search.ts
  - Add SEARCH_ENDPOINT constant and query keys
  - Implement useCourseSearch query with pagination
  - Implement useAutocomplete query
  - Implement useSearchHistory query with pagination
  - Implement useClearSearchHistory mutation
  - Add query invalidation for clear history mutation
  - _Requirements: 5.1, 5.2, 5.3, 5.4, 13.1, 13.2, 14.1_

## Phase 6: Grading Module Implementation

- [x] 6. Implement Grading module for grade management
  - Create grading.ts module file
  - Create types/grading.ts type file
  - Implement grading CRUD hooks
  - Add exports to index files
  - _Requirements: 6.1, 6.2, 6.3, 6.4_

- [x] 6.1 Create Grading module structure
  - Create prep-lsp-fe/lib/api/modules/grading.ts
  - Create prep-lsp-fe/lib/api/modules/types/grading.ts
  - Add exports to prep-lsp-fe/lib/api/modules/index.ts
  - Add type exports to prep-lsp-fe/lib/api/modules/types/index.ts
  - _Requirements: 15.1, 15.2, 15.3, 15.4_

- [x] 6.2 Add TypeScript types for Grading module
  - Create Grading interface with all fields
  - Create CreateGradingData interface
  - Create UpdateGradingData interface
  - Add union types for grade status enum
  - _Requirements: 6.1, 6.3, 12.1, 12.2, 12.4_

- [x] 6.3 Implement Grading hooks in grading.ts
  - Add GRADING_ENDPOINT constant and query keys
  - Implement useGradingsQuery with pagination
  - Implement useGradingQuery for single grading
  - Implement useCreateGrading mutation
  - Implement useUpdateGrading mutation
  - Implement useDeleteGrading mutation
  - Add query invalidation for all mutations
  - _Requirements: 6.1, 6.2, 6.3, 6.4, 13.1, 13.2, 14.1, 14.2_

## Phase 7: Notifications Module Implementation

- [x] 7. Implement Notifications module for notifications and preferences
  - Create notifications.ts module file
  - Create types/notifications.ts type file
  - Implement notification CRUD hooks
  - Implement notification preference hooks
  - Add exports to index files
  - _Requirements: 7.1, 7.2, 7.3, 7.4, 7.5_

- [x] 7.1 Create Notifications module structure
  - Create prep-lsp-fe/lib/api/modules/notifications.ts
  - Create prep-lsp-fe/lib/api/modules/types/notifications.ts
  - Add exports to prep-lsp-fe/lib/api/modules/index.ts
  - Add type exports to prep-lsp-fe/lib/api/modules/types/index.ts
  - _Requirements: 15.1, 15.2, 15.3, 15.4_

- [x] 7.2 Add TypeScript types for Notifications module
  - Create Notification interface with all fields
  - Create NotificationPreference interface
  - Create Create/Update data interfaces
  - Add union types for notification type and channel enums
  - _Requirements: 7.1, 7.3, 12.1, 12.2, 12.4_

- [x] 7.3 Implement Notification hooks in notifications.ts
  - Add NOTIFICATIONS_ENDPOINT constant and query keys
  - Implement useNotificationsQuery with pagination
  - Implement useNotificationQuery for single notification
  - Implement useCreateNotification mutation
  - Implement useUpdateNotification mutation
  - Implement useDeleteNotification mutation
  - Add query invalidation for all mutations
  - _Requirements: 7.1, 7.2, 7.5, 13.1, 13.2, 14.1, 14.2_

- [x] 7.4 Implement Notification Preference hooks in notifications.ts
  - Add PREFERENCES_ENDPOINT constant and query keys
  - Implement useNotificationPreferences query
  - Implement useUpdateNotificationPreferences mutation
  - Implement useResetNotificationPreferences mutation
  - Add query invalidation for preference mutations
  - _Requirements: 7.3, 7.4, 7.5, 13.1, 13.2, 14.1_

## Phase 8: Forums Module Implementation

- [x] 8. Implement Forums module for threads, replies, reactions, and statistics
  - Create forums.ts module file
  - Create types/forums.ts type file
  - Implement thread CRUD hooks with search, pin, close
  - Implement reply CRUD hooks with accept
  - Implement reaction hooks
  - Implement statistics hooks
  - Add exports to index files
  - _Requirements: 8.1, 8.2, 8.3, 8.4_

- [x] 8.1 Create Forums module structure
  - Create prep-lsp-fe/lib/api/modules/forums.ts
  - Create prep-lsp-fe/lib/api/modules/types/forums.ts
  - Add exports to prep-lsp-fe/lib/api/modules/index.ts
  - Add type exports to prep-lsp-fe/lib/api/modules/types/index.ts
  - _Requirements: 15.1, 15.2, 15.3, 15.4_

- [x] 8.2 Add TypeScript types for Forums module
  - Create Thread interface with all fields
  - Create Reply interface with all fields
  - Create Reaction interface with all fields
  - Create ForumStatistics interface
  - Create Create/Update data interfaces for threads and replies
  - Add union types for reaction type enum
  - _Requirements: 8.1, 8.2, 8.3, 8.4, 12.1, 12.2, 12.3, 12.4_

- [x] 8.3 Implement Thread hooks in forums.ts
  - Add THREADS_ENDPOINT constant and query keys
  - Implement useThreadsQuery with pagination (by scheme)
  - Implement useSearchThreads query
  - Implement useThreadQuery for single thread
  - Implement useCreateThread mutation
  - Implement useUpdateThread mutation
  - Implement useDeleteThread mutation
  - Implement usePinThread mutation
  - Implement useCloseThread mutation
  - Add query invalidation for all mutations
  - _Requirements: 8.1, 13.1, 13.2, 14.1, 14.2_

- [x] 8.4 Implement Reply hooks in forums.ts
  - Add REPLIES_ENDPOINT constant and query keys
  - Implement useRepliesQuery with pagination (by thread)
  - Implement useReplyQuery for single reply
  - Implement useCreateReply mutation
  - Implement useUpdateReply mutation
  - Implement useDeleteReply mutation
  - Implement useAcceptReply mutation
  - Add query invalidation for all mutations including parent thread
  - _Requirements: 8.2, 13.1, 13.2, 14.1, 14.2, 14.4_

- [x] 8.5 Implement Reaction and Statistics hooks in forums.ts
  - Implement useToggleThreadReaction mutation
  - Implement useToggleReplyReaction mutation
  - Implement useForumStatistics query (by scheme)
  - Implement useUserForumStats query
  - Add query invalidation for reaction mutations
  - _Requirements: 8.3, 8.4, 13.1, 13.2, 14.1_

## Phase 9: Gamification Module Implementation

- [x] 9. Implement Gamification module for challenges, leaderboards, badges, and points
  - Create gamification.ts module file
  - Create types/gamification.ts type file
  - Implement challenge hooks
  - Implement leaderboard hooks
  - Implement gamification dashboard hooks
  - Add exports to index files
  - _Requirements: 9.1, 9.2, 9.3, 9.4, 9.5_

- [x] 9.1 Create Gamification module structure
  - Create prep-lsp-fe/lib/api/modules/gamification.ts
  - Create prep-lsp-fe/lib/api/modules/types/gamification.ts
  - Add exports to prep-lsp-fe/lib/api/modules/index.ts
  - Add type exports to prep-lsp-fe/lib/api/modules/types/index.ts
  - _Requirements: 15.1, 15.2, 15.3, 15.4_

- [x] 9.2 Add TypeScript types for Gamification module
  - Create Challenge interface with all fields
  - Create Leaderboard interface
  - Create Badge interface
  - Create PointHistory interface
  - Create Achievement interface
  - Create GamificationSummary interface
  - Add union types for challenge and badge type enums
  - _Requirements: 9.1, 9.2, 9.3, 9.4, 12.1, 12.2, 12.3, 12.4_

- [x] 9.3 Implement Challenge hooks in gamification.ts
  - Add CHALLENGES_ENDPOINT constant and query keys
  - Implement useChallengesQuery with pagination
  - Implement useMyChallenges query
  - Implement useCompletedChallenges query
  - Implement useChallengeQuery for single challenge
  - Implement useClaimChallenge mutation
  - Add query invalidation for claim mutation
  - _Requirements: 9.1, 9.2, 9.5, 13.1, 13.2, 14.1_

- [x] 9.4 Implement Leaderboard hooks in gamification.ts
  - Add LEADERBOARDS_ENDPOINT constant and query keys
  - Implement useLeaderboards query
  - Implement useMyRank query
  - _Requirements: 9.3, 13.1_

- [x] 9.5 Implement Gamification Dashboard hooks in gamification.ts
  - Add GAMIFICATION_ENDPOINT constant and query keys
  - Implement useGamificationSummary query
  - Implement useBadges query
  - Implement usePointsHistory query with pagination
  - Implement useAchievements query
  - _Requirements: 9.4, 13.1_

## Phase 10: Content Module Implementation

- [x] 10. Implement Content module for announcements, news, statistics, and approval
  - Create content.ts module file
  - Create types/content.ts type file
  - Implement announcement CRUD hooks with publish, schedule, read
  - Implement news CRUD hooks with trending, publish, schedule
  - Implement course announcement hooks
  - Implement content statistics hooks
  - Implement content approval hooks
  - Add exports to index files
  - _Requirements: 10.1, 10.2, 10.3, 10.4, 10.5_

- [x] 10.1 Create Content module structure
  - Create prep-lsp-fe/lib/api/modules/content.ts
  - Create prep-lsp-fe/lib/api/modules/types/content.ts
  - Add exports to prep-lsp-fe/lib/api/modules/index.ts
  - Add type exports to prep-lsp-fe/lib/api/modules/types/index.ts
  - _Requirements: 15.1, 15.2, 15.3, 15.4_

- [x] 10.2 Add TypeScript types for Content module
  - Create Announcement interface with all fields
  - Create News interface with all fields
  - Create ContentStatistics interface
  - Create ContentApproval interface
  - Create Create/Update data interfaces for announcements and news
  - Add union types for content status, priority, and target type enums
  - _Requirements: 10.1, 10.2, 10.3, 10.4, 10.5, 12.1, 12.2, 12.3, 12.4_

- [x] 10.3 Implement Announcement hooks in content.ts
  - Add ANNOUNCEMENTS_ENDPOINT constant and query keys
  - Implement useAnnouncementsQuery with pagination
  - Implement useAnnouncementQuery for single announcement
  - Implement useCreateAnnouncement mutation
  - Implement useUpdateAnnouncement mutation
  - Implement useDeleteAnnouncement mutation
  - Implement usePublishAnnouncement mutation
  - Implement useScheduleAnnouncement 
  - Implement useMarkAnnouncementAsRead mutation
  - Add query invalidation for all mutations
  - _Requirements: 10.1, 13.1, 13.2, 14.1, 14.2_

- [x] 10.4 Implement News hooks in content.ts
  - Add NEWS_ENDPOINT constant and query keys
  - Implement useNewsQuery with pagination
  - Implement useTrendingNews query
  - Implement useNewsItemQuery for single news (by slug)
  - Implement useCreateNews mutation
  - Implement useUpdateNews mutation
  - Implement useDeleteNews mutation
  - Implement usePublishNews mutation
  - Implement useScheduleNews mutation
  - Add query invalidation for all mutations
  - _Requirements: 10.2, 13.1, 13.2, 14.1, 14.2_

- [x] 10.5 Implement Course Announcement hooks in content.ts
  - Add COURSE_ANNOUNCEMENTS_ENDPOINT constant and query keys
  - Implement useCourseAnnouncements query with pagination (by course)
  - Implement useCreateCourseAnnouncement mutation
  - Add query invalidation for create mutation
  - _Requirements: 10.3, 13.1, 13.2, 14.1_

- [x] 10.6 Implement Content Statistics hooks in content.ts
  - Add CONTENT_STATISTICS_ENDPOINT constant and query keys
  - Implement useContentStatistics query
  - Implement useAnnouncementStatistics query (by announcement)
  - Implement useNewsStatistics query (by news slug)
  - Implement useTrendingContent query
  - Implement useMostViewedContent query
  - _Requirements: 10.4, 13.1_

- [x] 10.7 Implement Content Approval hooks in content.ts
  - Add CONTENT_APPROVAL_ENDPOINT constant and query keys
  - Implement useSubmitContent mutation
  - Implement useApproveContent mutation
  - Implement useRejectContent mutation
  - Implement usePendingReviewContent query
  - Add query invalidation for all approval mutations
  - _Requirements: 10.5, 13.1, 13.2, 14.1_

## Phase 11: Operations Module Implementation

- [x] 11. Implement Operations module for operations management
  - Create operations.ts module file
  - Create types/operations.ts type file
  - Implement operations CRUD hooks
  - Add exports to index files
  - _Requirements: 11.1, 11.2, 11.3, 11.4_

- [x] 11.1 Create Operations module structure
  - Create prep-lsp-fe/lib/api/modules/operations.ts
  - Create prep-lsp-fe/lib/api/modules/types/operations.ts
  - Add exports to prep-lsp-fe/lib/api/modules/index.ts
  - Add type exports to prep-lsp-fe/lib/api/modules/types/index.ts
  - _Requirements: 15.1, 15.2, 15.3, 15.4_

- [x] 11.2 Add TypeScript types for Operations module
  - Create Operation interface with all fields
  - Create CreateOperationData interface
  - Create UpdateOperationData interface
  - Add union types for operation status enum if applicable
  - _Requirements: 11.1, 11.3, 12.1, 12.2, 12.4_

- [x] 11.3 Implement Operations hooks in operations.ts
  - Add OPERATIONS_ENDPOINT constant and query keys
  - Implement useOperationsQuery with pagination
  - Implement useOperationQuery for single operation
  - Implement useCreateOperation mutation
  - Implement useUpdateOperation mutation
  - Implement useDeleteOperation mutation
  - Add query invalidation for all mutations
  - _Requirements: 11.1, 11.2, 11.3, 11.4, 13.1, 13.2, 14.1, 14.2_

## Final Checkpoint

- [x] 12. Final verification and documentation
  - Verify all modules follow consistent patterns
  - Verify all TypeScript types are properly exported
  - Verify all query invalidations are correct
  - Verify all hooks use existing utilities
  - Update any documentation if needed
  - _Requirements: 13.5, 14.5, 15.5_
