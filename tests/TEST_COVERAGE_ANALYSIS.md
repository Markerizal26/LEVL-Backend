# Analisis Test Coverage

## Ringkasan
Total test cases: **261 test cases** di 13 file Feature Test

## ✅ Test Coverage yang Sudah Ada

### 1. Auth Module (AuthModuleTest.php) - 46 test cases
**Positive Cases:**
- ✅ Register user dengan verifikasi email
- ✅ Login dengan email/username
- ✅ Refresh token (via body, header, cookie)
- ✅ Logout dan revoke token
- ✅ Profile CRUD
- ✅ Set username untuk user tanpa username
- ✅ Email verification (send, verify dengan code/token)
- ✅ Email change request & verification
- ✅ Create managed users (Instructor, Admin, Superadmin)
- ✅ Resend credentials untuk pending user
- ✅ Update user status
- ✅ List users (Superadmin, Admin)
- ✅ Show user details (Superadmin)
- ✅ Forgot password & reset
- ✅ Change password

**Negative Cases:**
- ✅ Validasi register request
- ✅ Invalid credentials login
- ✅ Invalid refresh token
- ✅ Unauthenticated logout
- ✅ Unauthenticated profile access
- ✅ Username sudah diatur
- ✅ Email sudah terverifikasi
- ✅ Invalid email verification code
- ✅ Expired email change verification
- ✅ Role-based access control (Admin tidak bisa create Instructor, dll)
- ✅ Invalid password reset token
- ✅ Wrong current password

**Yang Kurang:**
- ❌ **Validasi detail untuk register**: username format (regex), password strength, email format edge cases
- ❌ **Login throttling**: test untuk rate limiting setelah multiple failed attempts
- ❌ **Refresh token expiry**: test untuk expired refresh token (idle & absolute)
- ❌ **Refresh token rotation**: verify old token tidak bisa digunakan lagi setelah refresh
- ❌ **Invalid token format**: test untuk malformed tokens
- ❌ **Email verification expiry**: test untuk expired verification code/token
- ❌ **Duplicate email change request**: test untuk multiple pending email changes
- ❌ **Password validation edge cases**: password terlalu pendek, tidak ada uppercase, dll
- ❌ **Username validation edge cases**: special characters, terlalu pendek/panjang
- ❌ **Profile update validasi**: test untuk invalid data format
- ❌ **User status edge cases**: test untuk update status ke pending (seharusnya tidak bisa)
- ❌ **List users pagination**: test untuk pagination, filtering, sorting
- ❌ **User details authorization**: test untuk Admin yang tidak bisa akses user details

### 2. Assessments Module

#### ExerciseManagementTest.php - 12 test cases
**Positive Cases:**
- ✅ Create exercise (Admin, Superadmin)
- ✅ List exercises dengan filters
- ✅ View exercise details
- ✅ Update draft exercise
- ✅ Delete draft exercise
- ✅ Publish exercise dengan questions
- ✅ Get exercise questions

**Negative Cases:**
- ✅ Student cannot create exercise
- ✅ Cannot update published exercise
- ✅ Cannot delete published exercise
- ✅ Cannot publish exercise without questions

**Yang Kurang:**
- ❌ **Validasi create exercise**: missing required fields, invalid scope_type/scope_id
- ❌ **Invalid scope**: test untuk scope_id yang tidak ada
- ❌ **Time limit validation**: negative values, invalid format
- ❌ **Max score validation**: negative values, invalid format
- ❌ **Date validation**: available_from > available_until
- ❌ **Authorization edge cases**: Instructor tidak bisa edit exercise dari course lain
- ❌ **Exercise status transitions**: test untuk invalid status changes
- ❌ **Pagination & filtering**: test untuk pagination, search, sort
- ❌ **Exercise dengan questions**: test untuk exercise yang sudah ada attempts tidak bisa dihapus

#### AttemptTest.php - 12 test cases
**Positive Cases:**
- ✅ Student can start attempt
- ✅ Student can list their attempts
- ✅ Student can view their attempt
- ✅ Student can submit answer
- ✅ Student can complete attempt

**Negative Cases:**
- ✅ Student cannot add question
- ✅ Student can update/delete question (tidak ada test, tapi seharusnya 403)

**Yang Kurang:**
- ❌ **Validasi start attempt**: test untuk exercise yang belum available, sudah expired
- ❌ **Multiple attempts**: test untuk limit attempts per exercise
- ❌ **Attempt time limit**: test untuk attempt yang melebihi time limit
- ❌ **Submit answer validasi**: invalid question_id, invalid option_id, missing required fields
- ❌ **Complete attempt validasi**: test untuk attempt yang belum menjawab semua questions
- ❌ **Attempt authorization**: test untuk student yang tidak enrolled tidak bisa start attempt
- ❌ **View attempt authorization**: test untuk student tidak bisa view attempt orang lain
- ❌ **Answer submission edge cases**: test untuk question type yang berbeda (free_text, multiple_choice)
- ❌ **Attempt status transitions**: test untuk invalid status changes
- ❌ **Attempt pagination**: test untuk pagination di list attempts

