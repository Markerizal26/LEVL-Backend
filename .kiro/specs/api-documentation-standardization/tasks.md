# Implementation Plan

## Phase 1: Configuration & Infrastructure

- [x] 1. Update Scramble Configuration
  - [x] 1.1 Configure multiple servers (Local Development, Production)
    - Add servers array with APP_URL and API_PRODUCTION_URL
    - _Requirements: 11.1, 11.2, 11.3, 11.4_
  - [x] 1.2 Configure tag groups for endpoint organization
    - Define tag groups: Autentikasi, Profil, Kursus & Skema, Pendaftaran, Pembelajaran, Forum, Gamifikasi, Konten, Notifikasi, Sistem
    - _Requirements: 10.1, 10.2, 10.3, 10.4_
  - [x] 1.3 Add rate limit headers extension
    - Configure X-RateLimit-Limit, X-RateLimit-Remaining, Retry-After headers
    - _Requirements: 6.1, 6.2, 6.3, 6.4_

- [x] 2. Create PHPDoc Helper Traits/Classes
  - [x] 2.1 Create documentation constants file for reusable response examples
    - Define standard error responses (401, 403, 404, 422, 429, 500)
    - Define pagination meta example
    - _Requirements: 13.1, 13.2, 13.3, 13.4, 13.5, 13.6_

- [x] 3. Checkpoint - Verify configuration
  - Ensure all tests pass, ask the user if questions arise.

## Phase 2: Auth Module PHPDoc Standardization

- [x] 4. Update Auth Module Controllers
  - [x] 4.1 Update AuthController with standardized PHPDoc
    - Add @summary in Bahasa Indonesia for login, register, logout
    - Add @description with rate limit info (10 requests/minute)
    - Add realistic response examples
    - _Requirements: 2.1, 2.3, 4.1, 5.2, 14.1_
  - [x] 4.2 Update PasswordResetController with standardized PHPDoc
    - Add @summary, @description, realistic examples
    - Document rate limit (10 requests/minute)
    - _Requirements: 2.1, 4.1, 5.2, 14.1_
  - [x] 4.3 Update EmailVerificationController with standardized PHPDoc
    - Add @summary, @description, realistic examples
    - _Requirements: 2.1, 4.1, 14.1_
  - [x] 4.4 Update ProfileController with standardized PHPDoc
    - Add @summary for profile CRUD operations
    - Add realistic user profile examples
    - _Requirements: 2.1, 2.2, 2.4, 4.1, 14.1_
  - [ ]* 4.5 Write property test for Auth module PHPDoc coverage
    - **Property 8: Module PHPDoc Coverage**
    - **Validates: Requirements 14.1**

## Phase 3: Schemes Module PHPDoc Standardization

- [x] 5. Update Schemes Module Controllers
  - [x] 5.1 Update CourseController with standardized PHPDoc
    - Add @summary: "Daftar Kursus", "Detail Kursus", "Buat Kursus Baru", etc.
    - Add @queryParam for pagination, filters, sorting
    - Add realistic course examples
    - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 4.2, 8.1, 8.2, 14.2_
  - [x] 5.2 Update UnitController with standardized PHPDoc
    - Add @summary, @description, realistic examples
    - _Requirements: 2.1, 4.2, 14.2_
  - [x] 5.3 Update LessonController with standardized PHPDoc
    - Add @summary, @description, realistic examples
    - _Requirements: 2.1, 4.2, 14.2_
  - [x] 5.4 Update LessonBlockController with standardized PHPDoc
    - Add @summary, @description, realistic examples
    - _Requirements: 2.1, 4.2, 14.2_
  - [ ]* 5.5 Write property test for Schemes module PHPDoc coverage
    - **Property 8: Module PHPDoc Coverage**
    - **Validates: Requirements 14.2**

## Phase 4: Enrollments Module PHPDoc Standardization

- [x] 6. Update Enrollments Module Controllers
  - [x] 6.1 Update EnrollmentController with standardized PHPDoc
    - Add @summary: "Daftar Pendaftaran", "Daftarkan Peserta ke Kursus", etc.
    - Document rate limit (5 requests/minute for enroll/unenroll)
    - Add realistic enrollment examples
    - _Requirements: 2.1, 2.3, 4.1, 5.3, 9.4, 14.3_
  - [x] 6.2 Update EnrollmentKeyController with standardized PHPDoc
    - Add @summary, @description, realistic examples
    - _Requirements: 2.1, 4.1, 14.3_
  - [ ]* 6.3 Write property test for Enrollments module PHPDoc coverage
    - **Property 8: Module PHPDoc Coverage**
    - **Validates: Requirements 14.3**

