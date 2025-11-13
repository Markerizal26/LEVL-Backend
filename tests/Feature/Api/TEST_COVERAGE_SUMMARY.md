# Test Coverage Summary - CRUD POST PUT DELETE

## ✅ CourseCrudTest.php (26 tests)

### POST - Create Course
**Positive:**
- ✅ admin can create course with valid data
- ✅ admin can create course with all fields
- ✅ superadmin can create course

**Negative:**
- ✅ student cannot create course (403)
- ✅ unauthenticated user cannot create course (401)
- ✅ cannot create course with duplicate code (422)
- ✅ cannot create course with invalid level_tag (422)
- ✅ cannot create course with missing required fields (422)
- ✅ cannot create course with invalid enrollment_type (422)
- ✅ requires enrollment_key for key_based enrollment_type (422)
- ✅ cannot create course with code exceeding max length (422)

### PUT - Update Course
**Positive:**
- ✅ admin can update course
- ✅ admin can update course with partial data

**Negative:**
- ✅ student cannot update course (403)
- ✅ admin cannot update course they dont manage (403)
- ✅ cannot update course with duplicate code (422)
- ✅ cannot update non-existent course (404)

### DELETE - Delete Course
**Positive:**
- ✅ admin can delete course
- ✅ superadmin can delete any course

**Negative:**
- ✅ student cannot delete course (403)
- ✅ admin cannot delete course they dont manage (403)
- ✅ cannot delete non-existent course (404)

### Publish/Unpublish
**Positive:**
- ✅ admin can publish course
- ✅ admin can unpublish course

**Negative:**
- ✅ student cannot publish course (403)
- ✅ admin cannot publish course they dont manage (403)

---

## ✅ UnitCrudTest.php (13 tests)

### POST - Create Unit
**Positive:**
- ✅ admin can create unit for their course
- ✅ superadmin can create unit for any course

**Negative:**
- ✅ student cannot create unit (403)
- ✅ admin cannot create unit for course they dont manage (403)
- ✅ cannot create unit with duplicate code (422)
- ✅ cannot create unit with missing required fields (422)

### PUT - Update Unit
**Positive:**
- ✅ admin can update unit in their course

**Negative:**
- ✅ student cannot update unit (403)
- ✅ admin cannot update unit in course they dont manage (403)
- ⚠️ **MISSING:** cannot update non-existent unit (404)
- ⚠️ **MISSING:** cannot update unit with duplicate code (422)

### DELETE - Delete Unit
**Positive:**
- ✅ admin can delete unit from their course

**Negative:**
- ✅ student cannot delete unit (403)
- ✅ admin cannot delete unit from course they dont manage (403)
- ⚠️ **MISSING:** cannot delete non-existent unit (404)

### Publish/Unpublish
**Positive:**
- ✅ admin can publish unit

**Negative:**
- ⚠️ **MISSING:** student cannot publish unit (403)
- ⚠️ **MISSING:** admin cannot publish unit they dont manage (403)

---

## ✅ LessonCrudTest.php (10 tests)

### POST - Create Lesson
**Positive:**
- ✅ admin can create lesson in their course unit

**Negative:**
- ✅ student cannot create lesson (403)
- ✅ admin cannot create lesson in course they dont manage (403)
- ✅ cannot create lesson with missing required fields (422)
- ⚠️ **MISSING:** unauthenticated user cannot create lesson (401)

### PUT - Update Lesson
**Positive:**
- ✅ admin can update lesson in their course

**Negative:**
- ✅ student cannot update lesson (403)
- ⚠️ **MISSING:** admin cannot update lesson in course they dont manage (403)
- ⚠️ **MISSING:** cannot update non-existent lesson (404)

### DELETE - Delete Lesson
**Positive:**
- ✅ admin can delete lesson from their course

**Negative:**
- ✅ student cannot delete lesson (403)
- ⚠️ **MISSING:** admin cannot delete lesson from course they dont manage (403)
- ⚠️ **MISSING:** cannot delete non-existent lesson (404)

### Publish/Unpublish
**Positive:**
- ✅ admin can publish lesson

**Negative:**
- ⚠️ **MISSING:** student cannot publish lesson (403)
- ⚠️ **MISSING:** admin cannot publish lesson they dont manage (403)

---

## ✅ TagCrudTest.php (12 tests)

### POST - Create Tag
**Positive:**
- ✅ admin can create tag
- ✅ admin can create multiple tags at once

**Negative:**
- ✅ student cannot create tag (403)
- ✅ cannot create tag with duplicate name (422)
- ✅ cannot create tag with missing name (422)
- ⚠️ **MISSING:** unauthenticated user cannot create tag (401)

### PUT - Update Tag
**Positive:**
- ✅ admin can update tag

**Negative:**
- ✅ student cannot update tag (403)
- ✅ cannot update non-existent tag (404)
- ⚠️ **MISSING:** cannot update tag with duplicate name (422)

### DELETE - Delete Tag
**Positive:**
- ✅ admin can delete tag