#### Question Management (AttemptTest.php)
**Positive Cases:**
- ✅ Instructor can add question
- ✅ Can update question
- ✅ Can delete question
- ✅ Instructor can add options
- ✅ Can update option
- ✅ Can delete option

**Negative Cases:**
- ✅ Student cannot add question

**Yang Kurang:**
- ❌ **Validasi question**: missing required fields, invalid type, invalid score_weight
- ❌ **Question type validation**: test untuk invalid question types
- ❌ **Score weight validation**: negative values, invalid format
- ❌ **Question options validation**: test untuk multiple_choice harus punya options
- ❌ **Correct answer validation**: test untuk multiple_choice harus punya minimal 1 correct answer
- ❌ **Question authorization**: test untuk instructor tidak bisa edit question dari exercise lain
- ❌ **Question deletion**: test untuk question yang sudah ada answers tidak bisa dihapus
- ❌ **Option validation**: test untuk duplicate options, missing option_text

#### GradingTest.php - 4 test cases
**Positive Cases:**
- ✅ Instructor can get exercise attempts
- ✅ Instructor can get attempt answers
- ✅ Instructor can add feedback to answer

**Negative Cases:**
- ✅ Student cannot view others answers

**Yang Kurang:**
- ❌ **Grading authorization**: test untuk instructor tidak bisa grade attempt dari course lain
- ❌ **Feedback validation**: test untuk invalid score_awarded (lebih dari max score, negative)
- ❌ **Answer feedback**: test untuk multiple feedbacks, update feedback
- ❌ **Grading pagination**: test untuk pagination di list attempts
- ❌ **Attempt filtering**: test untuk filter attempts by status, date, dll
- ❌ **Auto-grading**: test untuk auto-grading multiple choice questions
- ❌ **Manual grading**: test untuk manual grading free_text questions

### 3. CRUD Operations

#### CourseCrudTest.php - 29 test cases
**Positive Cases:**
- ✅ Create course dengan valid data
- ✅ Create course dengan all fields
- ✅ Create course dengan outcomes dan prerequisites
- ✅ Update course
- ✅ Delete course
- ✅ List courses
- ✅ View course details

**Negative Cases:**
- ✅ Student cannot create course
- ✅ Cannot create course dengan duplicate code
- ✅ Cannot create course dengan missing required fields
- ✅ Cannot update non-existent course
- ✅ Cannot delete non-existent course
- ✅ Unauthenticated user cannot create course

**Yang Kurang:**
- ❌ **Validasi detail**: test untuk invalid enrollment_type, invalid progression_mode
- ❌ **Enrollment key validation**: test untuk key_based course harus punya enrollment_key
- ❌ **Category validation**: test untuk invalid category_id
- ❌ **Outcomes validation**: test untuk empty outcomes array, invalid format
- ❌ **Prerequisites validation**: test untuk invalid HTML format
- ❌ **Course status transitions**: test untuk invalid status changes
- ❌ **Course pagination**: test untuk pagination, filtering, sorting
- ❌ **Course authorization**: test untuk Admin tidak bisa edit course dari instructor lain
- ❌ **Soft delete**: test untuk course yang sudah di-soft delete tidak muncul di list

#### CategoryCrudTest.php - 13 test cases
**Positive Cases:**
- ✅ Superadmin can create category
- ✅ Superadmin can update category
- ✅ Superadmin can delete category

**Negative Cases:**
- ✅ Admin cannot create category
- ✅ Cannot create category dengan duplicate value
- ✅ Cannot create category dengan missing required fields
- ✅ Unauthenticated user cannot create category
- ✅ Admin cannot update category
- ✅ Cannot update non-existent category
- ✅ Cannot update category dengan duplicate value
- ✅ Admin cannot delete category
- ✅ Cannot delete non-existent category
- ✅ Unauthenticated user cannot delete category

**Yang Kurang:**
- ❌ **Validasi detail**: test untuk invalid status value
- ❌ **Category value validation**: test untuk invalid format (spaces, special chars)
- ❌ **Category pagination**: test untuk pagination, filtering, sorting
- ❌ **Soft delete**: test untuk category yang sudah di-soft delete

#### TagCrudTest.php - 14 test cases
**Positive Cases:**
- ✅ Admin can create tag
- ✅ Admin can create multiple tags at once
- ✅ Admin can update tag
- ✅ Admin can delete tag

**Negative Cases:**
- ✅ Student cannot create tag
- ✅ Cannot create tag dengan duplicate name
- ✅ Cannot create tag dengan missing name
- ✅ Unauthenticated user cannot create tag
- ✅ Student cannot update tag
- ✅ Cannot update non-existent tag
- ✅ Cannot update tag dengan duplicate name
- ✅ Student cannot delete tag
- ✅ Cannot delete non-existent tag
- ✅ Unauthenticated user cannot delete tag