- [x] 7. Checkpoint - Verify Phase 2-4
  - Ensure all tests pass, ask the user if questions arise.

## Phase 5: Content Module PHPDoc Standardization

- [x] 8. Update Content Module Controllers
  - [x] 8.1 Update AnnouncementController with standardized PHPDoc
    - Add @summary: "Daftar Pengumuman", "Buat Pengumuman Baru", etc.
    - Add @queryParam for filters (course_id, priority, unread)
    - Add realistic announcement examples
    - _Requirements: 2.1, 2.3, 4.3, 8.3, 9.2, 9.3, 14.4_
  - [x] 8.2 Update NewsController with standardized PHPDoc
    - Add @summary: "Daftar Berita", "Detail Berita", etc.
    - Add realistic news examples
    - _Requirements: 2.1, 2.2, 4.4, 14.4_
  - [x] 8.3 Update ContentStatisticsController with standardized PHPDoc
    - Add @summary, @description, realistic examples
    - _Requirements: 2.1, 4.3, 4.4, 14.4_
  - [ ]* 8.4 Write property test for Content module PHPDoc coverage
    - **Property 8: Module PHPDoc Coverage**
    - **Validates: Requirements 14.4**

## Phase 6: Forums Module PHPDoc Standardization

- [x] 9. Update Forums Module Controllers
  - [x] 9.1 Update ThreadController with standardized PHPDoc
    - Add @summary: "Daftar Thread Diskusi", "Buat Thread Baru", etc.
    - Add realistic forum thread examples
    - _Requirements: 2.1, 2.3, 4.1, 14.5_
  - [x] 9.2 Update ReplyController with standardized PHPDoc
    - Add @summary: "Buat Balasan", "Perbarui Balasan", etc.
    - _Requirements: 2.1, 2.4, 14.5_
  - [x] 9.3 Update ReactionController with standardized PHPDoc
    - Add @summary: "Toggle Reaksi"
    - _Requirements: 2.6, 14.5_
  - [x] 9.4 Update ForumStatisticsController with standardized PHPDoc
    - Add @summary, @description
    - _Requirements: 2.1, 14.5_
  - [ ]* 9.5 Write property test for Forums module PHPDoc coverage
    - **Property 8: Module PHPDoc Coverage**
    - **Validates: Requirements 14.5**

## Phase 7: Learning Module PHPDoc Standardization

- [x] 10. Update Learning Module Controllers
  - [x] 10.1 Update AssignmentController with standardized PHPDoc
    - Add @summary: "Daftar Tugas", "Detail Tugas", etc.
    - Add realistic assignment examples
    - _Requirements: 2.1, 2.2, 4.1, 14.6_
  - [x] 10.2 Update SubmissionController with standardized PHPDoc
    - Add @summary: "Daftar Pengumpulan", "Kirim Jawaban", etc.
    - _Requirements: 2.1, 2.3, 14.6_
  - [x] 10.3 Update ProgressController with standardized PHPDoc
    - Add @summary: "Progress Pembelajaran", "Tandai Selesai"
    - _Requirements: 2.1, 2.6, 14.6_
  - [ ]* 10.4 Write property test for Learning module PHPDoc coverage
    - **Property 8: Module PHPDoc Coverage**
    - **Validates: Requirements 14.6**

- [x] 11. Checkpoint - Verify Phase 5-7
  - Ensure all tests pass, ask the user if questions arise.

## Phase 8: Gamification Module PHPDoc Standardization

- [x] 12. Update Gamification Module Controllers
  - [x] 12.1 Update BadgeController with standardized PHPDoc
    - Add @summary: "Daftar Badge", "Detail Badge", etc.
    - Add realistic badge examples
    - _Requirements: 2.1, 2.2, 4.1, 14.7_
  - [x] 12.2 Update PointController with standardized PHPDoc
    - Add @summary: "Riwayat Poin", "Total Poin"
    - _Requirements: 2.1, 14.7_
  - [x] 12.3 Update LeaderboardController with standardized PHPDoc
    - Add @summary: "Papan Peringkat"
    - _Requirements: 2.1, 14.7_
  - [x] 12.4 Update ChallengeController with standardized PHPDoc
    - Add @summary: "Daftar Tantangan", "Detail Tantangan"
    - _Requirements: 2.1, 2.2, 14.7_
  - [ ]* 12.5 Write property test for Gamification module PHPDoc coverage
    - **Property 8: Module PHPDoc Coverage**
    - **Validates: Requirements 14.7**

