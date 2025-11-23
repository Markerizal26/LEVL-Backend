# Test Cases yang Sudah Diimplementasikan

## ✅ Auth Module (AuthModuleTest.php)

### Login Throttling & Rate Limiting
- ✅ `it("throttles login after multiple failed attempts")` - Test rate limiting setelah 5 failed attempts
- ✅ `it("locks account after threshold failed attempts")` - Test account lockout setelah threshold
- ✅ `it("clears throttling after successful login")` - Test bahwa throttling di-clear setelah login sukses

### Refresh Token Expiry
- ✅ `it("rejects refresh token with expired idle expiry")` - Test idle expiry (14 hari)
- ✅ `it("rejects refresh token with expired absolute expiry")` - Test absolute expiry (90 hari)
- ✅ `it("updates idle expiry on refresh token usage")` - Test bahwa idle expiry di-update saat refresh

### Refresh Token Rotation
- ✅ `it("rotates refresh token on each refresh")` - Test token rotation
- ✅ `it("rejects old refresh token after rotation")` - Test bahwa old token tidak bisa digunakan lagi
- ✅ `it("revokes all device tokens when replaced token is reused")` - Test security untuk token reuse

### Password Validation Edge Cases
- ✅ `it("rejects password shorter than 8 characters")`
- ✅ `it("rejects password without uppercase letters")`
- ✅ `it("rejects password without lowercase letters")`
- ✅ `it("rejects password without numbers")`
- ✅ `it("rejects password without symbols")`
- ✅ `it("rejects password without confirmation match")`
- ✅ `it("accepts valid strong password for password reset")`

### Username Validation Edge Cases
- ✅ `it("rejects username shorter than 3 characters")`
- ✅ `it("rejects username longer than 50 characters for registration")`
- ✅ `it("rejects username with spaces")`
- ✅ `it("rejects username with special characters not allowed")`
- ✅ `it("accepts username with allowed special characters")`
- ✅ `it("rejects duplicate username")`
- ✅ `it("rejects username case-insensitive duplicate")`

**Total: +27 test cases untuk Auth Module**

## ✅ Assessments Module (AttemptTest.php)

### Attempt Time Limit Enforcement
- ✅ `it("cannot start attempt for exercise not yet available")`
- ✅ `it("cannot start attempt for expired exercise")`
- ✅ `it("cannot start attempt for unpublished exercise")`
- ✅ `it("cannot start attempt without enrollment")`

### Multiple Attempts Limit
- ✅ `it("allows multiple attempts for same exercise")` - Test bahwa multiple attempts diperbolehkan

### Answer Submission Validation
- ✅ `it("validates answer submission requires question_id")`
- ✅ `it("validates answer submission for multiple_choice requires selected_option_id")`
- ✅ `it("validates answer submission for free_text requires answer_text")`
- ✅ `it("rejects answer submission for invalid question_id")`
- ✅ `it("rejects answer submission for question from different exercise")`

### Question Type Validation
- ✅ `it("validates question type is required")`
- ✅ `it("validates question type is in allowed values")`
- ✅ `it("validates multiple_choice question has options")`

### Auto-Grading vs Manual Grading
- ✅ `it("auto-grades multiple_choice questions on answer submission")` - Test auto-grading
- ✅ `it("requires manual grading for free_text questions")` - Test manual grading

**Total: +17 test cases untuk Assessments Module**

## 📝 Test Cases yang Masih Perlu Ditambahkan

### 1. Validasi Detail untuk Semua Field

#### Exercise Validation
- [ ] Test untuk `time_limit_minutes` boundary values (min: 1, max: tidak ada)
- [ ] Test untuk `max_score` boundary values (min: 0, negative values)
- [ ] Test untuk `available_from` dan `available_until` date validation
- [ ] Test untuk `available_until` harus setelah `available_from`
- [ ] Test untuk `scope_type` harus dalam ['course', 'program']
- [ ] Test untuk `scope_id` harus integer dan exist
- [ ] Test untuk `type` harus dalam ['quiz', 'exam', 'assignment', 'homework']
- [ ] Test untuk `title` max length (255)
- [ ] Test untuk duplicate title dalam scope yang sama

#### Question Validation
- [ ] Test untuk `score_weight` boundary values (min: 0, negative values)
- [ ] Test untuk `question_text` required dan tidak boleh empty
- [ ] Test untuk `type` harus dalam ['multiple_choice', 'free_text', 'file_upload', 'true_false']
- [ ] Test untuk multiple_choice harus punya minimal 1 option
- [ ] Test untuk multiple_choice harus punya minimal 1 correct option