**Negative:**
- ✅ student cannot delete tag (403)
- ✅ cannot delete non-existent tag (404)
- ⚠️ **MISSING:** unauthenticated user cannot delete tag (401)

---

## ✅ AssignmentCrudTest.php (10 tests)

### POST - Create Assignment
**Positive:**
- ✅ admin can create assignment

**Negative:**
- ✅ student cannot create assignment (403)
- ✅ cannot create assignment with missing required fields (422)
- ⚠️ **MISSING:** unauthenticated user cannot create assignment (401)
- ⚠️ **MISSING:** admin cannot create assignment in course they dont manage (403)

### PUT - Update Assignment
**Positive:**
- ✅ admin can update assignment

**Negative:**
- ✅ student cannot update assignment (403)
- ⚠️ **MISSING:** cannot update non-existent assignment (404)
- ⚠️ **MISSING:** admin cannot update assignment they dont manage (403)

### DELETE - Delete Assignment
**Positive:**
- ✅ admin can delete assignment

**Negative:**
- ✅ student cannot delete assignment (403)
- ⚠️ **MISSING:** cannot delete non-existent assignment (404)
- ⚠️ **MISSING:** admin cannot delete assignment they dont manage (403)

### Publish/Unpublish
**Positive:**
- ✅ admin can publish assignment

**Negative:**
- ⚠️ **MISSING:** student cannot publish assignment (403)
- ⚠️ **MISSING:** admin cannot unpublish assignment (positive case)

---

## ✅ SubmissionCrudTest.php (14 tests)

### POST - Create Submission
**Positive:**
- ✅ student can create submission for published assignment

**Negative:**
- ✅ cannot create submission for draft assignment (422)
- ✅ cannot create submission without enrollment (422)
- ✅ unauthenticated user cannot create submission (401)

### PUT - Update Submission
**Positive:**
- ✅ student can update their own draft submission

**Negative:**
- ✅ cannot update graded submission (422)
- ✅ cannot update other user submission (403)
- ⚠️ **MISSING:** cannot update non-existent submission (404)
- ⚠️ **MISSING:** cannot update submitted submission (422 - hanya draft yang bisa diupdate)

### POST - Grade Submission
**Positive:**
- ✅ admin can grade submission

**Negative:**
- ✅ student cannot grade submission (403)
- ✅ cannot grade with invalid score (422)
- ⚠️ **MISSING:** cannot grade non-existent submission (404)

---

## ✅ CategoryCrudTest.php (12 tests)

### POST - Create Category
**Positive:**
- ✅ superadmin can create category

**Negative:**
- ✅ admin cannot create category (403)
- ✅ cannot create category with duplicate value (422)
- ✅ cannot create category with missing required fields (422)
- ⚠️ **MISSING:** unauthenticated user cannot create category (401)

### PUT - Update Category
**Positive:**
- ✅ superadmin can update category

**Negative:**
- ✅ admin cannot update category (403)
- ✅ cannot update non-existent category (404)
- ⚠️ **MISSING:** cannot update category with duplicate value (422)

### DELETE - Delete Category
**Positive:**
- ✅ superadmin can delete category

**Negative:**
- ✅ admin cannot delete category (403)
- ✅ cannot delete non-existent category (404)
- ⚠️ **MISSING:** unauthenticated user cannot delete category (401)

---

## ✅ EnrollmentOperationsTest.php (16 tests)

### POST - Enroll
**Positive:**
- ✅ student can enroll in auto_accept course
- ✅ student can enroll in key_based course with correct key
- ✅ student can enroll in approval course

**Negative:**
- ✅ cannot enroll in key_based course without key (422)
- ✅ cannot enroll in key_based course with wrong key (422)
- ✅ cannot enroll twice in same course (422)
- ✅ unauthenticated user cannot enroll (401)

### POST - Cancel
**Positive:**
- ✅ student can cancel their enrollment

**Negative:**
- ✅ cannot cancel non-existent enrollment (404/422)
- ✅ cannot cancel enrollment of other user (404/422)

### POST - Approve/Decline
**Positive:**
- ✅ admin can approve pending enrollment
- ✅ admin can decline pending enrollment

**Negative:**
- ✅ student cannot approve enrollment (403)
- ✅ cannot approve non-pending enrollment (422)
- ⚠️ **MISSING:** cannot approve non-existent enrollment (404)
- ⚠️ **MISSING:** admin cannot approve enrollment in course they dont manage (403)

---

## Summary

### ✅ Sudah Lengkap:
- CourseCrudTest.php - **LENGKAP** ✅
- FilteringSortingPaginationTest.php - **LENGKAP** ✅

### ⚠️ Perlu Ditambahkan Negative Cases:
1. **UnitCrudTest.php** - 5 test missing
2. **LessonCrudTest.php** - 7 test missing
3. **TagCrudTest.php** - 4 test missing
4. **AssignmentCrudTest.php** - 7 test missing
5. **SubmissionCrudTest.php** - 3 test missing
6. **CategoryCrudTest.php** - 4 test missing
7. **EnrollmentOperationsTest.php** - 2 test missing

**Total Missing: 32 negative test cases**