**Yang Kurang:**
- ❌ **Tag name validation**: test untuk invalid format (special chars, terlalu panjang)
- ❌ **Multiple tags validation**: test untuk empty array, duplicate names dalam array
- ❌ **Tag pagination**: test untuk pagination, filtering, sorting
- ❌ **Tag slug generation**: test untuk slug uniqueness

#### EnrollmentOperationsTest.php - 20 test cases
**Positive Cases:**
- ✅ Student can enroll in auto_accept course
- ✅ Student can enroll in key_based course dengan correct key
- ✅ Student can enroll in approval course
- ✅ Student can cancel their enrollment
- ✅ Admin can approve pending enrollment
- ✅ Admin can decline pending enrollment

**Negative Cases:**
- ✅ Cannot enroll in key_based course without key
- ✅ Cannot enroll in key_based course dengan wrong key
- ✅ Cannot enroll twice in same course
- ✅ Unauthenticated user cannot enroll
- ✅ Cannot cancel non-existent enrollment
- ✅ Cannot cancel enrollment of other user
- ✅ Student cannot approve enrollment
- ✅ Cannot approve non-pending enrollment
- ✅ Cannot approve non-existent enrollment
- ✅ Admin cannot approve enrollment in course they dont manage
- ✅ Cannot decline non-existent enrollment
- ✅ Admin cannot decline enrollment in course they dont manage
- ✅ Cannot decline non-pending enrollment
- ✅ Student cannot decline enrollment

**Yang Kurang:**
- ❌ **Enrollment status transitions**: test untuk invalid status changes
- ❌ **Enrollment pagination**: test untuk pagination, filtering, sorting
- ❌ **Enrollment authorization**: test untuk student tidak bisa approve/decline enrollment sendiri
- ❌ **Enrollment cancellation**: test untuk cancel enrollment yang sudah completed
- ❌ **Enrollment key validation**: test untuk key format, case sensitivity

### 4. Other Modules

#### FilteringSortingPaginationTest.php - 48 test cases
**Coverage:**
- ✅ Pagination untuk berbagai endpoints
- ✅ Filtering untuk berbagai fields
- ✅ Sorting untuk berbagai fields
- ✅ Combined filtering, sorting, pagination

#### UnitCrudTest.php, LessonCrudTest.php, SubmissionCrudTest.php, AssignmentCrudTest.php
**Status:** Perlu review detail untuk memastikan coverage lengkap

## 🔴 Test Case yang Sangat Penting Tapi Masih Kurang

### High Priority

1. **Auth Module:**
   - Login throttling/rate limiting
   - Refresh token expiry (idle & absolute)
   - Refresh token rotation verification
   - Password validation edge cases
   - Username validation edge cases

2. **Assessments Module:**
   - Attempt time limit enforcement
   - Multiple attempts limit
   - Answer submission validasi lengkap
   - Question type validation
   - Auto-grading vs manual grading

3. **CRUD Operations:**
   - Pagination untuk semua list endpoints
   - Filtering & sorting untuk semua list endpoints
   - Soft delete behavior
   - Status transition validasi

4. **Authorization:**
   - Cross-resource authorization (user tidak bisa akses resource dari course lain)
   - Role-based access untuk semua endpoints
   - Permission-based access untuk semua endpoints

### Medium Priority

1. **Edge Cases:**
   - Invalid data format
   - Boundary values (min/max)
   - Empty arrays/objects
   - Null values handling

2. **Integration:**
   - End-to-end workflows
   - Multi-step operations
   - Concurrent operations

3. **Error Handling:**
   - 500 error scenarios
   - Database constraint violations
   - File upload errors

## 📊 Coverage Statistics

- **Total Test Cases:** 261
- **Feature Tests:** 13 files
- **Unit Tests:** 19 files
- **Coverage Estimate:** ~70-75%

## 🎯 Rekomendasi

1. **Tambah test untuk validasi detail** - setiap field validation rule harus di-test
2. **Tambah test untuk edge cases** - boundary values, invalid formats
3. **Tambah test untuk authorization** - semua role combinations
4. **Tambah test untuk pagination/filtering** - semua list endpoints
5. **Tambah test untuk error scenarios** - semua error paths
6. **Tambah test untuk integration** - end-to-end workflows

## 📝 Template Test Case yang Disarankan

Untuk setiap endpoint, test harus mencakup:

1. **Positive Cases:**
   - Valid request dengan minimal required fields
   - Valid request dengan all fields
   - Valid request dengan edge case values (boundary)

2. **Validation Cases:**
   - Missing required fields
   - Invalid field formats
   - Invalid field values
   - Duplicate values (jika applicable)
   - Boundary values (min/max)

3. **Authorization Cases:**
   - Unauthenticated access
   - Wrong role access
   - Cross-resource access
   - Permission-based access

4. **Negative Cases:**
   - Non-existent resource
   - Invalid resource state
   - Business rule violations
   - Concurrent operation conflicts

5. **Edge Cases:**
   - Empty arrays/objects
   - Null values
   - Special characters
   - Very long strings
   - Unicode characters