#### Option Validation
- [ ] Test untuk `option_text` required
- [ ] Test untuk `is_correct` harus boolean
- [ ] Test untuk duplicate options dalam question yang sama

#### Answer Validation
- [ ] Test untuk `selected_option_id` harus exist dan belong to question
- [ ] Test untuk `answer_text` max length
- [ ] Test untuk `answer_text` required untuk free_text
- [ ] Test untuk tidak bisa submit answer untuk question yang sudah dijawab

### 2. Pagination & Filtering untuk Semua List Endpoints

#### Auth Module
- [ ] Test pagination untuk `/auth/users` (Superadmin, Admin)
- [ ] Test filtering untuk `/auth/users` (by status, role, email, username)
- [ ] Test sorting untuk `/auth/users` (by name, email, created_at)
- [ ] Test combined filtering, sorting, pagination

#### Assessments Module
- [ ] Test pagination untuk `/assessments/exercises`
- [ ] Test filtering untuk `/assessments/exercises` (by status, type, scope_type, scope_id)
- [ ] Test sorting untuk `/assessments/exercises` (by title, created_at, available_from)
- [ ] Test pagination untuk `/assessments/attempts`
- [ ] Test filtering untuk `/assessments/attempts` (by status, exercise_id, user_id)
- [ ] Test sorting untuk `/assessments/attempts` (by started_at, score)
- [ ] Test pagination untuk `/assessments/questions`
- [ ] Test pagination untuk grading endpoints

#### CRUD Modules
- [ ] Test pagination untuk semua list endpoints (Courses, Categories, Tags, Units, Lessons, dll)
- [ ] Test filtering untuk semua list endpoints
- [ ] Test sorting untuk semua list endpoints
- [ ] Test combined filtering, sorting, pagination untuk semua

### 3. Authorization Edge Cases

#### Cross-Resource Authorization
- [ ] Test student tidak bisa akses attempt dari student lain
- [ ] Test instructor tidak bisa edit exercise dari course lain
- [ ] Test admin tidak bisa approve enrollment dari course yang tidak mereka manage
- [ ] Test user tidak bisa akses resource dari course yang tidak mereka enroll
- [ ] Test instructor tidak bisa grade attempt dari exercise yang bukan miliknya

#### Permission-Based Access
- [ ] Test semua endpoints dengan permission checks
- [ ] Test role combinations untuk setiap endpoint
- [ ] Test permission inheritance (Superadmin > Admin > Instructor > Student)

#### Status Transition Validation
- [ ] Test exercise status transitions (draft -> published -> tidak bisa kembali)
- [ ] Test attempt status transitions (in_progress -> completed -> tidak bisa kembali)
- [ ] Test enrollment status transitions (pending -> active -> cancelled)
- [ ] Test user status transitions (pending -> active -> inactive, tidak bisa kembali ke pending)

### 4. Edge Cases Lainnya

#### Boundary Values
- [ ] Test untuk semua numeric fields dengan min/max values
- [ ] Test untuk string fields dengan max length
- [ ] Test untuk date fields dengan past/future dates
- [ ] Test untuk empty arrays/objects
- [ ] Test untuk null values handling

#### Concurrent Operations
- [ ] Test untuk concurrent attempt starts
- [ ] Test untuk concurrent answer submissions
- [ ] Test untuk concurrent status updates

#### Error Scenarios
- [ ] Test untuk 500 error scenarios
- [ ] Test untuk database constraint violations
- [ ] Test untuk file upload errors
- [ ] Test untuk network timeout scenarios

## 📊 Progress Summary

- **Auth Module**: 27 test cases ditambahkan ✅
- **Assessments Module**: 17 test cases ditambahkan ✅
- **Total Test Cases Ditambahkan**: 44 test cases
- **Test Cases Masih Perlu**: ~100+ test cases

## 🎯 Prioritas Selanjutnya

1. **High Priority**: Validasi detail untuk semua field (boundary values, formats)
2. **High Priority**: Pagination & filtering untuk semua list endpoints
3. **High Priority**: Authorization edge cases (cross-resource, permissions)
4. **Medium Priority**: Status transition validations
5. **Low Priority**: Concurrent operations, error scenarios

## 📝 Catatan

- Beberapa test case mungkin perlu adjustment berdasarkan implementasi aktual
- Test case untuk pagination & filtering bisa dibuat generic/reusable
- Test case untuk authorization bisa dibuat helper functions untuk berbagai role combinations