## Phase 9: Notifications Module PHPDoc Standardization

- [x] 13. Update Notifications Module Controllers
  - [x] 13.1 Update NotificationsController with standardized PHPDoc
    - Add @summary: "Daftar Notifikasi", "Tandai Dibaca", etc.
    - Add realistic notification examples
    - _Requirements: 2.1, 2.6, 4.1, 14.8_
  - [x] 13.2 Update NotificationPreferenceController with standardized PHPDoc
    - Add @summary: "Preferensi Notifikasi", "Perbarui Preferensi"
    - _Requirements: 2.1, 2.4, 14.8_
  - [ ]* 13.3 Write property test for Notifications module PHPDoc coverage
    - **Property 8: Module PHPDoc Coverage**
    - **Validates: Requirements 14.8**

## Phase 10: Common & Search Modules PHPDoc Standardization

- [x] 14. Update Common Module Controllers
  - [x] 14.1 Update CategoriesController with standardized PHPDoc
    - Add @summary: "Daftar Kategori", "Buat Kategori Baru", etc.
    - _Requirements: 2.1, 2.3, 14.9_
  - [x] 14.2 Update MasterDataController with standardized PHPDoc
    - Improve existing @summary annotations
    - Add realistic enum value examples
    - _Requirements: 2.1, 9.1, 9.2, 9.3, 9.4, 14.9_
  - [ ]* 14.3 Write property test for Common module PHPDoc coverage
    - **Property 8: Module PHPDoc Coverage**
    - **Validates: Requirements 14.9**

- [x] 15. Update Search Module Controllers
  - [x] 15.1 Update SearchController with standardized PHPDoc
    - Add @summary: "Cari Kursus", "Saran Pencarian", "Riwayat Pencarian"
    - Add @queryParam documentation
    - _Requirements: 2.1, 8.4, 14.10_
  - [ ]* 15.2 Write property test for Search module PHPDoc coverage
    - **Property 8: Module PHPDoc Coverage**
    - **Validates: Requirements 14.10**

- [x] 16. Checkpoint - Verify Phase 8-10
  - Ensure all tests pass, ask the user if questions arise.

## Phase 11: Cleanup & Validation

- [x] 17. Remove Redundant Documentation Files
  - [x] 17.1 Deprecate Modules/Content/openapi.yaml
    - Add deprecation notice or remove file
    - Update Content module README to reference Scalar
    - _Requirements: 12.1, 12.2_
  - [x] 17.2 Update module READMEs to reference Scalar documentation
    - Remove duplicate endpoint documentation
    - Add link to Scalar URL
    - _Requirements: 12.2, 12.3_

- [x] 18. Write Integration Tests
  - [ ]* 18.1 Write property test for Response Format Consistency
    - **Property 1: Response Format Consistency**
    - **Validates: Requirements 1.1, 1.2, 1.3, 1.4**
  - [ ]* 18.2 Write property test for PHPDoc Summary Format
    - **Property 2: PHPDoc Summary Format**
    - **Validates: Requirements 2.1, 2.2, 2.3, 2.4, 2.5**
  - [ ]* 18.3 Write property test for Realistic Examples
    - **Property 3: Realistic Examples**
    - **Validates: Requirements 4.1, 4.2, 4.3, 4.4, 4.5, 4.6**
  - [ ]* 18.4 Write property test for Rate Limit Documentation
    - **Property 4: Rate Limit Documentation**
    - **Validates: Requirements 5.1, 5.2, 5.3, 5.4, 5.5**

- [x] 19. Final Checkpoint - Verify all documentation
  - Ensure all tests pass, ask the user if questions arise.
  - Regenerate OpenAPI spec with `php artisan scramble:export`
  - Verify Scalar UI displays correctly
  - ✅ Verified: api.json generated with 166 endpoints
  - ✅ Verified: All summaries in Bahasa Indonesia
  - ✅ Verified: Tags organized properly (Autentikasi, Skema & Kursus, Forum Diskusi, etc.)

